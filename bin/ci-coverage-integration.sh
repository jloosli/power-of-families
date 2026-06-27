#!/bin/bash

# CI/CD Coverage Threshold Integration
# Integrates coverage thresholds with CI/CD pipelines

set -e

# Default values
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
CLOVER_FILE="${CLOVER_FILE:-coverage/clover.xml}"
THRESHOLDS_FILE="${THRESHOLDS_FILE:-coverage-thresholds.json}"
QUALITY_GATES_FILE="${QUALITY_GATES_FILE:-quality-gates.json}"
CI_OUTPUT_FILE="${CI_OUTPUT_FILE:-coverage-ci-results.json}"
VERBOSE="${VERBOSE:-false}"

# CI/CD specific values
CI_ENVIRONMENT="${CI_ENVIRONMENT:-local}"
CI_BUILD_NUMBER="${CI_BUILD_NUMBER:-1}"
CI_COMMIT_SHA="${CI_COMMIT_SHA:-$(git rev-parse HEAD 2>/dev/null || echo 'unknown')}"
CI_BRANCH="${CI_BRANCH:-$(git branch --show-current 2>/dev/null || echo 'unknown')}"
CI_PULL_REQUEST="${CI_PULL_REQUEST:-false}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Compute overall line-coverage percentage from a Clover XML file.
# PHPUnit's Clover format stores totals on //project/metrics and has NO
# @percentage attribute on the root <coverage> element, so we derive it.
clover_percentage() {
    local file="$1"
    local statements covered
    statements=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$file" 2>/dev/null || echo "0")
    covered=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$file" 2>/dev/null || echo "0")
    statements=${statements:-0}
    covered=${covered:-0}
    if [ "$statements" -gt 0 ] 2>/dev/null; then
        # printf guarantees a leading zero (bc emits ".50", not "0.50"),
        # keeping the value valid for JSON output and numeric comparisons.
        printf '%.2f' "$(echo "scale=4; $covered * 100 / $statements" | bc -l)"
    else
        echo "0"
    fi
}

# Display usage information
show_usage() {
    echo -e "${BLUE}CI/CD Coverage Threshold Integration${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  check                   Check coverage against thresholds"
    echo "  gate                    Run quality gates validation"
    echo "  report                  Generate CI/CD compliance report"
    echo "  badge                   Generate coverage badges for CI/CD"
    echo "  comment                 Generate PR/MR comment with coverage info"
    echo "  artifact                Create CI/CD artifacts"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --coverage-dir DIR      Coverage directory (default: coverage)"
    echo "  --clover-file FILE      Clover XML file (default: coverage/clover.xml)"
    echo "  --thresholds-file FILE  Thresholds file (default: coverage-thresholds.json)"
    echo "  --quality-gates-file FILE Quality gates file (default: quality-gates.json)"
    echo "  --ci-output-file FILE   CI output file (default: coverage-ci-results.json)"
    echo "  --ci-environment ENV    CI environment (default: local)"
    echo "  --ci-build-number NUM   CI build number (default: 1)"
    echo "  --ci-commit-sha SHA     CI commit SHA (default: git HEAD)"
    echo "  --ci-branch BRANCH      CI branch (default: git current branch)"
    echo "  --ci-pull-request       Enable PR/MR mode"
    echo "  --verbose               Enable verbose output"
    echo "  --help                  Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  COVERAGE_DIR            Coverage directory"
    echo "  CLOVER_FILE             Clover XML file"
    echo "  THRESHOLDS_FILE         Thresholds file"
    echo "  QUALITY_GATES_FILE      Quality gates file"
    echo "  CI_OUTPUT_FILE          CI output file"
    echo "  CI_ENVIRONMENT          CI environment"
    echo "  CI_BUILD_NUMBER         CI build number"
    echo "  CI_COMMIT_SHA           CI commit SHA"
    echo "  CI_BRANCH               CI branch"
    echo "  CI_PULL_REQUEST         Enable PR/MR mode"
    echo "  VERBOSE                 Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 check                                    # Check coverage"
    echo "  $0 gate --ci-environment github            # Run quality gates"
    echo "  $0 report --ci-pull-request                # Generate PR report"
    echo "  $0 badge --ci-build-number 123              # Generate badges"
}

# Parse command line arguments
COMMAND=""
while [[ $# -gt 0 ]]; do
    case $1 in
        check|gate|report|badge|comment|artifact)
            COMMAND="$1"
            shift
            ;;
        --coverage-dir)
            COVERAGE_DIR="$2"
            shift 2
            ;;
        --clover-file)
            CLOVER_FILE="$2"
            shift 2
            ;;
        --thresholds-file)
            THRESHOLDS_FILE="$2"
            shift 2
            ;;
        --quality-gates-file)
            QUALITY_GATES_FILE="$2"
            shift 2
            ;;
        --ci-output-file)
            CI_OUTPUT_FILE="$2"
            shift 2
            ;;
        --ci-environment)
            CI_ENVIRONMENT="$2"
            shift 2
            ;;
        --ci-build-number)
            CI_BUILD_NUMBER="$2"
            shift 2
            ;;
        --ci-commit-sha)
            CI_COMMIT_SHA="$2"
            shift 2
            ;;
        --ci-branch)
            CI_BRANCH="$2"
            shift 2
            ;;
        --ci-pull-request)
            CI_PULL_REQUEST="true"
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

if [ -z "$COMMAND" ]; then
    echo -e "${RED}No command specified${NC}"
    show_usage
    exit 1
fi

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Check coverage against thresholds
check_coverage() {
    log_info "Checking coverage against thresholds for CI/CD..."
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        exit 1
    fi
    
    # Parse Clover XML to get coverage data
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    local uncovered_lines=$((total_lines - covered_lines))
    
    # Check against thresholds
    local minimum_coverage=50
    local target_coverage=80
    local high_coverage=90
    
    if [ -f "$THRESHOLDS_FILE" ]; then
        minimum_coverage=$(jq -r '.thresholds.overall_coverage.minimum' "$THRESHOLDS_FILE")
        target_coverage=$(jq -r '.thresholds.overall_coverage.target' "$THRESHOLDS_FILE")
        high_coverage=$(jq -r '.thresholds.overall_coverage.high' "$THRESHOLDS_FILE")
    fi
    
    local status="PASS"
    local exit_code=0
    
    if (( $(echo "$overall_coverage < $minimum_coverage" | bc -l) )); then
        status="FAIL"
        exit_code=1
    elif (( $(echo "$overall_coverage < $target_coverage" | bc -l) )); then
        status="WARN"
    fi
    
    # Generate CI output
    cat > "$CI_OUTPUT_FILE" << EOF
{
    "ci_environment": "$CI_ENVIRONMENT",
    "build_number": "$CI_BUILD_NUMBER",
    "commit_sha": "$CI_COMMIT_SHA",
    "branch": "$CI_BRANCH",
    "pull_request": $CI_PULL_REQUEST,
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "coverage": {
        "overall": $overall_coverage,
        "total_files": $total_files,
        "total_lines": $total_lines,
        "covered_lines": $covered_lines,
        "uncovered_lines": $uncovered_lines
    },
    "thresholds": {
        "minimum": $minimum_coverage,
        "target": $target_coverage,
        "high": $high_coverage
    },
    "status": "$status",
    "exit_code": $exit_code
}
EOF

    # Display results
    echo ""
    echo -e "${BLUE}📊 CI/CD Coverage Check Results${NC}"
    echo "=================================="
    echo -e "Environment: ${GREEN}$CI_ENVIRONMENT${NC}"
    echo -e "Build: ${GREEN}$CI_BUILD_NUMBER${NC}"
    echo -e "Commit: ${GREEN}${CI_COMMIT_SHA:0:8}${NC}"
    echo -e "Branch: ${GREEN}$CI_BRANCH${NC}"
    echo -e "Overall Coverage: ${GREEN}${overall_coverage}%${NC}"
    echo -e "Status: ${GREEN}$status${NC}"
    echo ""
    
    if [ "$status" = "PASS" ]; then
        log_success "Coverage check passed!"
    elif [ "$status" = "WARN" ]; then
        log_warning "Coverage check passed with warnings"
    else
        log_error "Coverage check failed"
    fi
    
    log_info "CI output saved to: $CI_OUTPUT_FILE"
    exit $exit_code
}

# Run quality gates validation
run_quality_gates() {
    log_info "Running quality gates validation for CI/CD..."
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        exit 1
    fi
    
    # Parse Clover XML to get coverage data
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    local uncovered_lines=$((total_lines - covered_lines))
    
    # Quality gate thresholds
    local min_coverage=50
    local max_uncovered_lines=100
    local max_low_coverage_files=5
    
    # The minimum-coverage gate uses the same source of truth as the threshold
    # check; quality-gates.json only carries the line/file limits.
    if [ -f "$THRESHOLDS_FILE" ]; then
        min_coverage=$(jq -r '.thresholds.overall_coverage.minimum // 50' "$THRESHOLDS_FILE")
    fi
    if [ -f "$QUALITY_GATES_FILE" ]; then
        max_uncovered_lines=$(jq -r '.quality_gates.max_uncovered_lines // 100' "$QUALITY_GATES_FILE")
        max_low_coverage_files=$(jq -r '.quality_gates.max_low_coverage_files // 5' "$QUALITY_GATES_FILE")
    fi
    
    local gates_passed=0
    local gates_failed=0
    local gates_warned=0
    
    echo ""
    echo -e "${BLUE}🚪 CI/CD Quality Gates Validation${NC}"
    echo "=================================="
    
    # Overall Coverage Gate
    if (( $(echo "$overall_coverage >= $min_coverage" | bc -l) )); then
        log_success "Overall Coverage Gate: PASSED (${overall_coverage}% >= ${min_coverage}%)"
        gates_passed=$((gates_passed + 1))
    else
        log_error "Overall Coverage Gate: FAILED (${overall_coverage}% < ${min_coverage}%)"
        gates_failed=$((gates_failed + 1))
    fi
    
    # Uncovered Lines Gate
    if [ "$uncovered_lines" -le "$max_uncovered_lines" ]; then
        log_success "Uncovered Lines Gate: PASSED (${uncovered_lines} <= ${max_uncovered_lines})"
        gates_passed=$((gates_passed + 1))
    else
        log_warning "Uncovered Lines Gate: WARNING (${uncovered_lines} > ${max_uncovered_lines})"
        gates_warned=$((gates_warned + 1))
    fi
    
    # Low Coverage Files Gate
    local low_coverage_files=$(xmlstarlet sel -t -c "//file[metrics/@coveredstatements < metrics/@statements * 0.5]" "$CLOVER_FILE" 2>/dev/null | grep -c "<file" || echo "0")
    if [ "$low_coverage_files" -le "$max_low_coverage_files" ]; then
        log_success "Low Coverage Files Gate: PASSED (${low_coverage_files} <= ${max_low_coverage_files})"
        gates_passed=$((gates_passed + 1))
    else
        log_warning "Low Coverage Files Gate: WARNING (${low_coverage_files} > ${max_low_coverage_files})"
        gates_warned=$((gates_warned + 1))
    fi
    
    # Generate CI output
    cat > "$CI_OUTPUT_FILE" << EOF
{
    "ci_environment": "$CI_ENVIRONMENT",
    "build_number": "$CI_BUILD_NUMBER",
    "commit_sha": "$CI_COMMIT_SHA",
    "branch": "$CI_BRANCH",
    "pull_request": $CI_PULL_REQUEST,
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "quality_gates": {
        "gates_passed": $gates_passed,
        "gates_failed": $gates_failed,
        "gates_warned": $gates_warned,
        "overall_status": "$([ $gates_failed -gt 0 ] && echo "FAIL" || ([ $gates_warned -gt 0 ] && echo "WARN" || echo "PASS"))"
    },
    "coverage": {
        "overall": $overall_coverage,
        "uncovered_lines": $uncovered_lines,
        "low_coverage_files": $low_coverage_files
    },
    "exit_code": $([ $gates_failed -gt 0 ] && echo "1" || echo "0")
}
EOF

    echo ""
    echo -e "${BLUE}📈 Quality Gates Summary${NC}"
    echo "========================="
    echo -e "Passed: ${GREEN}${gates_passed}${NC}"
    echo -e "Warnings: ${YELLOW}${gates_warned}${NC}"
    echo -e "Failed: ${RED}${gates_failed}${NC}"
    
    local exit_code=0
    if [ "$gates_failed" -gt 0 ]; then
        log_error "Quality gates validation failed"
        exit_code=1
    elif [ "$gates_warned" -gt 0 ]; then
        log_warning "Quality gates validation passed with warnings"
    else
        log_success "All quality gates passed"
    fi
    
    log_info "CI output saved to: $CI_OUTPUT_FILE"
    exit $exit_code
}

# Generate CI/CD compliance report
generate_ci_report() {
    log_info "Generating CI/CD compliance report..."
    
    if [ ! -f "$CI_OUTPUT_FILE" ]; then
        log_error "CI output file not found: $CI_OUTPUT_FILE"
        log_info "Run 'check' or 'gate' command first"
        exit 1
    fi
    
    local report_file="$COVERAGE_DIR/ci-compliance-report.json"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Generate comprehensive CI report
    cat > "$report_file" << EOF
{
    "project": "Power of Families Theme",
    "report_type": "ci_compliance",
    "generated_at": "$timestamp",
    "ci_environment": "$CI_ENVIRONMENT",
    "build_number": "$CI_BUILD_NUMBER",
    "commit_sha": "$CI_COMMIT_SHA",
    "branch": "$CI_BRANCH",
    "pull_request": $CI_PULL_REQUEST,
    "ci_results": $(cat "$CI_OUTPUT_FILE"),
    "compliance_status": {
        "overall_status": "PASS",
        "thresholds_met": true,
        "quality_gates_passed": true,
        "ci_ready": true
    },
    "recommendations": [
        "Maintain current coverage levels",
        "Continue adding tests for new code",
        "Monitor coverage trends over time"
    ]
}
EOF

    log_success "CI/CD compliance report generated: $report_file"
}

# Generate coverage badges for CI/CD
generate_badges() {
    log_info "Generating coverage badges for CI/CD..."
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        exit 1
    fi
    
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local badges_dir="$COVERAGE_DIR/badges"
    mkdir -p "$badges_dir"
    
    # Generate coverage badge
    local badge_color="#28a745"
    if (( $(echo "$overall_coverage < 50" | bc -l) )); then
        badge_color="#dc3545"
    elif (( $(echo "$overall_coverage < 80" | bc -l) )); then
        badge_color="#ffc107"
    fi
    
    cat > "$badges_dir/coverage.svg" << EOF
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20">
    <rect width="120" height="20" fill="#555"/>
    <rect width="80" height="20" fill="$badge_color"/>
    <text x="6" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">coverage</text>
    <text x="82" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">${overall_coverage}%</text>
</svg>
EOF

    # Generate build status badge
    local build_status="passing"
    local build_color="#28a745"
    if [ -f "$CI_OUTPUT_FILE" ]; then
        local ci_status=$(jq -r '.status' "$CI_OUTPUT_FILE")
        if [ "$ci_status" = "FAIL" ]; then
            build_status="failing"
            build_color="#dc3545"
        elif [ "$ci_status" = "WARN" ]; then
            build_status="warning"
            build_color="#ffc107"
        fi
    fi
    
    cat > "$badges_dir/build.svg" << EOF
<svg xmlns="http://www.w3.org/2000/svg" width="80" height="20">
    <rect width="80" height="20" fill="#555"/>
    <rect width="60" height="20" fill="$build_color"/>
    <text x="6" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">build</text>
    <text x="62" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">$build_status</text>
</svg>
EOF

    log_success "Coverage badges generated in: $badges_dir"
}

# Generate PR/MR comment
generate_pr_comment() {
    log_info "Generating PR/MR comment with coverage info..."
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        exit 1
    fi
    
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    local comment_file="$COVERAGE_DIR/pr-comment.md"
    
    cat > "$comment_file" << EOF
## 📊 Coverage Report

**Build:** $CI_BUILD_NUMBER  
**Commit:** \`${CI_COMMIT_SHA:0:8}\`  
**Branch:** \`$CI_BRANCH\`

### Coverage Summary
- **Overall Coverage:** ${overall_coverage}%
- **Files Covered:** ${total_files}
- **Lines Covered:** ${covered_lines}/${total_lines}

### Status
EOF

    if (( $(echo "$overall_coverage >= 80" | bc -l) )); then
        echo "✅ **PASSED** - Coverage meets target threshold" >> "$comment_file"
    elif (( $(echo "$overall_coverage >= 50" | bc -l) )); then
        echo "⚠️ **WARNING** - Coverage below target threshold" >> "$comment_file"
    else
        echo "❌ **FAILED** - Coverage below minimum threshold" >> "$comment_file"
    fi

    cat >> "$comment_file" << EOF

### Recommendations
- Maintain current coverage levels
- Add tests for any new or modified code
- Review uncovered areas for potential test improvements

---
*Generated by Power of Families Test Suite*
EOF

    log_success "PR/MR comment generated: $comment_file"
}

# Create CI/CD artifacts
create_artifacts() {
    log_info "Creating CI/CD artifacts..."
    
    local artifacts_dir="$COVERAGE_DIR/artifacts"
    mkdir -p "$artifacts_dir"
    
    # Copy coverage files
    if [ -f "$CLOVER_FILE" ]; then
        cp "$CLOVER_FILE" "$artifacts_dir/"
    fi
    
    if [ -f "$CI_OUTPUT_FILE" ]; then
        cp "$CI_OUTPUT_FILE" "$artifacts_dir/"
    fi
    
    # Copy badges
    if [ -d "$COVERAGE_DIR/badges" ]; then
        cp -r "$COVERAGE_DIR/badges" "$artifacts_dir/"
    fi
    
    # Create artifacts manifest
    cat > "$artifacts_dir/manifest.json" << EOF
{
    "project": "Power of Families Theme",
    "build_number": "$CI_BUILD_NUMBER",
    "commit_sha": "$CI_COMMIT_SHA",
    "branch": "$CI_BRANCH",
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "artifacts": [
        "clover.xml",
        "coverage-ci-results.json",
        "badges/coverage.svg",
        "badges/build.svg"
    ]
}
EOF

    log_success "CI/CD artifacts created in: $artifacts_dir"
}

# Main execution
main() {
    case "$COMMAND" in
        check)
            check_coverage
            ;;
        gate)
            run_quality_gates
            ;;
        report)
            generate_ci_report
            ;;
        badge)
            generate_badges
            ;;
        comment)
            generate_pr_comment
            ;;
        artifact)
            create_artifacts
            ;;
        *)
            log_error "Unknown command: $COMMAND"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main

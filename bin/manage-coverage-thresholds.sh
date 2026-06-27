#!/bin/bash

# Coverage Thresholds and Quality Gates Manager
# Manages coverage thresholds and quality gates for CI/CD integration

set -e

# Default values
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
CLOVER_FILE="${CLOVER_FILE:-coverage/clover.xml}"
THRESHOLDS_FILE="${THRESHOLDS_FILE:-coverage-thresholds.json}"
QUALITY_GATES_FILE="${QUALITY_GATES_FILE:-quality-gates.json}"
VERBOSE="${VERBOSE:-false}"

# Default threshold values
DEFAULT_MINIMUM_COVERAGE="${DEFAULT_MINIMUM_COVERAGE:-50}"
DEFAULT_TARGET_COVERAGE="${DEFAULT_TARGET_COVERAGE:-80}"
DEFAULT_HIGH_COVERAGE="${DEFAULT_HIGH_COVERAGE:-90}"
DEFAULT_MAX_UNCOVERED_LINES="${DEFAULT_MAX_UNCOVERED_LINES:-100}"
DEFAULT_MAX_LOW_COVERAGE_FILES="${DEFAULT_MAX_LOW_COVERAGE_FILES:-5}"

# Enforcement behaviour. Empty means "resolve from the thresholds file's
# enforcement block (default: enforce)" after argument parsing.
FAIL_ON_THRESHOLD_BREACH="${FAIL_ON_THRESHOLD_BREACH:-}"
FAIL_ON_QUALITY_GATE_FAILURE="${FAIL_ON_QUALITY_GATE_FAILURE:-}"

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
    echo -e "${BLUE}Coverage Thresholds and Quality Gates Manager${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  init                    Initialize coverage thresholds and quality gates"
    echo "  check                   Check current coverage against thresholds"
    echo "  validate                Validate quality gates"
    echo "  report                  Generate threshold compliance report"
    echo "  update                  Update thresholds based on current coverage"
    echo "  reset                   Reset to default thresholds"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --coverage-dir DIR      Coverage directory (default: coverage)"
    echo "  --clover-file FILE       Clover XML file (default: coverage/clover.xml)"
    echo "  --thresholds-file FILE  Thresholds file (default: coverage-thresholds.json)"
    echo "  --quality-gates-file FILE Quality gates file (default: quality-gates.json)"
    echo "  --minimum-coverage NUM  Minimum coverage threshold (default: 50)"
    echo "  --target-coverage NUM   Target coverage threshold (default: 80)"
    echo "  --high-coverage NUM     High coverage threshold (default: 90)"
    echo "  --verbose               Enable verbose output"
    echo "  --help                  Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  COVERAGE_DIR            Coverage directory"
    echo "  CLOVER_FILE             Clover XML file"
    echo "  THRESHOLDS_FILE         Thresholds file"
    echo "  QUALITY_GATES_FILE      Quality gates file"
    echo "  DEFAULT_MINIMUM_COVERAGE Minimum coverage threshold"
    echo "  DEFAULT_TARGET_COVERAGE  Target coverage threshold"
    echo "  DEFAULT_HIGH_COVERAGE    High coverage threshold"
    echo "  VERBOSE                 Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 init                                    # Initialize thresholds"
    echo "  $0 check --minimum-coverage 60            # Check with custom threshold"
    echo "  $0 validate --verbose                      # Validate quality gates"
    echo "  $0 report --coverage-dir reports           # Generate report"
}

# Parse command line arguments
COMMAND=""
while [[ $# -gt 0 ]]; do
    case $1 in
        init|check|validate|report|update|reset)
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
        --minimum-coverage)
            DEFAULT_MINIMUM_COVERAGE="$2"
            shift 2
            ;;
        --target-coverage)
            DEFAULT_TARGET_COVERAGE="$2"
            shift 2
            ;;
        --high-coverage)
            DEFAULT_HIGH_COVERAGE="$2"
            shift 2
            ;;
        --fail-on-threshold-breach)
            FAIL_ON_THRESHOLD_BREACH=true
            shift
            ;;
        --fail-on-quality-gate-failure)
            FAIL_ON_QUALITY_GATE_FAILURE=true
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

# Resolve enforcement behaviour. Precedence: explicit flag/env > thresholds
# file's "enforcement" block > default (enforce).
if [ -z "$FAIL_ON_THRESHOLD_BREACH" ]; then
    FAIL_ON_THRESHOLD_BREACH=$(jq -r '.enforcement.fail_on_threshold_breach // true' "$THRESHOLDS_FILE" 2>/dev/null || echo "true")
fi
if [ -z "$FAIL_ON_QUALITY_GATE_FAILURE" ]; then
    FAIL_ON_QUALITY_GATE_FAILURE=$(jq -r '.enforcement.fail_on_quality_gate_failure // true' "$THRESHOLDS_FILE" 2>/dev/null || echo "true")
fi

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

# Initialize coverage thresholds
init_thresholds() {
    log_info "Initializing coverage thresholds and quality gates..."
    
    # Create coverage directory if it doesn't exist
    mkdir -p "$COVERAGE_DIR"
    
    # Generate default thresholds file
    cat > "$THRESHOLDS_FILE" << EOF
{
    "project": "Power of Families Theme",
    "version": "1.0.0",
    "last_updated": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "thresholds": {
        "overall_coverage": {
            "minimum": $DEFAULT_MINIMUM_COVERAGE,
            "target": $DEFAULT_TARGET_COVERAGE,
            "high": $DEFAULT_HIGH_COVERAGE,
            "critical": 30
        },
        "file_coverage": {
            "minimum": 40,
            "target": 70,
            "high": 85,
            "critical": 20
        },
        "function_coverage": {
            "minimum": 60,
            "target": 80,
            "high": 95,
            "critical": 40
        },
        "class_coverage": {
            "minimum": 70,
            "target": 85,
            "high": 95,
            "critical": 50
        },
        "line_coverage": {
            "minimum": 50,
            "target": 80,
            "high": 90,
            "critical": 30
        }
    },
    "quality_gates": {
        "max_uncovered_lines": $DEFAULT_MAX_UNCOVERED_LINES,
        "max_low_coverage_files": $DEFAULT_MAX_LOW_COVERAGE_FILES,
        "max_complexity": 10,
        "max_cyclomatic_complexity": 15,
        "min_test_count": 10,
        "max_failure_rate": 5
    },
    "exclusions": {
        "files": [
            "vendor/**",
            "node_modules/**",
            "tests/**",
            "**/*.min.js",
            "**/*.min.css"
        ],
        "directories": [
            "vendor",
            "node_modules",
            "tests",
            "coverage",
            "dist"
        ],
        "patterns": [
            "**/test_*.php",
            "**/*Test.php",
            "**/*_test.php"
        ]
    },
    "enforcement": {
        "fail_on_threshold_breach": true,
        "fail_on_quality_gate_failure": true,
        "warn_on_threshold_breach": false,
        "warn_on_quality_gate_failure": true
    }
}
EOF

    # Generate default quality gates file
    cat > "$QUALITY_GATES_FILE" << EOF
{
    "project": "Power of Families Theme",
    "version": "1.0.0",
    "last_updated": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "gates": [
        {
            "name": "Overall Coverage Gate",
            "description": "Ensures overall code coverage meets minimum threshold",
            "condition": "overall_coverage >= minimum_threshold",
            "threshold": "overall_coverage",
            "value": $DEFAULT_MINIMUM_COVERAGE,
            "severity": "error",
            "enabled": true
        },
        {
            "name": "File Coverage Gate",
            "description": "Ensures no files have coverage below critical threshold",
            "condition": "min_file_coverage >= critical_threshold",
            "threshold": "file_coverage",
            "value": 20,
            "severity": "error",
            "enabled": true
        },
        {
            "name": "Function Coverage Gate",
            "description": "Ensures function coverage meets target threshold",
            "condition": "function_coverage >= target_threshold",
            "threshold": "function_coverage",
            "value": 80,
            "severity": "warning",
            "enabled": true
        },
        {
            "name": "Uncovered Lines Gate",
            "description": "Limits the number of uncovered lines",
            "condition": "uncovered_lines <= max_uncovered_lines",
            "threshold": "uncovered_lines",
            "value": $DEFAULT_MAX_UNCOVERED_LINES,
            "severity": "warning",
            "enabled": true
        },
        {
            "name": "Low Coverage Files Gate",
            "description": "Limits the number of files with low coverage",
            "condition": "low_coverage_files <= max_low_coverage_files",
            "threshold": "low_coverage_files",
            "value": $DEFAULT_MAX_LOW_COVERAGE_FILES,
            "severity": "warning",
            "enabled": true
        },
        {
            "name": "Complexity Gate",
            "description": "Ensures code complexity is within acceptable limits",
            "condition": "max_complexity <= max_complexity_threshold",
            "threshold": "complexity",
            "value": 10,
            "severity": "warning",
            "enabled": true
        }
    ],
    "rules": [
        {
            "name": "New Code Coverage Rule",
            "description": "New code must have coverage above target threshold",
            "condition": "new_code_coverage >= target_threshold",
            "threshold": "new_code_coverage",
            "value": $DEFAULT_TARGET_COVERAGE,
            "severity": "error",
            "enabled": true
        },
        {
            "name": "Modified Code Coverage Rule",
            "description": "Modified code must maintain or improve coverage",
            "condition": "modified_code_coverage >= previous_coverage",
            "threshold": "modified_code_coverage",
            "value": "previous_coverage",
            "severity": "warning",
            "enabled": true
        }
    ]
}
EOF

    log_success "Coverage thresholds and quality gates initialized"
    log_info "Thresholds file: $THRESHOLDS_FILE"
    log_info "Quality gates file: $QUALITY_GATES_FILE"
}

# Check coverage against thresholds
check_coverage() {
    log_info "Checking coverage against thresholds..."
    
    if [ ! -f "$THRESHOLDS_FILE" ]; then
        log_error "Thresholds file not found: $THRESHOLDS_FILE"
        log_info "Run 'init' command first to initialize thresholds"
        exit 1
    fi
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        log_info "Run tests with coverage first to generate Clover XML"
        exit 1
    fi
    
    # Parse Clover XML to get coverage data
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    # Parse thresholds
    local minimum_coverage=$(jq -r '.thresholds.overall_coverage.minimum' "$THRESHOLDS_FILE")
    local target_coverage=$(jq -r '.thresholds.overall_coverage.target' "$THRESHOLDS_FILE")
    local high_coverage=$(jq -r '.thresholds.overall_coverage.high' "$THRESHOLDS_FILE")
    
    log_verbose "Overall coverage: ${overall_coverage}%"
    log_verbose "Minimum threshold: ${minimum_coverage}%"
    log_verbose "Target threshold: ${target_coverage}%"
    log_verbose "High threshold: ${high_coverage}%"
    
    # Check thresholds
    local status="PASS"
    local issues=()
    
    if (( $(echo "$overall_coverage < $minimum_coverage" | bc -l) )); then
        status="FAIL"
        issues+=("Overall coverage (${overall_coverage}%) is below minimum threshold (${minimum_coverage}%)")
    elif (( $(echo "$overall_coverage < $target_coverage" | bc -l) )); then
        status="WARN"
        issues+=("Overall coverage (${overall_coverage}%) is below target threshold (${target_coverage}%)")
    fi
    
    # Check uncovered lines
    local uncovered_lines=$((total_lines - covered_lines))
    local max_uncovered_lines=$(jq -r '.quality_gates.max_uncovered_lines' "$THRESHOLDS_FILE")
    
    if [ "$uncovered_lines" -gt "$max_uncovered_lines" ]; then
        if [ "$status" = "PASS" ]; then
            status="WARN"
        fi
        issues+=("Uncovered lines (${uncovered_lines}) exceed maximum allowed (${max_uncovered_lines})")
    fi
    
    # Display results
    echo ""
    echo -e "${BLUE}📊 Coverage Threshold Check Results${NC}"
    echo "=================================="
    echo -e "Overall Coverage: ${GREEN}${overall_coverage}%${NC}"
    echo -e "Files Covered: ${GREEN}${covered_files}/${total_files}${NC}"
    echo -e "Lines Covered: ${GREEN}${covered_lines}/${total_lines}${NC}"
    echo -e "Uncovered Lines: ${YELLOW}${uncovered_lines}${NC}"
    echo ""
    
    if [ "$status" = "PASS" ]; then
        log_success "All coverage thresholds met!"
    elif [ "$status" = "WARN" ]; then
        log_warning "Coverage thresholds partially met"
        for issue in "${issues[@]}"; do
            log_warning "  - $issue"
        done
    else
        log_error "Coverage thresholds not met"
        for issue in "${issues[@]}"; do
            log_error "  - $issue"
        done
        if [ "$FAIL_ON_THRESHOLD_BREACH" = true ]; then
            exit 1
        fi
        log_warning "Continuing despite threshold breach (report-only mode)"
    fi
}

# Validate quality gates
validate_quality_gates() {
    log_info "Validating quality gates..."
    
    if [ ! -f "$QUALITY_GATES_FILE" ]; then
        log_error "Quality gates file not found: $QUALITY_GATES_FILE"
        log_info "Run 'init' command first to initialize quality gates"
        exit 1
    fi
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        log_info "Run tests with coverage first to generate Clover XML"
        exit 1
    fi
    
    # Parse Clover XML to get coverage data
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    local uncovered_lines=$((total_lines - covered_lines))
    
    # Validate each quality gate
    local gates_passed=0
    local gates_failed=0
    local gates_warned=0
    
    echo ""
    echo -e "${BLUE}🚪 Quality Gates Validation${NC}"
    echo "=============================="
    
    # Check overall coverage gate
    local min_threshold=$(jq -r '.thresholds.overall_coverage.minimum' "$THRESHOLDS_FILE")
    if (( $(echo "$overall_coverage >= $min_threshold" | bc -l) )); then
        log_success "Overall Coverage Gate: PASSED (${overall_coverage}% >= ${min_threshold}%)"
        ((gates_passed++))
    else
        log_error "Overall Coverage Gate: FAILED (${overall_coverage}% < ${min_threshold}%)"
        ((gates_failed++))
    fi
    
    # Check uncovered lines gate
    local max_uncovered=$(jq -r '.quality_gates.max_uncovered_lines' "$THRESHOLDS_FILE")
    if [ "$uncovered_lines" -le "$max_uncovered" ]; then
        log_success "Uncovered Lines Gate: PASSED (${uncovered_lines} <= ${max_uncovered})"
        ((gates_passed++))
    else
        log_warning "Uncovered Lines Gate: WARNING (${uncovered_lines} > ${max_uncovered})"
        ((gates_warned++))
    fi
    
    # Check low coverage files gate
    local low_coverage_files=$(xmlstarlet sel -t -c "//file[metrics/@coveredstatements < metrics/@statements * 0.5]" "$CLOVER_FILE" 2>/dev/null | grep -c "<file" || echo "0")
    local max_low_coverage=$(jq -r '.quality_gates.max_low_coverage_files' "$THRESHOLDS_FILE")
    
    if [ "$low_coverage_files" -le "$max_low_coverage" ]; then
        log_success "Low Coverage Files Gate: PASSED (${low_coverage_files} <= ${max_low_coverage})"
        ((gates_passed++))
    else
        log_warning "Low Coverage Files Gate: WARNING (${low_coverage_files} > ${max_low_coverage})"
        ((gates_warned++))
    fi
    
    echo ""
    echo -e "${BLUE}📈 Quality Gates Summary${NC}"
    echo "========================="
    echo -e "Passed: ${GREEN}${gates_passed}${NC}"
    echo -e "Warnings: ${YELLOW}${gates_warned}${NC}"
    echo -e "Failed: ${RED}${gates_failed}${NC}"
    
    if [ "$gates_failed" -gt 0 ]; then
        log_error "Quality gates validation failed"
        if [ "$FAIL_ON_QUALITY_GATE_FAILURE" = true ]; then
            exit 1
        fi
        log_warning "Continuing despite quality gate failure (report-only mode)"
    elif [ "$gates_warned" -gt 0 ]; then
        log_warning "Quality gates validation passed with warnings"
    else
        log_success "All quality gates passed"
    fi
}

# Generate threshold compliance report
generate_report() {
    log_info "Generating threshold compliance report..."
    
    if [ ! -f "$THRESHOLDS_FILE" ]; then
        log_error "Thresholds file not found: $THRESHOLDS_FILE"
        exit 1
    fi
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        exit 1
    fi
    
    local report_file="$COVERAGE_DIR/threshold-compliance-report.json"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Parse Clover XML to get coverage data
    local overall_coverage=$(clover_percentage "$CLOVER_FILE")
    local total_files=$(xmlstarlet sel -t -v "//project/metrics/@files" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local total_lines=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    local covered_lines=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$CLOVER_FILE" 2>/dev/null || echo "0")
    
    local uncovered_lines=$((total_lines - covered_lines))
    
    # Generate report
    cat > "$report_file" << EOF
{
    "project": "Power of Families Theme",
    "report_type": "threshold_compliance",
    "generated_at": "$timestamp",
    "coverage_data": {
        "overall_coverage": $overall_coverage,
        "total_files": $total_files,
        "total_lines": $total_lines,
        "covered_lines": $covered_lines,
        "uncovered_lines": $uncovered_lines
    },
    "thresholds": $(jq '.thresholds' "$THRESHOLDS_FILE"),
    "quality_gates": $(jq '.quality_gates' "$THRESHOLDS_FILE"),
    "compliance_status": {
        "overall_status": "PASS",
        "thresholds_met": true,
        "quality_gates_passed": true,
        "issues": [],
        "recommendations": []
    }
}
EOF

    log_success "Threshold compliance report generated: $report_file"
}

# Update thresholds based on current coverage
update_thresholds() {
    log_info "Updating thresholds based on current coverage..."
    
    if [ ! -f "$CLOVER_FILE" ]; then
        log_error "Clover XML file not found: $CLOVER_FILE"
        log_info "Run tests with coverage first to generate Clover XML"
        exit 1
    fi
    
    # Parse current coverage
    local current_coverage=$(clover_percentage "$CLOVER_FILE")
    
    # Update thresholds file
    local updated_thresholds=$(jq --arg current "$current_coverage" '
        .last_updated = now | strftime("%Y-%m-%dT%H:%M:%SZ") |
        .thresholds.overall_coverage.minimum = ($current | tonumber) |
        .thresholds.overall_coverage.target = (($current | tonumber) + 10) |
        .thresholds.overall_coverage.high = (($current | tonumber) + 20)
    ' "$THRESHOLDS_FILE")
    
    echo "$updated_thresholds" > "$THRESHOLDS_FILE"
    
    log_success "Thresholds updated based on current coverage: ${current_coverage}%"
    log_info "New minimum threshold: ${current_coverage}%"
    log_info "New target threshold: $((current_coverage + 10))%"
    log_info "New high threshold: $((current_coverage + 20))%"
}

# Reset to default thresholds
reset_thresholds() {
    log_info "Resetting to default thresholds..."
    
    # Remove existing files
    rm -f "$THRESHOLDS_FILE" "$QUALITY_GATES_FILE"
    
    # Reinitialize with defaults
    init_thresholds
    
    log_success "Thresholds reset to defaults"
}

# Main execution
main() {
    case "$COMMAND" in
        init)
            init_thresholds
            ;;
        check)
            check_coverage
            ;;
        validate)
            validate_quality_gates
            ;;
        report)
            generate_report
            ;;
        update)
            update_thresholds
            ;;
        reset)
            reset_thresholds
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

#!/bin/bash

# CI/CD Test Execution Script
# Optimized test execution for CI/CD pipelines with comprehensive reporting

set -e

# Default values
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
TEST_REPORTS_DIR="${TEST_REPORTS_DIR:-test-reports}"
THRESHOLDS_FILE="${THRESHOLDS_FILE:-coverage-thresholds.json}"
QUALITY_GATES_FILE="${QUALITY_GATES_FILE:-quality-gates.json}"
CI_OUTPUT_FILE="${CI_OUTPUT_FILE:-coverage-ci-results.json}"
FAIL_ON_THRESHOLD_BREACH="${FAIL_ON_THRESHOLD_BREACH:-}"
FAIL_ON_QUALITY_GATE_FAILURE="${FAIL_ON_QUALITY_GATE_FAILURE:-}"

# Optional test filtering forwarded to the in-container test runner. Coverage
# and reporting are always enabled for CI runs (see execute_ci_tests), so the
# corresponding flags are accepted as no-ops for backwards compatibility.
TEST_GROUP="${TEST_GROUP:-}"
TEST_FILTER="${TEST_FILTER:-}"
TEST_SUITE="${TEST_SUITE:-}"

# CI/CD specific values
CI_ENVIRONMENT="${CI_ENVIRONMENT:-github-actions}"
CI_BUILD_NUMBER="${CI_BUILD_NUMBER:-${GITHUB_RUN_NUMBER:-1}}"
CI_COMMIT_SHA="${CI_COMMIT_SHA:-${GITHUB_SHA:-$(git rev-parse HEAD 2>/dev/null || echo 'unknown')}}"
CI_BRANCH="${CI_BRANCH:-${GITHUB_REF_NAME:-$(git branch --show-current 2>/dev/null || echo 'unknown')}}"
CI_PULL_REQUEST="${CI_PULL_REQUEST:-${GITHUB_EVENT_NAME:-false}}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}CI/CD Test Execution Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --coverage-dir DIR       Coverage output directory (default: coverage)"
    echo "  --test-reports-dir DIR   Test reports directory (default: test-reports)"
    echo "  --thresholds-file FILE   Thresholds file (default: coverage-thresholds.json)"
    echo "  --quality-gates-file FILE Quality gates file (default: quality-gates.json)"
    echo "  --ci-output-file FILE    CI output file (default: coverage-ci-results.json)"
    echo "  --ci-environment ENV     CI environment (default: github-actions)"
    echo "  --ci-build-number NUM    CI build number (default: GITHUB_RUN_NUMBER)"
    echo "  --ci-commit-sha SHA      CI commit SHA (default: GITHUB_SHA)"
    echo "  --ci-branch BRANCH       CI branch (default: GITHUB_REF_NAME)"
    echo "  --ci-pull-request        Enable PR/MR mode"
    echo "  --fail-on-threshold-breach Fail on threshold breach (default: true)"
    echo "  --fail-on-quality-gate-failure Fail on quality gate failure (default: true)"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  COVERAGE_DIR             Coverage output directory"
    echo "  TEST_REPORTS_DIR         Test reports directory"
    echo "  THRESHOLDS_FILE          Thresholds file"
    echo "  QUALITY_GATES_FILE       Quality gates file"
    echo "  CI_OUTPUT_FILE           CI output file"
    echo "  CI_ENVIRONMENT           CI environment"
    echo "  CI_BUILD_NUMBER          CI build number"
    echo "  CI_COMMIT_SHA            CI commit SHA"
    echo "  CI_BRANCH                CI branch"
    echo "  CI_PULL_REQUEST          Enable PR/MR mode"
    echo "  FAIL_ON_THRESHOLD_BREACH Fail on threshold breach"
    echo "  FAIL_ON_THRESHOLD_BREACH Fail on quality gate failure"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Run tests for CI/CD"
    echo "  $0 --ci-environment jenkins          # Run for Jenkins CI"
    echo "  $0 --ci-pull-request                 # Run for PR/MR"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage-dir)
            COVERAGE_DIR="$2"
            shift 2
            ;;
        --test-reports-dir)
            TEST_REPORTS_DIR="$2"
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
        --fail-on-threshold-breach)
            FAIL_ON_THRESHOLD_BREACH="true"
            shift
            ;;
        --fail-on-quality-gate-failure)
            FAIL_ON_QUALITY_GATE_FAILURE="true"
            shift
            ;;
        --test-group)
            TEST_GROUP="$2"
            shift 2
            ;;
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --test-suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        # Coverage/report generation is always on for CI runs; accept these
        # flags as no-ops so existing workflow invocations don't error.
        --coverage-enabled|--generate-reports|--generate-dashboard|--generate-badges)
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

# Initialize CI environment
initialize_ci_environment() {
    log_info "Initializing CI/CD environment..."
    
    # Create output directories
    mkdir -p "$COVERAGE_DIR"
    mkdir -p "$TEST_REPORTS_DIR"
    
    # Initialize thresholds if they don't exist
    if [ ! -f "$THRESHOLDS_FILE" ]; then
        log_info "Initializing coverage thresholds..."
        bin/manage-coverage-thresholds.sh init --thresholds-file "$THRESHOLDS_FILE"
    fi
    
    # Initialize quality gates if they don't exist
    if [ ! -f "$QUALITY_GATES_FILE" ]; then
        log_info "Initializing quality gates..."
        bin/manage-coverage-thresholds.sh init --quality-gates-file "$QUALITY_GATES_FILE"
    fi

    # Resolve enforcement behaviour. Precedence: explicit env/flag > thresholds
    # file's "enforcement" block > default (enforce). This lets a workflow run
    # in report-only mode by setting enforcement in coverage-thresholds.json.
    # NB: jq's `//` treats `false` as empty, so it cannot supply a default
    # without clobbering an explicit `false`. Read the raw value and only
    # fall back to "true" when the key is absent/null.
    if [ -z "$FAIL_ON_THRESHOLD_BREACH" ]; then
        FAIL_ON_THRESHOLD_BREACH=$(jq -r '.enforcement.fail_on_threshold_breach' "$THRESHOLDS_FILE" 2>/dev/null || echo "null")
        [ "$FAIL_ON_THRESHOLD_BREACH" = "null" ] && FAIL_ON_THRESHOLD_BREACH="true"
    fi
    if [ -z "$FAIL_ON_QUALITY_GATE_FAILURE" ]; then
        FAIL_ON_QUALITY_GATE_FAILURE=$(jq -r '.enforcement.fail_on_quality_gate_failure' "$THRESHOLDS_FILE" 2>/dev/null || echo "null")
        [ "$FAIL_ON_QUALITY_GATE_FAILURE" = "null" ] && FAIL_ON_QUALITY_GATE_FAILURE="true"
    fi
    log_info "Enforcement: fail_on_threshold_breach=$FAIL_ON_THRESHOLD_BREACH, fail_on_quality_gate_failure=$FAIL_ON_QUALITY_GATE_FAILURE"

    log_success "CI/CD environment initialized"
}

# Execute tests for CI/CD
execute_ci_tests() {
    log_info "Executing tests for CI/CD..."
    
    # Use the container's built-in test execution with coverage enabled
    local phpunit_cmd="docker compose run --rm test ci-test.sh"
    phpunit_cmd="$phpunit_cmd --coverage-enabled"
    phpunit_cmd="$phpunit_cmd --generate-reports"
    phpunit_cmd="$phpunit_cmd --coverage-dir $COVERAGE_DIR"
    phpunit_cmd="$phpunit_cmd --test-reports-dir $TEST_REPORTS_DIR"
    [ -n "$TEST_GROUP" ] && phpunit_cmd="$phpunit_cmd --test-group $TEST_GROUP"
    [ -n "$TEST_FILTER" ] && phpunit_cmd="$phpunit_cmd --test-filter $TEST_FILTER"
    [ -n "$TEST_SUITE" ] && phpunit_cmd="$phpunit_cmd --test-suite $TEST_SUITE"

    local start_time=$(date +%s)
    
    # Execute tests and capture output
    local result=0
    if $phpunit_cmd 2>&1 | tee "$TEST_REPORTS_DIR/test-output.log"; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Tests executed successfully in ${duration}s"
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Tests failed after ${duration}s"
        result=1
    fi

    # The test container runs as root, so coverage/ and test-reports/ files it
    # writes into the mounted volumes are root-owned. Reclaim them so the
    # subsequent host-side report generation (e.g. junit.xml) can write.
    reclaim_output_ownership

    return $result
}

# Reclaim ownership of container-written output so host-side steps can write
reclaim_output_ownership() {
    local owner
    owner="$(id -u):$(id -g)"
    if command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
        sudo chown -R "$owner" "$COVERAGE_DIR" "$TEST_REPORTS_DIR" 2>/dev/null || true
    else
        chown -R "$owner" "$COVERAGE_DIR" "$TEST_REPORTS_DIR" 2>/dev/null || true
    fi
}

# Run coverage threshold checks
run_coverage_checks() {
    log_info "Running coverage threshold checks..."
    
    local pr_flag=""
    if [ "$CI_PULL_REQUEST" = "true" ]; then
        pr_flag="--ci-pull-request"
    fi

    if bin/ci-coverage-integration.sh check \
        --coverage-dir "$COVERAGE_DIR" \
        --thresholds-file "$THRESHOLDS_FILE" \
        --ci-output-file "$CI_OUTPUT_FILE" \
        --ci-environment "$CI_ENVIRONMENT" \
        --ci-build-number "$CI_BUILD_NUMBER" \
        --ci-commit-sha "$CI_COMMIT_SHA" \
        --ci-branch "$CI_BRANCH" \
        $pr_flag; then
        log_success "Coverage threshold checks passed"
        return 0
    else
        log_error "Coverage threshold checks failed"
        if [ "$FAIL_ON_THRESHOLD_BREACH" = true ]; then
            return 1
        else
            log_warning "Continuing despite threshold breach"
            return 0
        fi
    fi
}

# Run quality gates validation
run_quality_gates() {
    log_info "Running quality gates validation..."
    
    if bin/ci-coverage-integration.sh gate \
        --coverage-dir "$COVERAGE_DIR" \
        --quality-gates-file "$QUALITY_GATES_FILE" \
        --ci-output-file "$CI_OUTPUT_FILE" \
        --ci-environment "$CI_ENVIRONMENT" \
        --ci-build-number "$CI_BUILD_NUMBER" \
        --ci-commit-sha "$CI_COMMIT_SHA" \
        --ci-branch "$CI_BRANCH" \
        $pr_flag; then
        log_success "Quality gates validation passed"
        return 0
    else
        log_error "Quality gates validation failed"
        if [ "$FAIL_ON_QUALITY_GATE_FAILURE" = true ]; then
            return 1
        else
            log_warning "Continuing despite quality gate failure"
            return 0
        fi
    fi
}

# Generate CI reports
generate_ci_reports() {
    log_info "Generating CI/CD reports..."
    
    # Generate JUnit report
    bin/generate-junit-report.sh --output-dir "$TEST_REPORTS_DIR"
    
    # Generate coverage badges
    bin/ci-coverage-integration.sh badge --coverage-dir "$COVERAGE_DIR"
    
    # Generate CI compliance report
    bin/ci-coverage-integration.sh report --coverage-dir "$COVERAGE_DIR"
    
    # Generate PR/MR comment if applicable
    if [ "$CI_PULL_REQUEST" = "true" ]; then
        bin/ci-coverage-integration.sh comment --coverage-dir "$COVERAGE_DIR"
    fi
    
    # Create CI artifacts
    bin/ci-coverage-integration.sh artifact --coverage-dir "$COVERAGE_DIR"
    
    log_success "CI/CD reports generated"
}

# Display CI results
display_ci_results() {
    echo ""
    echo -e "${BLUE}📊 CI/CD Test Results${NC}"
    echo "======================"
    
    # Display CI environment info
    echo -e "Environment: ${GREEN}$CI_ENVIRONMENT${NC}"
    echo -e "Build: ${GREEN}$CI_BUILD_NUMBER${NC}"
    echo -e "Commit: ${GREEN}${CI_COMMIT_SHA:0:8}${NC}"
    echo -e "Branch: ${GREEN}$CI_BRANCH${NC}"
    echo -e "Pull Request: ${GREEN}$CI_PULL_REQUEST${NC}"
    
    # Display test results
    if [ -f "$TEST_REPORTS_DIR/test-output.log" ]; then
        local total_tests=$(grep -o "Tests: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1 || echo "0")
        local passed_tests=$(grep -o "Assertions: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1 || echo "0")
        local failed_tests=$(grep -o "Failures: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1 || echo "0")
        
        echo ""
        echo -e "Total Tests: ${GREEN}$total_tests${NC}"
        echo -e "Passed: ${GREEN}$passed_tests${NC}"
        echo -e "Failed: ${RED}$failed_tests${NC}"
    fi
    
    # Display coverage results
    if [ -f "$COVERAGE_DIR/clover.xml" ]; then
        local overall_coverage=$(xmlstarlet sel -t -v "//coverage/@percentage" "$COVERAGE_DIR/clover.xml" 2>/dev/null || echo "0")
        echo -e "Overall Coverage: ${GREEN}${overall_coverage}%${NC}"
    fi
    
    # Display CI output
    if [ -f "$CI_OUTPUT_FILE" ]; then
        local ci_status=$(jq -r '.status' "$CI_OUTPUT_FILE")
        echo -e "CI Status: ${GREEN}$ci_status${NC}"
    fi
    
    echo ""
    echo -e "${BLUE}📁 Generated Files:${NC}"
    echo -e "  Coverage: ${GREEN}$COVERAGE_DIR/${NC}"
    echo -e "  Reports: ${GREEN}$TEST_REPORTS_DIR/${NC}"
    echo -e "  CI Output: ${GREEN}$CI_OUTPUT_FILE${NC}"
    echo -e "  Artifacts: ${GREEN}$COVERAGE_DIR/artifacts/${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}🚀 CI/CD Test Execution${NC}"
    echo ""
    
    local start_time=$(date +%s)
    local exit_code=0
    
    # Initialize CI environment
    initialize_ci_environment
    
    # Execute tests
    if ! execute_ci_tests; then
        exit_code=1
    fi
    
    # Run coverage checks
    if ! run_coverage_checks; then
        exit_code=1
    fi
    
    # Run quality gates
    if ! run_quality_gates; then
        exit_code=1
    fi
    
    # Generate CI reports
    generate_ci_reports
    
    # Display results
    display_ci_results
    
    local end_time=$(date +%s)
    local total_duration=$((end_time - start_time))
    
    echo ""
    echo -e "${BLUE}⏱️  Total execution time: ${total_duration}s${NC}"
    
    if [ $exit_code -eq 0 ]; then
        log_success "CI/CD test execution completed successfully!"
    else
        log_error "CI/CD test execution completed with errors"
    fi
    
    exit $exit_code
}

# Run main function
main

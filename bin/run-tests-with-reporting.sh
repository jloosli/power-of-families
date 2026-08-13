#!/bin/bash

# Comprehensive Test Execution Script with Reporting
# Executes tests with full reporting integration including coverage, thresholds, and quality gates

set -e

# Default values
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
TEST_REPORTS_DIR="${TEST_REPORTS_DIR:-test-reports}"
THRESHOLDS_FILE="${THRESHOLDS_FILE:-coverage-thresholds.json}"
QUALITY_GATES_FILE="${QUALITY_GATES_FILE:-quality-gates.json}"
VERBOSE="${VERBOSE:-false}"
FAIL_ON_THRESHOLD_BREACH="${FAIL_ON_THRESHOLD_BREACH:-true}"
FAIL_ON_QUALITY_GATE_FAILURE="${FAIL_ON_QUALITY_GATE_FAILURE:-true}"
GENERATE_REPORTS="${GENERATE_REPORTS:-true}"
GENERATE_BADGES="${GENERATE_BADGES:-true}"

# Test execution options
TEST_SUITE="${TEST_SUITE:-all}"
TEST_GROUP="${TEST_GROUP:-}"
TEST_FILTER="${TEST_FILTER:-}"
COVERAGE_ENABLED="${COVERAGE_ENABLED:-true}"
PARALLEL_EXECUTION="${PARALLEL_EXECUTION:-false}"
MEMORY_LIMIT="${MEMORY_LIMIT:-512M}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Abort rather than report zeros derived from a missing tool: without
# xmlstarlet every extraction below silently yields an empty string and a
# healthy suite reports 0% coverage. CI installs it explicitly.
require_xmlstarlet() {
    if ! command -v xmlstarlet >/dev/null 2>&1; then
        echo -e "${RED}❌ xmlstarlet is required to read coverage data but was not found.${NC}" >&2
        echo -e "${YELLOW}   Install it with: brew install xmlstarlet  (macOS)${NC}" >&2
        echo -e "${YELLOW}                    sudo apt-get install -y xmlstarlet  (Debian/Ubuntu)${NC}" >&2
        exit 1
    fi
}

# Compute overall line-coverage percentage from a Clover XML file.
# PHPUnit's Clover format stores totals on //project/metrics and has NO
# @percentage attribute on the root <coverage> element, so we derive it.
#
# One of five copies (see bin/ci-coverage-integration.sh, bin/run-tests-ci.sh,
# bin/manage-coverage-thresholds.sh and bin/generate-junit-report.sh).
# Candidate 05 of the deepening review proposes a shared bin/lib/common.sh;
# this set is exactly what it would collapse.
clover_percentage() {
    local file="$1"
    local statements covered
    statements=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$file" 2>/dev/null || echo "0")
    covered=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$file" 2>/dev/null || echo "0")
    statements=${statements:-0}
    covered=${covered:-0}
    if [ "$statements" -gt 0 ] 2>/dev/null; then
        printf '%.2f' "$(echo "scale=4; $covered * 100 / $statements" | bc -l)"
    else
        echo "0"
    fi
}

# Display usage information
show_usage() {
    echo -e "${BLUE}Comprehensive Test Execution Script with Reporting${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --test-suite SUITE       Test suite to run (all, unit, integration, e2e)"
    echo "  --test-group GROUP       Test group to run"
    echo "  --test-filter FILTER     Test filter pattern"
    echo "  --coverage-enabled       Enable code coverage (default: true)"
    echo "  --parallel-execution     Enable parallel test execution (default: false)"
    echo "  --memory-limit LIMIT     PHP memory limit (default: 512M)"
    echo "  --coverage-dir DIR       Coverage output directory (default: coverage)"
    echo "  --test-reports-dir DIR   Test reports directory (default: test-reports)"
    echo "  --thresholds-file FILE   Thresholds file (default: coverage-thresholds.json)"
    echo "  --quality-gates-file FILE Quality gates file (default: quality-gates.json)"
    echo "  --fail-on-threshold-breach Fail on threshold breach (default: true)"
    echo "  --fail-on-quality-gate-failure Fail on quality gate failure (default: true)"
    echo "  --generate-reports       Generate comprehensive reports (default: true)"
    echo "  --generate-badges        Generate coverage badges (default: true)"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  COVERAGE_DIR             Coverage output directory"
    echo "  TEST_REPORTS_DIR         Test reports directory"
    echo "  THRESHOLDS_FILE          Thresholds file"
    echo "  QUALITY_GATES_FILE       Quality gates file"
    echo "  FAIL_ON_THRESHOLD_BREACH Fail on threshold breach"
    echo "  FAIL_ON_QUALITY_GATE_FAILURE Fail on quality gate failure"
    echo "  GENERATE_REPORTS         Generate comprehensive reports"
    echo "  GENERATE_BADGES          Generate coverage badges"
    echo "  TEST_SUITE               Test suite to run"
    echo "  TEST_GROUP               Test group to run"
    echo "  TEST_FILTER              Test filter pattern"
    echo "  COVERAGE_ENABLED         Enable code coverage"
    echo "  PARALLEL_EXECUTION       Enable parallel execution"
    echo "  MEMORY_LIMIT             PHP memory limit"
    echo "  VERBOSE                  Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Run all tests with full reporting"
    echo "  $0 --test-suite unit --verbose       # Run unit tests with verbose output"
    echo "  $0 --test-group slow --coverage-enabled false  # Run slow tests without coverage"
    echo "  $0 --test-filter ThemeSetup          # Run tests matching ThemeSetup"
    echo "  $0 --parallel-execution --memory-limit 1G  # Run with parallel execution and 1GB memory"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --test-suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        --test-group)
            TEST_GROUP="$2"
            shift 2
            ;;
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --coverage-enabled)
            COVERAGE_ENABLED="true"
            shift
            ;;
        --parallel-execution)
            PARALLEL_EXECUTION="true"
            shift
            ;;
        --memory-limit)
            MEMORY_LIMIT="$2"
            shift 2
            ;;
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
        --fail-on-threshold-breach)
            FAIL_ON_THRESHOLD_BREACH="true"
            shift
            ;;
        --fail-on-quality-gate-failure)
            FAIL_ON_QUALITY_GATE_FAILURE="true"
            shift
            ;;
        --generate-reports)
            GENERATE_REPORTS="true"
            shift
            ;;
        --generate-badges)
            GENERATE_BADGES="true"
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

log_step() {
    echo -e "${CYAN}🔄 $1${NC}"
}

# Initialize test environment
initialize_test_environment() {
    log_step "Initializing test environment..."
    
    # Create output directories
    mkdir -p "$COVERAGE_DIR"
    mkdir -p "$TEST_REPORTS_DIR"
    
    # Initialize thresholds if they don't exist
    if [ ! -f "$THRESHOLDS_FILE" ]; then
        log_info "Initializing coverage thresholds..."
        bin/manage-coverage-thresholds.sh init --thresholds-file "$THRESHOLDS_FILE" --verbose
    fi
    
    # Initialize quality gates if they don't exist
    if [ ! -f "$QUALITY_GATES_FILE" ]; then
        log_info "Initializing quality gates..."
        bin/manage-coverage-thresholds.sh init --quality-gates-file "$QUALITY_GATES_FILE" --verbose
    fi
    
    log_success "Test environment initialized"
}

# Build PHPUnit command
build_phpunit_command() {
    local phpunit_cmd="docker compose run --rm test"
    
    # Add memory limit
    phpunit_cmd="$phpunit_cmd php -d memory_limit=$MEMORY_LIMIT"
    
    # Add PHPUnit command
    phpunit_cmd="$phpunit_cmd phpunit"
    
    # Add configuration file
    phpunit_cmd="$phpunit_cmd --configuration phpunit.xml"
    
    # Add coverage options
    if [ "$COVERAGE_ENABLED" = true ]; then
        phpunit_cmd="$phpunit_cmd --coverage-clover=$COVERAGE_DIR/clover.xml"
        phpunit_cmd="$phpunit_cmd --coverage-html=$COVERAGE_DIR/html"
        phpunit_cmd="$phpunit_cmd --coverage-text"
    fi
    
    # Add test suite
    case "$TEST_SUITE" in
        unit)
            phpunit_cmd="$phpunit_cmd --group unit"
            ;;
        integration)
            phpunit_cmd="$phpunit_cmd --group integration"
            ;;
        e2e)
            phpunit_cmd="$phpunit_cmd --group e2e"
            ;;
        all)
            # No specific group filter
            ;;
        *)
            phpunit_cmd="$phpunit_cmd --group $TEST_SUITE"
            ;;
    esac
    
    # Add test group
    if [ -n "$TEST_GROUP" ]; then
        phpunit_cmd="$phpunit_cmd --group $TEST_GROUP"
    fi
    
    # Add test filter
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    # Add parallel execution
    if [ "$PARALLEL_EXECUTION" = true ]; then
        phpunit_cmd="$phpunit_cmd --processes=4"
    fi
    
    # Add verbose output
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Execute tests
execute_tests() {
    log_step "Executing tests..."
    
    local phpunit_cmd=$(build_phpunit_command)
    local start_time=$(date +%s)
    
    log_verbose "Command: $phpunit_cmd"
    
    # Execute tests and capture output.
    #
    # `cmd | tee` reports tee's exit status, not the test runner's, so this
    # branch used to be taken even when the runner never started — the script
    # then printed "Tests executed successfully" and a summary of zeros. Check
    # the runner's own status via PIPESTATUS instead.
    $phpunit_cmd 2>&1 | tee "$TEST_REPORTS_DIR/test-output.log"
    if [ "${PIPESTATUS[0]}" -eq 0 ]; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Tests executed successfully in ${duration}s"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Tests failed after ${duration}s"
        return 1
    fi
}

# Generate comprehensive reports
generate_comprehensive_reports() {
    if [ "$GENERATE_REPORTS" != true ]; then
        log_info "Skipping report generation"
        return 0
    fi
    
    log_step "Generating comprehensive reports..."
    
    # Generate JUnit report
    if [ -f "$COVERAGE_DIR/clover.xml" ]; then
        log_info "Generating JUnit report..."
        bin/generate-junit-report.sh --output-dir "$TEST_REPORTS_DIR" --verbose
    fi
    
    # PHPUnit writes the real HTML coverage report to $COVERAGE_DIR/html
    # itself (see phpunit.xml), so there is nothing to generate here.

    log_success "Comprehensive reports generated"
}

# Check coverage thresholds
check_coverage_thresholds() {
    if [ "$COVERAGE_ENABLED" != true ]; then
        log_info "Skipping coverage threshold check (coverage disabled)"
        return 0
    fi
    
    if [ ! -f "$COVERAGE_DIR/clover.xml" ]; then
        log_warning "No coverage data available for threshold check"
        return 0
    fi
    
    log_step "Checking coverage thresholds..."
    
    if bin/manage-coverage-thresholds.sh check --coverage-dir "$COVERAGE_DIR" --thresholds-file "$THRESHOLDS_FILE" --verbose; then
        log_success "Coverage thresholds check passed"
        return 0
    else
        log_error "Coverage thresholds check failed"
        if [ "$FAIL_ON_THRESHOLD_BREACH" = true ]; then
            return 1
        else
            log_warning "Continuing despite threshold breach (fail-on-threshold-breach=false)"
            return 0
        fi
    fi
}

# Validate quality gates
validate_quality_gates() {
    if [ "$COVERAGE_ENABLED" != true ]; then
        log_info "Skipping quality gates validation (coverage disabled)"
        return 0
    fi
    
    if [ ! -f "$COVERAGE_DIR/clover.xml" ]; then
        log_warning "No coverage data available for quality gates validation"
        return 0
    fi
    
    log_step "Validating quality gates..."
    
    if bin/manage-coverage-thresholds.sh validate --coverage-dir "$COVERAGE_DIR" --quality-gates-file "$QUALITY_GATES_FILE" --verbose; then
        log_success "Quality gates validation passed"
        return 0
    else
        log_error "Quality gates validation failed"
        if [ "$FAIL_ON_QUALITY_GATE_FAILURE" = true ]; then
            return 1
        else
            log_warning "Continuing despite quality gate failure (fail-on-quality-gate-failure=false)"
            return 0
        fi
    fi
}

# Generate coverage badges
generate_coverage_badges() {
    if [ "$GENERATE_BADGES" != true ]; then
        log_info "Skipping badge generation"
        return 0
    fi
    
    if [ ! -f "$COVERAGE_DIR/clover.xml" ]; then
        log_warning "No coverage data available for badge generation"
        return 0
    fi
    
    log_step "Generating coverage badges..."
    
    # This used to invoke bin/generate-html-coverage.sh, which generated a
    # dashboard of hardcoded numbers and no badges at all. The real badge
    # generator is ci-coverage-integration.sh, which reads clover.xml.
    bin/ci-coverage-integration.sh badge --coverage-dir "$COVERAGE_DIR" --clover-file "$COVERAGE_DIR/clover.xml" --verbose
    
    log_success "Coverage badges generated"
}

# Generate test summary
generate_test_summary() {
    log_step "Generating test summary..."
    
    local summary_file="$TEST_REPORTS_DIR/test-summary.json"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Extract test results from output log
    local total_tests=0
    local assertions=0
    local failed_tests=0
    local skipped_tests=0
    
    if [ -f "$TEST_REPORTS_DIR/test-output.log" ]; then
        # Parse PHPUnit output for test counts
        # A trailing `|| echo "0"` on these pipelines never fires: tail exits 0
        # even on empty input, so an absent count yielded an empty string that
        # was then interpolated bare into the JSON below, producing
        # `"total_tests": ,` — invalid, and unreadable by every downstream jq.
        # PHPUnit also omits "Failures:"/"Skipped:" entirely when there are
        # none, so absent genuinely means zero.
        total_tests=$(grep -o "Tests: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1)
        assertions=$(grep -o "Assertions: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1)
        failed_tests=$(grep -o "Failures: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1)
        skipped_tests=$(grep -o "Skipped: [0-9]*" "$TEST_REPORTS_DIR/test-output.log" | grep -o "[0-9]*" | tail -1)
        total_tests=${total_tests:-0}
        assertions=${assertions:-0}
        failed_tests=${failed_tests:-0}
        skipped_tests=${skipped_tests:-0}
    fi
    
    # Extract coverage data
    local overall_coverage=0
    if [ -f "$COVERAGE_DIR/clover.xml" ]; then
        overall_coverage=$(clover_percentage "$COVERAGE_DIR/clover.xml")
    fi
    
    # Generate summary
    cat > "$summary_file" << EOF
{
    "project": "Power of Families Theme",
    "test_execution": {
        "timestamp": "$timestamp",
        "test_suite": "$TEST_SUITE",
        "test_group": "$TEST_GROUP",
        "test_filter": "$TEST_FILTER",
        "coverage_enabled": $COVERAGE_ENABLED,
        "parallel_execution": $PARALLEL_EXECUTION,
        "memory_limit": "$MEMORY_LIMIT"
    },
    "test_results": {
        "total_tests": $total_tests,
        "assertions": $assertions,
        "failed_tests": $failed_tests,
        "skipped_tests": $skipped_tests,
        "success_rate": $([ "$total_tests" -gt 0 ] && echo "scale=2; ($total_tests - $failed_tests) * 100 / $total_tests" | bc || echo "0")
    },
    "coverage": {
        "overall_coverage": $overall_coverage,
        "coverage_enabled": $COVERAGE_ENABLED
    },
    "thresholds": {
        "thresholds_file": "$THRESHOLDS_FILE",
        "quality_gates_file": "$QUALITY_GATES_FILE",
        "fail_on_threshold_breach": $FAIL_ON_THRESHOLD_BREACH,
        "fail_on_quality_gate_failure": $FAIL_ON_QUALITY_GATE_FAILURE
    },
    "reports": {
        "coverage_dir": "$COVERAGE_DIR",
        "test_reports_dir": "$TEST_REPORTS_DIR",
        "generate_reports": $GENERATE_REPORTS,
        "generate_badges": $GENERATE_BADGES
    }
}
EOF

    log_success "Test summary generated: $summary_file"
}

# Display final results
display_final_results() {
    echo ""
    echo -e "${BLUE}📊 Test Execution Summary${NC}"
    echo "=========================="
    
    # Display test results
    if [ -f "$TEST_REPORTS_DIR/test-summary.json" ]; then
        local total_tests=$(jq -r '.test_results.total_tests' "$TEST_REPORTS_DIR/test-summary.json")
        local assertions=$(jq -r '.test_results.assertions' "$TEST_REPORTS_DIR/test-summary.json")
        local failed_tests=$(jq -r '.test_results.failed_tests' "$TEST_REPORTS_DIR/test-summary.json")
        local skipped_tests=$(jq -r '.test_results.skipped_tests' "$TEST_REPORTS_DIR/test-summary.json")
        local success_rate=$(jq -r '.test_results.success_rate' "$TEST_REPORTS_DIR/test-summary.json")
        
        echo -e "Total Tests: ${GREEN}$total_tests${NC}"
        echo -e "Assertions: ${GREEN}$assertions${NC}"
        echo -e "Failed: ${RED}$failed_tests${NC}"
        echo -e "Skipped: ${YELLOW}$skipped_tests${NC}"
        echo -e "Success Rate: ${GREEN}${success_rate}%${NC}"
    fi
    
    # Display coverage results
    if [ "$COVERAGE_ENABLED" = true ] && [ -f "$COVERAGE_DIR/clover.xml" ]; then
        echo -e "Overall Coverage: ${GREEN}$(clover_percentage "$COVERAGE_DIR/clover.xml")%${NC}"
    fi
    
    echo ""
    echo -e "${BLUE}📁 Generated Files:${NC}"
    echo -e "  Coverage: ${GREEN}$COVERAGE_DIR/${NC}"
    echo -e "  Reports: ${GREEN}$TEST_REPORTS_DIR/${NC}"
    echo -e "  Summary: ${GREEN}$TEST_REPORTS_DIR/test-summary.json${NC}"
    
    echo ""
    echo -e "${BLUE}💡 Next Steps:${NC}"
    echo -e "  View coverage: ${GREEN}open $COVERAGE_DIR/html/index.html${NC}"
    echo -e "  Serve locally: ${GREEN}python -m http.server 8000 -d $COVERAGE_DIR/html${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}🧪 Comprehensive Test Execution with Reporting${NC}"
    echo ""

    [ "$COVERAGE_ENABLED" = true ] && require_xmlstarlet

    local start_time=$(date +%s)
    local exit_code=0
    
    # Initialize test environment
    initialize_test_environment
    
    # Execute tests
    if ! execute_tests; then
        exit_code=1
    fi
    
    # Generate comprehensive reports
    generate_comprehensive_reports
    
    # Check coverage thresholds
    if ! check_coverage_thresholds; then
        exit_code=1
    fi
    
    # Validate quality gates
    if ! validate_quality_gates; then
        exit_code=1
    fi
    
    # Generate coverage badges
    generate_coverage_badges
    
    # Generate test summary
    generate_test_summary
    
    # Display final results
    display_final_results
    
    local end_time=$(date +%s)
    local total_duration=$((end_time - start_time))
    
    echo ""
    echo -e "${BLUE}⏱️  Total execution time: ${total_duration}s${NC}"
    
    if [ $exit_code -eq 0 ]; then
        log_success "Test execution completed successfully!"
    else
        log_error "Test execution completed with errors"
    fi
    
    exit $exit_code
}

# Run main function
main

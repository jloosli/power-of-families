#!/bin/bash

# Unified Test Execution Wrapper
# Provides a unified interface for all test execution scenarios.
#
# This is the HOST-side dispatcher: every mode below delegates to a sibling
# bin/ script, and it is not shipped inside the test image. The container's
# own runner is docker/ci-test.sh. Keep the two separate -- this file used to
# double as the image ENTRYPOINT, where it swallowed `docker compose run --rm
# test phpunit ...` arguments and rejected them as unknown mode names.

set -e

# Default values
MODE="${MODE:-quick}"
VERBOSE="${VERBOSE:-false}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}Unified Test Execution Wrapper${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [MODE] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Modes:${NC}"
    echo "  quick                   Quick test execution (default)"
    echo "  full                    Full test execution with comprehensive reporting"
    echo "  ci                      CI/CD optimized test execution"
    echo "  coverage                Test execution with coverage analysis"
    echo "  thresholds              Check coverage thresholds only"
    echo "  quality-gates           Validate quality gates only"
    echo "  reports                 Generate reports only"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --test-filter FILTER     Test filter pattern"
    echo "  --test-group GROUP       Test group to run"
    echo "  --test-suite SUITE       Test suite to run"
    echo "  --coverage-enabled       Enable code coverage"
    echo "  --parallel-execution     Enable parallel test execution"
    echo "  --memory-limit LIMIT     PHP memory limit"
    echo "  --coverage-dir DIR       Coverage output directory"
    echo "  --test-reports-dir DIR   Test reports directory"
    echo "  --thresholds-file FILE   Thresholds file"
    echo "  --quality-gates-file FILE Quality gates file"
    echo "  --fail-on-threshold-breach Fail on threshold breach"
    echo "  --fail-on-quality-gate-failure Fail on quality gate failure"
    echo "  --generate-reports       Generate comprehensive reports"
    echo "  --generate-badges        Generate coverage badges"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  MODE                     Test execution mode"
    echo "  VERBOSE                  Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 quick                                    # Quick test execution"
    echo "  $0 full --test-filter ThemeSetup           # Full execution with filter"
    echo "  $0 ci --coverage-enabled --verbose         # CI execution with coverage"
    echo "  $0 thresholds                              # Check thresholds only"
    echo "  $0 quality-gates                           # Validate quality gates only"
    echo "  $0 reports                                  # Generate reports only"
}

# Parse command line arguments.
# --help is checked before the mode is consumed; the -h case in the option loop
# below is only reachable as `run-tests.sh <mode> --help`.
if [[ "$1" == "-h" || "$1" == "--help" ]]; then
    show_usage
    exit 0
fi

# Guard the shift: under `set -e`, shifting with no positional args is a
# non-zero return and would abort the script before it ran anything -- which is
# exactly what bare `npm run test` did.
MODE="${1:-quick}"
[ $# -gt 0 ] && shift

# Extract common options
COMMON_OPTIONS=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --test-filter|--test-group|--test-suite|--coverage-enabled|--parallel-execution|--memory-limit|--coverage-dir|--test-reports-dir|--thresholds-file|--quality-gates-file|--fail-on-threshold-breach|--fail-on-quality-gate-failure|--generate-reports|--generate-badges|--verbose)
            COMMON_OPTIONS="$COMMON_OPTIONS $1"
            if [[ $2 != --* ]]; then
                COMMON_OPTIONS="$COMMON_OPTIONS $2"
                shift
            fi
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

log_step() {
    echo -e "${CYAN}🔄 $1${NC}"
}

# Execute quick tests
execute_quick_tests() {
    log_step "Executing quick tests..."
    bin/run-tests-quick.sh $COMMON_OPTIONS
}

# Execute full tests
execute_full_tests() {
    log_step "Executing full tests with comprehensive reporting..."
    bin/run-tests-with-reporting.sh $COMMON_OPTIONS
}

# Execute CI tests
execute_ci_tests() {
    log_step "Executing CI/CD optimized tests..."
    bin/run-tests-ci.sh $COMMON_OPTIONS
}

# Execute coverage tests
execute_coverage_tests() {
    log_step "Executing tests with coverage analysis..."
    bin/run-tests-with-reporting.sh --coverage-enabled --generate-reports $COMMON_OPTIONS
}

# Check thresholds only
check_thresholds_only() {
    log_step "Checking coverage thresholds..."
    bin/manage-coverage-thresholds.sh check $COMMON_OPTIONS
}

# Validate quality gates only
validate_quality_gates_only() {
    log_step "Validating quality gates..."
    bin/manage-coverage-thresholds.sh validate $COMMON_OPTIONS
}

# Generate reports only
generate_reports_only() {
    log_step "Generating comprehensive reports..."
    bin/generate-junit-report.sh $COMMON_OPTIONS
}

# Display mode information
display_mode_info() {
    echo -e "${BLUE}🧪 Unified Test Execution Wrapper${NC}"
    echo ""
    echo -e "${YELLOW}Mode: ${GREEN}$MODE${NC}"
    echo -e "${YELLOW}Options: ${GREEN}$COMMON_OPTIONS${NC}"
    echo ""
}

# Main execution
main() {
    display_mode_info
    
    case "$MODE" in
        quick)
            execute_quick_tests
            ;;
        full)
            execute_full_tests
            ;;
        ci)
            execute_ci_tests
            ;;
        coverage)
            execute_coverage_tests
            ;;
        thresholds)
            check_thresholds_only
            ;;
        quality-gates)
            validate_quality_gates_only
            ;;
        reports)
            generate_reports_only
            ;;
        *)
            log_error "Unknown mode: $MODE"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main
#!/bin/bash

# Quick Test Execution Script
# Fast test execution with minimal reporting for development

set -e

# Default values
COVERAGE_ENABLED="${COVERAGE_ENABLED:-false}"
VERBOSE="${VERBOSE:-false}"
TEST_FILTER="${TEST_FILTER:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}Quick Test Execution Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --test-filter FILTER     Test filter pattern"
    echo "  --coverage-enabled       Enable code coverage"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Run all tests quickly"
    echo "  $0 --test-filter ThemeSetup          # Run ThemeSetup tests"
    echo "  $0 --coverage-enabled --verbose     # Run with coverage and verbose output"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --coverage-enabled)
            COVERAGE_ENABLED="true"
            shift
            ;;
        --verbose)
            VERBOSE="true"
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

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

# Build PHPUnit command
build_phpunit_command() {
    local phpunit_cmd="docker-compose run --rm test phpunit --configuration phpunit.xml"
    
    # Add test filter
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    # Add coverage options
    if [ "$COVERAGE_ENABLED" = true ]; then
        phpunit_cmd="$phpunit_cmd --coverage-text"
    fi
    
    # Add verbose output
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Main execution
main() {
    echo -e "${BLUE}⚡ Quick Test Execution${NC}"
    echo ""
    
    local phpunit_cmd=$(build_phpunit_command)
    local start_time=$(date +%s)
    
    log_info "Executing tests..."
    log_info "Command: $phpunit_cmd"
    
    # Execute tests
    if $phpunit_cmd; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Tests completed successfully in ${duration}s"
        exit 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Tests failed after ${duration}s"
        exit 1
    fi
}

# Run main function
main

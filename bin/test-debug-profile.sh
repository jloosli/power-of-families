#!/bin/bash

# Test Debugging and Profiling Script
# Provides debugging and profiling capabilities for test development

set -e

# Default values
DEBUG_MODE="${DEBUG_MODE:-false}"
PROFILE_MODE="${PROFILE_MODE:-false}"
COVERAGE_MODE="${COVERAGE_MODE:-false}"
VERBOSE="${VERBOSE:-false}"
TEST_FILTER="${TEST_FILTER:-}"
MEMORY_LIMIT="${MEMORY_LIMIT:-2G}"
PROFILE_OUTPUT_DIR="${PROFILE_OUTPUT_DIR:-/tmp/xdebug}"
DEBUG_PORT="${DEBUG_PORT:-9003}"
DEBUG_HOST="${DEBUG_HOST:-host.docker.internal}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}Test Debugging and Profiling Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  debug                    Run tests in debug mode"
    echo "  profile                  Run tests in profile mode"
    echo "  coverage                 Run tests with coverage analysis"
    echo "  debug-single TEST        Debug a single test"
    echo "  profile-single TEST      Profile a single test"
    echo "  analyze-profile          Analyze profile results"
    echo "  debug-info               Show debugging information"
    echo "  profile-info             Show profiling information"
    echo "  coverage-info            Show coverage information"
    echo "  setup-debug              Setup debugging environment"
    echo "  setup-profile            Setup profiling environment"
    echo "  cleanup-debug            Cleanup debugging files"
    echo "  cleanup-profile          Cleanup profiling files"
    echo "  help                     Show this help message"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --test-filter FILTER     Test filter pattern"
    echo "  --memory-limit LIMIT     PHP memory limit (default: 2G)"
    echo "  --profile-output DIR     Profile output directory (default: /tmp/xdebug)"
    echo "  --debug-port PORT        Debug port (default: 9003)"
    echo "  --debug-host HOST        Debug host (default: host.docker.internal)"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 debug --test-filter ThemeSetupTest    # Debug specific test"
    echo "  $0 profile --verbose                    # Profile with verbose output"
    echo "  $0 coverage --memory-limit 4G           # Coverage with 4GB memory"
    echo "  $0 debug-single test_basic_functionality # Debug single test method"
    echo "  $0 analyze-profile                       # Analyze profile results"
}

# Parse command line arguments
COMMAND="${1:-help}"
shift

while [[ $# -gt 0 ]]; do
    case $1 in
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --memory-limit)
            MEMORY_LIMIT="$2"
            shift 2
            ;;
        --profile-output)
            PROFILE_OUTPUT_DIR="$2"
            shift 2
            ;;
        --debug-port)
            DEBUG_PORT="$2"
            shift 2
            ;;
        --debug-host)
            DEBUG_HOST="$2"
            shift 2
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

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

log_debug() {
    echo -e "${PURPLE}🐛 $1${NC}"
}

log_profile() {
    echo -e "${CYAN}📊 $1${NC}"
}

log_coverage() {
    echo -e "${YELLOW}📈 $1${NC}"
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${WHITE}🔍 $1${NC}"
    fi
}

# Build PHPUnit command with debugging
build_debug_command() {
    local phpunit_cmd="docker-compose run --rm"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_MODE=debug"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_CONFIG=client_host=$DEBUG_HOST client_port=$DEBUG_PORT idekey=docker"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_START_WITH_REQUEST=yes"
    phpunit_cmd="$phpunit_cmd test"
    phpunit_cmd="$phpunit_cmd php -d memory_limit=$MEMORY_LIMIT"
    phpunit_cmd="$phpunit_cmd phpunit --configuration phpunit.xml"
    
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Build PHPUnit command with profiling
build_profile_command() {
    local phpunit_cmd="docker-compose run --rm"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_MODE=profile"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_OUTPUT_DIR=$PROFILE_OUTPUT_DIR"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_PROFILER_ENABLE=1"
    phpunit_cmd="$phpunit_cmd test"
    phpunit_cmd="$phpunit_cmd php -d memory_limit=$MEMORY_LIMIT"
    phpunit_cmd="$phpunit_cmd phpunit --configuration phpunit.xml"
    
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Build PHPUnit command with coverage
build_coverage_command() {
    local phpunit_cmd="docker-compose run --rm"
    phpunit_cmd="$phpunit_cmd -e XDEBUG_MODE=coverage"
    phpunit_cmd="$phpunit_cmd test"
    phpunit_cmd="$phpunit_cmd php -d memory_limit=$MEMORY_LIMIT"
    phpunit_cmd="$phpunit_cmd phpunit --configuration phpunit.xml"
    phpunit_cmd="$phpunit_cmd --coverage-clover=coverage/clover.xml"
    phpunit_cmd="$phpunit_cmd --coverage-html=coverage/html"
    phpunit_cmd="$phpunit_cmd --coverage-text"
    
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Run tests in debug mode
run_debug_tests() {
    log_debug "Running tests in debug mode..."
    log_debug "Debug host: $DEBUG_HOST"
    log_debug "Debug port: $DEBUG_PORT"
    log_debug "IDE key: docker"
    echo ""
    
    local phpunit_cmd=$(build_debug_command)
    local start_time=$(date +%s)
    
    log_verbose "Command: $phpunit_cmd"
    echo ""
    
    # Execute tests
    if $phpunit_cmd; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Debug tests completed successfully in ${duration}s"
        log_debug "Check your IDE for debugging session"
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Debug tests failed after ${duration}s"
        return 1
    fi
}

# Run tests in profile mode
run_profile_tests() {
    log_profile "Running tests in profile mode..."
    log_profile "Profile output: $PROFILE_OUTPUT_DIR"
    echo ""
    
    local phpunit_cmd=$(build_profile_command)
    local start_time=$(date +%s)
    
    log_verbose "Command: $phpunit_cmd"
    echo ""
    
    # Execute tests
    if $phpunit_cmd; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Profile tests completed successfully in ${duration}s"
        log_profile "Profile files saved to: $PROFILE_OUTPUT_DIR"
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Profile tests failed after ${duration}s"
        return 1
    fi
}

# Run tests with coverage
run_coverage_tests() {
    log_coverage "Running tests with coverage analysis..."
    echo ""
    
    local phpunit_cmd=$(build_coverage_command)
    local start_time=$(date +%s)
    
    log_verbose "Command: $phpunit_cmd"
    echo ""
    
    # Execute tests
    if $phpunit_cmd; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Coverage tests completed successfully in ${duration}s"
        log_coverage "Coverage reports saved to: coverage/html/"
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Coverage tests failed after ${duration}s"
        return 1
    fi
}

# Debug a single test
debug_single_test() {
    local test_name="$1"
    if [ -z "$test_name" ]; then
        log_error "Please specify a test name"
        return 1
    fi
    
    log_debug "Debugging single test: $test_name"
    echo ""
    
    TEST_FILTER="$test_name"
    run_debug_tests
}

# Profile a single test
profile_single_test() {
    local test_name="$1"
    if [ -z "$test_name" ]; then
        log_error "Please specify a test name"
        return 1
    fi
    
    log_profile "Profiling single test: $test_name"
    echo ""
    
    TEST_FILTER="$test_name"
    run_profile_tests
}

# Analyze profile results
analyze_profile_results() {
    log_profile "Analyzing profile results..."
    echo ""
    
    if [ ! -d "$PROFILE_OUTPUT_DIR" ]; then
        log_error "Profile output directory not found: $PROFILE_OUTPUT_DIR"
        return 1
    fi
    
    local profile_files=$(find "$PROFILE_OUTPUT_DIR" -name "*.cachegrind" -type f)
    
    if [ -z "$profile_files" ]; then
        log_warning "No profile files found in: $PROFILE_OUTPUT_DIR"
        return 0
    fi
    
    log_info "Found $(echo "$profile_files" | wc -l) profile files:"
    echo ""
    
    for profile_file in $profile_files; do
        local file_name=$(basename "$profile_file")
        local file_size=$(du -h "$profile_file" | cut -f1)
        local file_time=$(stat -c %y "$profile_file" 2>/dev/null || stat -f %Sm "$profile_file" 2>/dev/null)
        
        echo -e "  ${GREEN}$file_name${NC} - $file_size - $file_time"
    done
    
    echo ""
    log_info "Profile analysis complete"
    log_info "Use tools like KCacheGrind or QCacheGrind to analyze the .cachegrind files"
}

# Show debugging information
show_debug_info() {
    echo -e "${BLUE}🐛 Debugging Information${NC}"
    echo "======================"
    echo ""
    echo -e "Debug Host: ${GREEN}$DEBUG_HOST${NC}"
    echo -e "Debug Port: ${GREEN}$DEBUG_PORT${NC}"
    echo -e "IDE Key: ${GREEN}docker${NC}"
    echo -e "Memory Limit: ${GREEN}$MEMORY_LIMIT${NC}"
    echo ""
    echo -e "${YELLOW}IDE Configuration:${NC}"
    echo "1. Set up xDebug in your IDE"
    echo "2. Configure host: $DEBUG_HOST"
    echo "3. Configure port: $DEBUG_PORT"
    echo "4. Configure IDE key: docker"
    echo "5. Start listening for debug connections"
    echo "6. Run tests with debug mode enabled"
    echo ""
    echo -e "${YELLOW}Debugging Tips:${NC}"
    echo "- Set breakpoints in your test files"
    echo "- Use step debugging to trace execution"
    echo "- Inspect variables and call stack"
    echo "- Use conditional breakpoints for complex scenarios"
}

# Show profiling information
show_profile_info() {
    echo -e "${BLUE}📊 Profiling Information${NC}"
    echo "======================="
    echo ""
    echo -e "Profile Output: ${GREEN}$PROFILE_OUTPUT_DIR${NC}"
    echo -e "Memory Limit: ${GREEN}$MEMORY_LIMIT${NC}"
    echo ""
    echo -e "${YELLOW}Profile Analysis Tools:${NC}"
    echo "1. KCacheGrind (Linux/KDE)"
    echo "2. QCacheGrind (Cross-platform)"
    echo "3. WebGrind (Web-based)"
    echo "4. PhpStorm built-in profiler"
    echo ""
    echo -e "${YELLOW}Profile Analysis Tips:${NC}"
    echo "- Look for functions with high execution time"
    echo "- Identify memory-intensive operations"
    echo "- Find bottlenecks in test execution"
    echo "- Optimize slow test methods"
}

# Show coverage information
show_coverage_info() {
    echo -e "${BLUE}📈 Coverage Information${NC}"
    echo "======================="
    echo ""
    echo -e "Coverage Output: ${GREEN}coverage/html/${NC}"
    echo -e "Clover XML: ${GREEN}coverage/clover.xml${NC}"
    echo -e "Memory Limit: ${GREEN}$MEMORY_LIMIT${NC}"
    echo ""
    echo -e "${YELLOW}Coverage Analysis:${NC}"
    echo "1. Open coverage/html/index.html in browser"
    echo "2. Review line-by-line coverage"
    echo "3. Identify uncovered code paths"
    echo "4. Add tests for uncovered areas"
    echo ""
    echo -e "${YELLOW}Coverage Tips:${NC}"
    echo "- Aim for high coverage on critical paths"
    echo "- Focus on business logic coverage"
    echo "- Test edge cases and error conditions"
    echo "- Use coverage to guide test development"
}

# Setup debugging environment
setup_debug_environment() {
    log_debug "Setting up debugging environment..."
    
    # Create debug output directory
    mkdir -p "$PROFILE_OUTPUT_DIR"
    
    # Check if xDebug is available
    if docker-compose run --rm test php -m | grep -q xdebug; then
        log_success "xDebug extension is available"
    else
        log_error "xDebug extension is not available"
        return 1
    fi
    
    # Test debug connection
    log_info "Testing debug connection..."
    if docker-compose run --rm test php -r "
        if (function_exists('xdebug_info')) {
            echo 'xDebug is properly configured' . PHP_EOL;
        } else {
            echo 'xDebug is not properly configured' . PHP_EOL;
            exit(1);
        }
    "; then
        log_success "Debug environment setup complete"
    else
        log_error "Debug environment setup failed"
        return 1
    fi
}

# Setup profiling environment
setup_profile_environment() {
    log_profile "Setting up profiling environment..."
    
    # Create profile output directory
    mkdir -p "$PROFILE_OUTPUT_DIR"
    
    # Check if xDebug is available
    if docker-compose run --rm test php -m | grep -q xdebug; then
        log_success "xDebug extension is available"
    else
        log_error "xDebug extension is not available"
        return 1
    fi
    
    # Test profile configuration
    log_info "Testing profile configuration..."
    if docker-compose run --rm test php -r "
        if (ini_get('xdebug.mode') && strpos(ini_get('xdebug.mode'), 'profile') !== false) {
            echo 'Profile mode is properly configured' . PHP_EOL;
        } else {
            echo 'Profile mode is not properly configured' . PHP_EOL;
            exit(1);
        }
    "; then
        log_success "Profile environment setup complete"
    else
        log_error "Profile environment setup failed"
        return 1
    fi
}

# Cleanup debugging files
cleanup_debug_files() {
    log_debug "Cleaning up debugging files..."
    
    # Remove debug log files
    if [ -d "$PROFILE_OUTPUT_DIR" ]; then
        find "$PROFILE_OUTPUT_DIR" -name "*.log" -type f -delete 2>/dev/null || true
        log_success "Debug log files cleaned up"
    fi
    
    log_success "Debug cleanup complete"
}

# Cleanup profiling files
cleanup_profile_files() {
    log_profile "Cleaning up profiling files..."
    
    # Remove profile files
    if [ -d "$PROFILE_OUTPUT_DIR" ]; then
        find "$PROFILE_OUTPUT_DIR" -name "*.cachegrind" -type f -delete 2>/dev/null || true
        log_success "Profile files cleaned up"
    fi
    
    log_success "Profile cleanup complete"
}

# Main execution
main() {
    case "$COMMAND" in
        debug)
            run_debug_tests
            ;;
        profile)
            run_profile_tests
            ;;
        coverage)
            run_coverage_tests
            ;;
        debug-single)
            debug_single_test "$1"
            ;;
        profile-single)
            profile_single_test "$1"
            ;;
        analyze-profile)
            analyze_profile_results
            ;;
        debug-info)
            show_debug_info
            ;;
        profile-info)
            show_profile_info
            ;;
        coverage-info)
            show_coverage_info
            ;;
        setup-debug)
            setup_debug_environment
            ;;
        setup-profile)
            setup_profile_environment
            ;;
        cleanup-debug)
            cleanup_debug_files
            ;;
        cleanup-profile)
            cleanup_profile_files
            ;;
        help)
            show_usage
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

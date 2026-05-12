#!/bin/bash

# Interactive Test Execution Script
# Provides interactive test execution with debugging and development support

set -e

# Default values
INTERACTIVE="${INTERACTIVE:-true}"
DEBUG_MODE="${DEBUG_MODE:-false}"
PROFILE_MODE="${PROFILE_MODE:-false}"
COVERAGE_MODE="${COVERAGE_MODE:-false}"
VERBOSE="${VERBOSE:-false}"
TEST_FILTER="${TEST_FILTER:-}"
TEST_GROUP="${TEST_GROUP:-}"
MEMORY_LIMIT="${MEMORY_LIMIT:-1G}"

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
    echo -e "${BLUE}Interactive Test Execution Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --debug                  Enable xDebug debugging mode"
    echo "  --profile                Enable xDebug profiling mode"
    echo "  --coverage               Enable code coverage mode"
    echo "  --test-filter FILTER     Test filter pattern"
    echo "  --test-group GROUP       Test group to run"
    echo "  --memory-limit LIMIT     PHP memory limit (default: 1G)"
    echo "  --verbose                Enable verbose output"
    echo "  --non-interactive        Disable interactive mode"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Interactive Commands:${NC}"
    echo "  run                      Run tests with current settings"
    echo "  debug                    Toggle debug mode"
    echo "  profile                  Toggle profile mode"
    echo "  coverage                 Toggle coverage mode"
    echo "  filter [pattern]         Set test filter pattern"
    echo "  group [name]             Set test group"
    echo "  memory [limit]           Set memory limit"
    echo "  verbose                  Toggle verbose output"
    echo "  status                   Show current settings"
    echo "  help                     Show this help"
    echo "  quit                     Exit the script"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Interactive mode"
    echo "  $0 --debug --test-filter ThemeSetup  # Debug specific tests"
    echo "  $0 --coverage --verbose              # Coverage with verbose output"
    echo "  $0 --non-interactive --profile       # Profile mode non-interactive"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --debug)
            DEBUG_MODE="true"
            shift
            ;;
        --profile)
            PROFILE_MODE="true"
            shift
            ;;
        --coverage)
            COVERAGE_MODE="true"
            shift
            ;;
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --test-group)
            TEST_GROUP="$2"
            shift 2
            ;;
        --memory-limit)
            MEMORY_LIMIT="$2"
            shift 2
            ;;
        --verbose)
            VERBOSE="true"
            shift
            ;;
        --non-interactive)
            INTERACTIVE="false"
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

# Display current settings
display_status() {
    echo ""
    echo -e "${WHITE}📋 Current Settings${NC}"
    echo "=================="
    echo -e "Debug Mode: ${GREEN}$DEBUG_MODE${NC}"
    echo -e "Profile Mode: ${GREEN}$PROFILE_MODE${NC}"
    echo -e "Coverage Mode: ${GREEN}$COVERAGE_MODE${NC}"
    echo -e "Test Filter: ${GREEN}${TEST_FILTER:-'none'}${NC}"
    echo -e "Test Group: ${GREEN}${TEST_GROUP:-'none'}${NC}"
    echo -e "Memory Limit: ${GREEN}$MEMORY_LIMIT${NC}"
    echo -e "Verbose: ${GREEN}$VERBOSE${NC}"
    echo ""
}

# Build PHPUnit command
build_phpunit_command() {
    local phpunit_cmd="docker compose run --rm test"
    
    # Set environment variables
    local env_vars=""
    if [ "$DEBUG_MODE" = true ]; then
        env_vars="$env_vars -e XDEBUG_MODE=debug"
        env_vars="$env_vars -e XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003 idekey=docker"
    fi
    
    if [ "$PROFILE_MODE" = true ]; then
        env_vars="$env_vars -e XDEBUG_MODE=profile"
        env_vars="$env_vars -e XDEBUG_OUTPUT_DIR=/tmp/xdebug"
    fi
    
    if [ "$COVERAGE_MODE" = true ]; then
        env_vars="$env_vars -e XDEBUG_MODE=coverage"
    fi
    
    # Add environment variables to command
    if [ -n "$env_vars" ]; then
        phpunit_cmd="$phpunit_cmd $env_vars"
    fi
    
    # Add PHP command with memory limit
    phpunit_cmd="$phpunit_cmd php -d memory_limit=$MEMORY_LIMIT"
    
    # Add PHPUnit command
    phpunit_cmd="$phpunit_cmd phpunit --configuration phpunit.xml"
    
    # Add test filter
    if [ -n "$TEST_FILTER" ]; then
        phpunit_cmd="$phpunit_cmd --filter $TEST_FILTER"
    fi
    
    # Add test group
    if [ -n "$TEST_GROUP" ]; then
        phpunit_cmd="$phpunit_cmd --group $TEST_GROUP"
    fi
    
    # Add coverage options
    if [ "$COVERAGE_MODE" = true ]; then
        phpunit_cmd="$phpunit_cmd --coverage-clover=coverage/clover.xml"
        phpunit_cmd="$phpunit_cmd --coverage-html=coverage/html"
        phpunit_cmd="$phpunit_cmd --coverage-text"
    fi
    
    # Add verbose output
    if [ "$VERBOSE" = true ]; then
        phpunit_cmd="$phpunit_cmd --verbose"
    fi
    
    echo "$phpunit_cmd"
}

# Execute tests
execute_tests() {
    local phpunit_cmd=$(build_phpunit_command)
    local start_time=$(date +%s)
    
    echo ""
    log_info "Executing tests..."
    log_debug "Command: $phpunit_cmd"
    echo ""
    
    # Execute tests
    if $phpunit_cmd; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_success "Tests completed successfully in ${duration}s"
        
        # Show additional information based on mode
        if [ "$DEBUG_MODE" = true ]; then
            log_debug "Debug mode was active - check your IDE for debugging session"
        fi
        
        if [ "$PROFILE_MODE" = true ]; then
            log_profile "Profile mode was active - check /tmp/xdebug for profile files"
        fi
        
        if [ "$COVERAGE_MODE" = true ]; then
            log_coverage "Coverage reports generated in coverage/html/"
        fi
        
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log_error "Tests failed after ${duration}s"
        return 1
    fi
}

# Interactive command processing
process_interactive_command() {
    local cmd="$1"
    local arg="$2"
    
    case "$cmd" in
        run)
            execute_tests
            ;;
        debug)
            if [ "$DEBUG_MODE" = true ]; then
                DEBUG_MODE="false"
                log_info "Debug mode disabled"
            else
                DEBUG_MODE="true"
                PROFILE_MODE="false"  # Disable profile when enabling debug
                log_debug "Debug mode enabled"
            fi
            ;;
        profile)
            if [ "$PROFILE_MODE" = true ]; then
                PROFILE_MODE="false"
                log_info "Profile mode disabled"
            else
                PROFILE_MODE="true"
                DEBUG_MODE="false"  # Disable debug when enabling profile
                log_profile "Profile mode enabled"
            fi
            ;;
        coverage)
            if [ "$COVERAGE_MODE" = true ]; then
                COVERAGE_MODE="false"
                log_info "Coverage mode disabled"
            else
                COVERAGE_MODE="true"
                log_coverage "Coverage mode enabled"
            fi
            ;;
        filter)
            if [ -n "$arg" ]; then
                TEST_FILTER="$arg"
                log_info "Test filter set to: $TEST_FILTER"
            else
                TEST_FILTER=""
                log_info "Test filter cleared"
            fi
            ;;
        group)
            if [ -n "$arg" ]; then
                TEST_GROUP="$arg"
                log_info "Test group set to: $TEST_GROUP"
            else
                TEST_GROUP=""
                log_info "Test group cleared"
            fi
            ;;
        memory)
            if [ -n "$arg" ]; then
                MEMORY_LIMIT="$arg"
                log_info "Memory limit set to: $MEMORY_LIMIT"
            else
                log_warning "Please specify memory limit (e.g., 512M, 1G)"
            fi
            ;;
        verbose)
            if [ "$VERBOSE" = true ]; then
                VERBOSE="false"
                log_info "Verbose output disabled"
            else
                VERBOSE="true"
                log_info "Verbose output enabled"
            fi
            ;;
        status)
            display_status
            ;;
        help)
            show_usage
            ;;
        quit|exit)
            log_info "Goodbye!"
            exit 0
            ;;
        *)
            log_error "Unknown command: $cmd"
            log_info "Type 'help' for available commands"
            ;;
    esac
}

# Interactive mode
run_interactive_mode() {
    echo -e "${BLUE}🧪 Interactive Test Execution${NC}"
    echo ""
    log_info "Welcome to the interactive test execution environment!"
    log_info "Type 'help' for available commands or 'quit' to exit"
    echo ""
    
    display_status
    
    while true; do
        echo -n -e "${CYAN}test> ${NC}"
        read -r input
        
        if [ -z "$input" ]; then
            continue
        fi
        
        # Parse input
        local cmd=$(echo "$input" | cut -d' ' -f1)
        local arg=$(echo "$input" | cut -d' ' -f2-)
        
        process_interactive_command "$cmd" "$arg"
    done
}

# Non-interactive mode
run_non_interactive_mode() {
    echo -e "${BLUE}🧪 Test Execution${NC}"
    echo ""
    display_status
    execute_tests
}

# Main execution
main() {
    if [ "$INTERACTIVE" = true ]; then
        run_interactive_mode
    else
        run_non_interactive_mode
    fi
}

# Run main function
main

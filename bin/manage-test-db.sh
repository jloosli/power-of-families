#!/bin/bash

# Test Database Management Script
# Comprehensive script for managing test database operations

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Display usage information
show_usage() {
    echo -e "${BLUE}Test Database Management Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  setup       Set up a fresh test database"
    echo "  cleanup     Clean up test database and files"
    echo "  reset       Completely reset test database and environment"
    echo "  verify      Verify database isolation"
    echo "  status      Show current database status"
    echo "  help        Show this help message"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --force     Force operation without confirmation"
    echo "  --verbose   Enable verbose output"
    echo "  --quiet     Suppress output except errors"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 setup                    # Set up test database"
    echo "  $0 cleanup --force          # Clean up without confirmation"
    echo "  $0 reset --verbose          # Reset with verbose output"
    echo "  $0 verify                   # Verify isolation"
    echo "  $0 status                   # Show status"
}

# Parse command line arguments
COMMAND=""
FORCE=false
VERBOSE=false
QUIET=false

while [[ $# -gt 0 ]]; do
    case $1 in
        setup|cleanup|reset|verify|status|help)
            COMMAND="$1"
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --quiet)
            QUIET=true
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

# If no command provided, show usage
if [ -z "$COMMAND" ]; then
    show_usage
    exit 1
fi

# Logging functions
log_info() {
    if [ "$QUIET" = false ]; then
        echo -e "${BLUE}ℹ️  $1${NC}"
    fi
}

log_success() {
    if [ "$QUIET" = false ]; then
        echo -e "${GREEN}✅ $1${NC}"
    fi
}

log_warning() {
    if [ "$QUIET" = false ]; then
        echo -e "${YELLOW}⚠️  $1${NC}"
    fi
}

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

log_verbose() {
    if [ "$VERBOSE" = true ] && [ "$QUIET" = false ]; then
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Confirmation function
confirm() {
    if [ "$FORCE" = true ]; then
        return 0
    fi
    
    read -p "$1 (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Execute command with error handling
execute_command() {
    local cmd="$1"
    local description="$2"
    
    log_verbose "Executing: $cmd"
    
    if eval "$cmd"; then
        log_success "$description"
        return 0
    else
        log_error "Failed: $description"
        return 1
    fi
}

# Main command handlers
handle_setup() {
    log_info "Setting up test database..."
    
    if [ -f "$SCRIPT_DIR/setup-test-db.sh" ]; then
        execute_command "bash $SCRIPT_DIR/setup-test-db.sh" "Test database setup"
    else
        log_error "Setup script not found: $SCRIPT_DIR/setup-test-db.sh"
        exit 1
    fi
}

handle_cleanup() {
    log_info "Cleaning up test database..."
    
    if confirm "Are you sure you want to clean up the test database? This will remove all test data."; then
        if [ -f "$SCRIPT_DIR/cleanup-test-db.sh" ]; then
            execute_command "bash $SCRIPT_DIR/cleanup-test-db.sh" "Test database cleanup"
        else
            log_error "Cleanup script not found: $SCRIPT_DIR/cleanup-test-db.sh"
            exit 1
        fi
    else
        log_info "Cleanup cancelled"
    fi
}

handle_reset() {
    log_info "Resetting test database and environment..."
    
    if confirm "Are you sure you want to reset the test database? This will completely rebuild the test environment."; then
        if [ -f "$SCRIPT_DIR/reset-test-db.sh" ]; then
            execute_command "bash $SCRIPT_DIR/reset-test-db.sh" "Test database reset"
        else
            log_error "Reset script not found: $SCRIPT_DIR/reset-test-db.sh"
            exit 1
        fi
    else
        log_info "Reset cancelled"
    fi
}

handle_verify() {
    log_info "Verifying database isolation..."
    
    if [ -f "$SCRIPT_DIR/verify-db-isolation.sh" ]; then
        execute_command "bash $SCRIPT_DIR/verify-db-isolation.sh" "Database isolation verification"
    else
        log_error "Verification script not found: $SCRIPT_DIR/verify-db-isolation.sh"
        exit 1
    fi
}

handle_status() {
    log_info "Checking test database status..."
    
    # Check if database is running
    if docker-compose ps db | grep -q "Up"; then
        log_success "Database container is running"
    else
        log_warning "Database container is not running"
    fi
    
    # Check if test database exists
    TEST_DB_NAME="${TEST_DB_NAME:-wordpress_tests}"
    if mysql -h"${TEST_DB_HOST:-db}" -u"${TEST_DB_USER:-root}" -p"${TEST_DB_PASSWORD:-password}" --ssl=0 -e "USE \`$TEST_DB_NAME\`;" 2>/dev/null; then
        log_success "Test database ($TEST_DB_NAME) exists"
        
        # Count tables
        TABLE_COUNT=$(mysql -h"${TEST_DB_HOST:-db}" -u"${TEST_DB_USER:-root}" -p"${TEST_DB_PASSWORD:-password}" --ssl=0 -e "USE \`$TEST_DB_NAME\`; SHOW TABLES;" 2>/dev/null | grep -v "Tables_in" | wc -l)
        log_info "Test database has $TABLE_COUNT tables"
    else
        log_warning "Test database ($TEST_DB_NAME) does not exist"
    fi
    
    # Check if test container exists
    if docker-compose ps test | grep -q "Up"; then
        log_success "Test container is running"
    elif docker images | grep -q "power-of-families_test"; then
        log_info "Test container image exists but is not running"
    else
        log_warning "Test container image does not exist"
    fi
    
    # Check for test files
    if [ -d "coverage" ] && [ "$(ls -A coverage 2>/dev/null)" ]; then
        log_info "Coverage reports found"
    else
        log_info "No coverage reports found"
    fi
    
    if [ -d "test-reports" ] && [ "$(ls -A test-reports 2>/dev/null)" ]; then
        log_info "Test reports found"
    else
        log_info "No test reports found"
    fi
}

# Main execution
case $COMMAND in
    setup)
        handle_setup
        ;;
    cleanup)
        handle_cleanup
        ;;
    reset)
        handle_reset
        ;;
    verify)
        handle_verify
        ;;
    status)
        handle_status
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

log_success "Command completed successfully"

#!/bin/bash

# Test Environment Setup and Teardown Script
# Manages test environment lifecycle for development

set -e

# Default values
VERBOSE="${VERBOSE:-false}"
FORCE="${FORCE:-false}"
CLEAN_DATABASE="${CLEAN_DATABASE:-false}"
CLEAN_COVERAGE="${CLEAN_COVERAGE:-false}"
CLEAN_REPORTS="${CLEAN_REPORTS:-false}"
CLEAN_ALL="${CLEAN_ALL:-false}"

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
    echo -e "${BLUE}Test Environment Setup and Teardown Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  setup                    Setup test environment"
    echo "  teardown                 Teardown test environment"
    echo "  reset                    Reset test environment"
    echo "  status                   Show environment status"
    echo "  validate                 Validate environment setup"
    echo "  clean-database           Clean test database"
    echo "  clean-coverage           Clean coverage files"
    echo "  clean-reports            Clean test reports"
    echo "  clean-all                Clean all test artifacts"
    echo "  backup-env               Backup current environment"
    echo "  restore-env              Restore environment from backup"
    echo "  help                     Show this help message"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --force                  Force operations without confirmation"
    echo "  --verbose                Enable verbose output"
    echo "  --clean-database         Clean database during setup"
    echo "  --clean-coverage         Clean coverage during setup"
    echo "  --clean-reports          Clean reports during setup"
    echo "  --clean-all              Clean all artifacts during setup"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 setup                              # Setup test environment"
    echo "  $0 teardown --force                   # Force teardown"
    echo "  $0 reset --clean-all                  # Reset with full cleanup"
    echo "  $0 status                             # Show environment status"
    echo "  $0 validate                           # Validate setup"
}

# Parse command line arguments
COMMAND="${1:-help}"
shift

while [[ $# -gt 0 ]]; do
    case $1 in
        --force)
            FORCE="true"
            shift
            ;;
        --verbose)
            VERBOSE="true"
            shift
            ;;
        --clean-database)
            CLEAN_DATABASE="true"
            shift
            ;;
        --clean-coverage)
            CLEAN_COVERAGE="true"
            shift
            ;;
        --clean-reports)
            CLEAN_REPORTS="true"
            shift
            ;;
        --clean-all)
            CLEAN_ALL="true"
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

# Confirm action
confirm_action() {
    local message="$1"
    if [ "$FORCE" = true ]; then
        return 0
    fi
    
    echo -e "${YELLOW}$message${NC}"
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Check if Docker is running
check_docker() {
    log_verbose "Checking Docker status..."
    
    if ! docker info >/dev/null 2>&1; then
        log_error "Docker is not running"
        return 1
    fi
    
    log_success "Docker is running"
    return 0
}

# Check if docker-compose is available
check_docker_compose() {
    log_verbose "Checking docker-compose availability..."
    
    if ! command -v docker-compose >/dev/null 2>&1; then
        log_error "docker-compose is not available"
        return 1
    fi
    
    log_success "docker-compose is available"
    return 0
}

# Check if test container exists
check_test_container() {
    log_verbose "Checking test container..."
    
    if docker-compose ps test >/dev/null 2>&1; then
        log_success "Test container is configured"
        return 0
    else
        log_warning "Test container is not configured"
        return 1
    fi
}

# Check if test database is accessible
check_test_database() {
    log_verbose "Checking test database..."
    
    if docker-compose exec -T db mysqladmin ping -h"localhost" -u"root" -p"password" --silent 2>/dev/null; then
        log_success "Test database is accessible"
        return 0
    else
        log_warning "Test database is not accessible"
        return 1
    fi
}

# Check if test directories exist
check_test_directories() {
    log_verbose "Checking test directories..."
    
    local directories=("coverage" "test-reports" "xdebug-logs" "xdebug-profiles")
    local all_exist=true
    
    for dir in "${directories[@]}"; do
        if [ -d "$dir" ]; then
            log_verbose "Directory exists: $dir"
        else
            log_warning "Directory missing: $dir"
            all_exist=false
        fi
    done
    
    if [ "$all_exist" = true ]; then
        log_success "All test directories exist"
        return 0
    else
        log_warning "Some test directories are missing"
        return 1
    fi
}

# Check if test files exist
check_test_files() {
    log_verbose "Checking test files..."
    
    local test_files=("wp-content/themes/power-of-families/tests/test_sample.php" "wp-content/themes/power-of-families/tests/test_ThemeSetup.php")
    local all_exist=true
    
    for file in "${test_files[@]}"; do
        if [ -f "$file" ]; then
            log_verbose "Test file exists: $file"
        else
            log_warning "Test file missing: $file"
            all_exist=false
        fi
    done
    
    if [ "$all_exist" = true ]; then
        log_success "Test files exist"
        return 0
    else
        log_warning "Some test files are missing"
        return 1
    fi
}

# Setup test environment
setup_test_environment() {
    log_step "Setting up test environment..."
    
    # Check prerequisites
    if ! check_docker; then
        return 1
    fi
    
    if ! check_docker_compose; then
        return 1
    fi
    
    # Create test directories
    log_info "Creating test directories..."
    mkdir -p coverage/html
    mkdir -p test-reports
    mkdir -p xdebug-logs
    mkdir -p xdebug-profiles
    
    log_success "Test directories created"
    
    # Build test container
    log_info "Building test container..."
    if docker-compose build test; then
        log_success "Test container built"
    else
        log_error "Failed to build test container"
        return 1
    fi
    
    # Setup test database
    log_info "Setting up test database..."
    if bin/setup-test-db.sh; then
        log_success "Test database setup complete"
    else
        log_error "Failed to setup test database"
        return 1
    fi
    
    # Initialize coverage thresholds
    log_info "Initializing coverage thresholds..."
    if bin/manage-coverage-thresholds.sh init; then
        log_success "Coverage thresholds initialized"
    else
        log_warning "Failed to initialize coverage thresholds"
    fi
    
    # Validate setup
    log_info "Validating setup..."
    if validate_environment; then
        log_success "Test environment setup complete"
    else
        log_warning "Test environment setup completed with warnings"
    fi
}

# Teardown test environment
teardown_test_environment() {
    log_step "Teardown test environment..."
    
    if ! confirm_action "This will stop test containers and clean up test data. Continue?"; then
        log_info "Teardown cancelled"
        return 0
    fi
    
    # Stop test containers
    log_info "Stopping test containers..."
    docker-compose stop test 2>/dev/null || true
    docker-compose stop db 2>/dev/null || true
    
    log_success "Test containers stopped"
    
    # Clean up test database
    log_info "Cleaning up test database..."
    if bin/cleanup-test-db.sh; then
        log_success "Test database cleaned up"
    else
        log_warning "Failed to clean up test database"
    fi
    
    # Clean up test artifacts
    log_info "Cleaning up test artifacts..."
    clean_all_artifacts
    
    log_success "Test environment teardown complete"
}

# Reset test environment
reset_test_environment() {
    log_step "Resetting test environment..."
    
    if ! confirm_action "This will reset the entire test environment. Continue?"; then
        log_info "Reset cancelled"
        return 0
    fi
    
    # Teardown first
    teardown_test_environment
    
    # Setup again
    setup_test_environment
    
    log_success "Test environment reset complete"
}

# Show environment status
show_environment_status() {
    echo -e "${BLUE}📊 Test Environment Status${NC}"
    echo "=========================="
    echo ""
    
    # Docker status
    echo -e "${YELLOW}Docker Status:${NC}"
    if check_docker; then
        echo -e "  Status: ${GREEN}Running${NC}"
    else
        echo -e "  Status: ${RED}Not Running${NC}"
    fi
    
    # Container status
    echo -e "${YELLOW}Container Status:${NC}"
    if check_test_container; then
        echo -e "  Test Container: ${GREEN}Configured${NC}"
    else
        echo -e "  Test Container: ${RED}Not Configured${NC}"
    fi
    
    # Database status
    echo -e "${YELLOW}Database Status:${NC}"
    if check_test_database; then
        echo -e "  Test Database: ${GREEN}Accessible${NC}"
    else
        echo -e "  Test Database: ${RED}Not Accessible${NC}"
    fi
    
    # Directory status
    echo -e "${YELLOW}Directory Status:${NC}"
    if check_test_directories; then
        echo -e "  Test Directories: ${GREEN}Complete${NC}"
    else
        echo -e "  Test Directories: ${YELLOW}Partial${NC}"
    fi
    
    # File status
    echo -e "${YELLOW}File Status:${NC}"
    if check_test_files; then
        echo -e "  Test Files: ${GREEN}Complete${NC}"
    else
        echo -e "  Test Files: ${YELLOW}Partial${NC}"
    fi
    
    echo ""
}

# Validate environment setup
validate_environment() {
    log_step "Validating environment setup..."
    
    local validation_passed=true
    
    # Check Docker
    if ! check_docker; then
        validation_passed=false
    fi
    
    # Check docker-compose
    if ! check_docker_compose; then
        validation_passed=false
    fi
    
    # Check test container
    if ! check_test_container; then
        validation_passed=false
    fi
    
    # Check test database
    if ! check_test_database; then
        validation_passed=false
    fi
    
    # Check test directories
    if ! check_test_directories; then
        validation_passed=false
    fi
    
    # Check test files
    if ! check_test_files; then
        validation_passed=false
    fi
    
    if [ "$validation_passed" = true ]; then
        log_success "Environment validation passed"
        return 0
    else
        log_error "Environment validation failed"
        return 1
    fi
}

# Clean test database
clean_test_database() {
    log_step "Cleaning test database..."
    
    if ! confirm_action "This will clean the test database. Continue?"; then
        log_info "Database cleanup cancelled"
        return 0
    fi
    
    if bin/cleanup-test-db.sh; then
        log_success "Test database cleaned"
    else
        log_error "Failed to clean test database"
        return 1
    fi
}

# Clean coverage files
clean_coverage_files() {
    log_step "Cleaning coverage files..."
    
    if [ -d "coverage" ]; then
        rm -rf coverage/*
        log_success "Coverage files cleaned"
    else
        log_info "No coverage directory found"
    fi
}

# Clean test reports
clean_test_reports() {
    log_step "Cleaning test reports..."
    
    if [ -d "test-reports" ]; then
        rm -rf test-reports/*
        log_success "Test reports cleaned"
    else
        log_info "No test-reports directory found"
    fi
}

# Clean all artifacts
clean_all_artifacts() {
    log_step "Cleaning all test artifacts..."
    
    # Clean coverage
    clean_coverage_files
    
    # Clean reports
    clean_test_reports
    
    # Clean xDebug logs
    if [ -d "xdebug-logs" ]; then
        rm -rf xdebug-logs/*
        log_success "xDebug logs cleaned"
    fi
    
    # Clean xDebug profiles
    if [ -d "xdebug-profiles" ]; then
        rm -rf xdebug-profiles/*
        log_success "xDebug profiles cleaned"
    fi
    
    log_success "All test artifacts cleaned"
}

# Backup environment
backup_environment() {
    log_step "Backing up test environment..."
    
    local backup_dir="test-env-backup-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$backup_dir"
    
    # Backup coverage
    if [ -d "coverage" ]; then
        cp -r coverage "$backup_dir/"
        log_verbose "Coverage backed up"
    fi
    
    # Backup reports
    if [ -d "test-reports" ]; then
        cp -r test-reports "$backup_dir/"
        log_verbose "Reports backed up"
    fi
    
    # Backup thresholds
    if [ -f "coverage-thresholds.json" ]; then
        cp coverage-thresholds.json "$backup_dir/"
        log_verbose "Thresholds backed up"
    fi
    
    # Backup quality gates
    if [ -f "quality-gates.json" ]; then
        cp quality-gates.json "$backup_dir/"
        log_verbose "Quality gates backed up"
    fi
    
    log_success "Environment backed up to: $backup_dir"
}

# Restore environment
restore_environment() {
    local backup_dir="$1"
    
    if [ -z "$backup_dir" ]; then
        log_error "Please specify backup directory"
        return 1
    fi
    
    if [ ! -d "$backup_dir" ]; then
        log_error "Backup directory not found: $backup_dir"
        return 1
    fi
    
    log_step "Restoring test environment from: $backup_dir"
    
    if ! confirm_action "This will restore the test environment from backup. Continue?"; then
        log_info "Restore cancelled"
        return 0
    fi
    
    # Restore coverage
    if [ -d "$backup_dir/coverage" ]; then
        cp -r "$backup_dir/coverage" .
        log_verbose "Coverage restored"
    fi
    
    # Restore reports
    if [ -d "$backup_dir/test-reports" ]; then
        cp -r "$backup_dir/test-reports" .
        log_verbose "Reports restored"
    fi
    
    # Restore thresholds
    if [ -f "$backup_dir/coverage-thresholds.json" ]; then
        cp "$backup_dir/coverage-thresholds.json" .
        log_verbose "Thresholds restored"
    fi
    
    # Restore quality gates
    if [ -f "$backup_dir/quality-gates.json" ]; then
        cp "$backup_dir/quality-gates.json" .
        log_verbose "Quality gates restored"
    fi
    
    log_success "Environment restored from: $backup_dir"
}

# Main execution
main() {
    case "$COMMAND" in
        setup)
            setup_test_environment
            ;;
        teardown)
            teardown_test_environment
            ;;
        reset)
            reset_test_environment
            ;;
        status)
            show_environment_status
            ;;
        validate)
            validate_environment
            ;;
        clean-database)
            clean_test_database
            ;;
        clean-coverage)
            clean_coverage_files
            ;;
        clean-reports)
            clean_test_reports
            ;;
        clean-all)
            clean_all_artifacts
            ;;
        backup-env)
            backup_environment
            ;;
        restore-env)
            restore_environment "$1"
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

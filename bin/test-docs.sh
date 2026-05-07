#!/bin/bash

# Test Documentation and Help Script
# Provides comprehensive documentation and help for test development

set -e

# Default values
VERBOSE="${VERBOSE:-false}"
OUTPUT_FORMAT="${OUTPUT_FORMAT:-text}"
OUTPUT_FILE="${OUTPUT_FILE:-}"

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
    echo -e "${BLUE}Test Documentation and Help Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  help                     Show this help message"
    echo "  quick-start              Show quick start guide"
    echo "  commands                 List all available test commands"
    echo "  examples                 Show usage examples"
    echo "  best-practices           Show testing best practices"
    echo "  troubleshooting          Show troubleshooting guide"
    echo "  api-reference            Show API reference"
    echo "  configuration            Show configuration options"
    echo "  environment              Show environment setup"
    echo "  debugging                Show debugging guide"
    echo "  profiling                Show profiling guide"
    echo "  coverage                 Show coverage guide"
    echo "  performance              Show performance guide"
    echo "  generate-docs            Generate comprehensive documentation"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --output-format FORMAT   Output format (text, html, markdown)"
    echo "  --output-file FILE       Output file path"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 quick-start                           # Show quick start guide"
    echo "  $0 commands --output-format html        # List commands in HTML"
    echo "  $0 generate-docs --output-file docs.html # Generate full docs"
}

# Parse command line arguments
COMMAND="${1:-help}"
shift

while [[ $# -gt 0 ]]; do
    case $1 in
        --output-format)
            OUTPUT_FORMAT="$2"
            shift 2
            ;;
        --output-file)
            OUTPUT_FILE="$2"
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

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Show quick start guide
show_quick_start() {
    echo -e "${BLUE}🚀 Quick Start Guide${NC}"
    echo "=================="
    echo ""
    echo -e "${YELLOW}1. Setup Test Environment${NC}"
    echo "   bin/test-env-manager.sh setup"
    echo ""
    echo -e "${YELLOW}2. Run Tests${NC}"
    echo "   # Quick test run"
    echo "   npm test"
    echo ""
    echo "   # Full test run with coverage"
    echo "   npm run test:coverage"
    echo ""
    echo -e "${YELLOW}3. Debug Tests${NC}"
    echo "   bin/test-debug-profile.sh debug"
    echo ""
    echo -e "${YELLOW}4. Profile Tests${NC}"
    echo "   bin/test-debug-profile.sh profile"
    echo ""
    echo -e "${YELLOW}5. Monitor Performance${NC}"
    echo "   bin/test-performance-monitor.sh monitor"
    echo ""
    echo -e "${YELLOW}6. Create New Tests${NC}"
    echo "   bin/test-dev-helper.sh create-test MyNewTest"
    echo ""
    echo -e "${GREEN}That's it! You're ready to start testing.${NC}"
    echo ""
}

# List all available test commands
list_commands() {
    echo -e "${BLUE}📋 Available Test Commands${NC}"
    echo "========================="
    echo ""
    
    echo -e "${YELLOW}Test Execution Commands:${NC}"
    echo "  npm test                    # Quick test execution"
    echo "  npm run test:quick          # Fast test execution"
    echo "  npm run test:full           # Full test execution with reporting"
    echo "  npm run test:ci             # CI/CD optimized execution"
    echo "  npm run test:coverage       # Test execution with coverage"
    echo "  npm run test:thresholds     # Check coverage thresholds"
    echo "  npm run test:quality-gates  # Validate quality gates"
    echo "  npm run test:reports        # Generate reports only"
    echo ""
    
    echo -e "${YELLOW}Interactive Commands:${NC}"
    echo "  bin/test-interactive.sh     # Interactive test execution"
    echo "  bin/test-debug-profile.sh   # Debug and profile tests"
    echo "  bin/test-performance-monitor.sh # Monitor test performance"
    echo ""
    
    echo -e "${YELLOW}Development Commands:${NC}"
    echo "  bin/test-dev-helper.sh      # Test development utilities"
    echo "  bin/test-env-manager.sh     # Environment management"
    echo "  bin/manage-coverage-thresholds.sh # Coverage threshold management"
    echo ""
    
    echo -e "${YELLOW}Database Commands:${NC}"
    echo "  bin/setup-test-db.sh        # Setup test database"
    echo "  bin/cleanup-test-db.sh      # Cleanup test database"
    echo "  bin/reset-test-db.sh         # Reset test database"
    echo "  bin/verify-db-isolation.sh   # Verify database isolation"
    echo "  bin/manage-test-db.sh       # Unified database management"
    echo ""
    
    echo -e "${YELLOW}Reporting Commands:${NC}"
    echo "  bin/generate-junit-report.sh      # Generate JUnit reports"
    echo "  bin/generate-html-coverage.sh     # Generate HTML coverage"
    echo "  bin/generate-coverage-dashboard.sh # Generate coverage dashboard"
    echo "  bin/ci-coverage-integration.sh    # CI/CD integration"
    echo ""
    
    echo -e "${YELLOW}Documentation Commands:${NC}"
    echo "  bin/test-docs.sh            # This documentation script"
    echo ""
}

# Show usage examples
show_examples() {
    echo -e "${BLUE}📚 Usage Examples${NC}"
    echo "================"
    echo ""
    
    echo -e "${YELLOW}Basic Test Execution:${NC}"
    echo "# Run all tests quickly"
    echo "npm test"
    echo ""
    echo "# Run tests with coverage"
    echo "npm run test:coverage"
    echo ""
    echo "# Run specific test"
    echo "bin/run-tests-quick.sh --test-filter ThemeSetupTest"
    echo ""
    
    echo -e "${YELLOW}Interactive Testing:${NC}"
    echo "# Start interactive mode"
    echo "bin/test-interactive.sh"
    echo ""
    echo "# Debug specific test"
    echo "bin/test-debug-profile.sh debug-single test_basic_functionality"
    echo ""
    echo "# Profile tests"
    echo "bin/test-debug-profile.sh profile --test-filter ThemeSetupTest"
    echo ""
    
    echo -e "${YELLOW}Development Workflow:${NC}"
    echo "# Create new test class"
    echo "bin/test-dev-helper.sh create-test MyNewTest"
    echo ""
    echo "# Create test data factory"
    echo "bin/test-dev-helper.sh create-factory UserFactory"
    echo ""
    echo "# Validate all tests"
    echo "bin/test-dev-helper.sh validate-tests"
    echo ""
    
    echo -e "${YELLOW}Environment Management:${NC}"
    echo "# Setup test environment"
    echo "bin/test-env-manager.sh setup"
    echo ""
    echo "# Check environment status"
    echo "bin/test-env-manager.sh status"
    echo ""
    echo "# Reset environment"
    echo "bin/test-env-manager.sh reset"
    echo ""
    
    echo -e "${YELLOW}Performance Monitoring:${NC}"
    echo "# Monitor test performance"
    echo "bin/test-performance-monitor.sh monitor --monitor-duration 120"
    echo ""
    echo "# Identify slow tests"
    echo "bin/test-performance-monitor.sh slow-tests"
    echo ""
    echo "# Check for memory leaks"
    echo "bin/test-performance-monitor.sh memory-leaks"
    echo ""
    
    echo -e "${YELLOW}CI/CD Integration:${NC}"
    echo "# Run CI tests"
    echo "npm run test:ci"
    echo ""
    echo "# Check coverage thresholds"
    echo "npm run test:thresholds"
    echo ""
    echo "# Validate quality gates"
    echo "npm run test:quality-gates"
    echo ""
}

# Show testing best practices
show_best_practices() {
    echo -e "${BLUE}✨ Testing Best Practices${NC}"
    echo "======================="
    echo ""
    
    echo -e "${YELLOW}Test Organization:${NC}"
    echo "• Use descriptive test method names (test_should_return_true_when_valid_input)"
    echo "• Group related tests in the same test class"
    echo "• Use setUp() and tearDown() methods for test isolation"
    echo "• Keep tests independent and order-independent"
    echo ""
    
    echo -e "${YELLOW}Test Data Management:${NC}"
    echo "• Use test data factories for consistent data creation"
    echo "• Create test fixtures for complex data scenarios"
    echo "• Use database seeders for test data setup"
    echo "• Clean up test data after each test"
    echo ""
    
    echo -e "${YELLOW}Test Performance:${NC}"
    echo "• Keep tests fast (ideally under 1 second each)"
    echo "• Use mocks for external dependencies"
    echo "• Avoid database operations in unit tests"
    echo "• Run tests in parallel when possible"
    echo ""
    
    echo -e "${YELLOW}Test Coverage:${NC}"
    echo "• Aim for high coverage on critical business logic"
    echo "• Focus on testing edge cases and error conditions"
    echo "• Don't test trivial getters/setters"
    echo "• Use coverage reports to identify gaps"
    echo ""
    
    echo -e "${YELLOW}Test Maintenance:${NC}"
    echo "• Update tests when requirements change"
    echo "• Refactor tests to improve readability"
    echo "• Remove obsolete tests"
    echo "• Document complex test scenarios"
    echo ""
}

# Show troubleshooting guide
show_troubleshooting() {
    echo -e "${BLUE}🔧 Troubleshooting Guide${NC}"
    echo "======================="
    echo ""
    
    echo -e "${YELLOW}Common Issues and Solutions:${NC}"
    echo ""
    
    echo -e "${CYAN}1. Tests not running${NC}"
    echo "   Problem: Tests fail to execute"
    echo "   Solution:"
    echo "   • Check Docker is running: docker info"
    echo "   • Verify test container: docker-compose ps test"
    echo "   • Check test environment: bin/test-env-manager.sh status"
    echo ""
    
    echo -e "${CYAN}2. Database connection errors${NC}"
    echo "   Problem: Cannot connect to test database"
    echo "   Solution:"
    echo "   • Setup test database: bin/setup-test-db.sh"
    echo "   • Check database status: bin/test-env-manager.sh status"
    echo "   • Reset database: bin/reset-test-db.sh"
    echo ""
    
    echo -e "${CYAN}3. Memory issues${NC}"
    echo "   Problem: Tests fail due to memory limits"
    echo "   Solution:"
    echo "   • Increase memory limit: --memory-limit 4G"
    echo "   • Check for memory leaks: bin/test-performance-monitor.sh memory-leaks"
    echo "   • Optimize test data usage"
    echo ""
    
    echo -e "${CYAN}4. Slow test execution${NC}"
    echo "   Problem: Tests take too long to run"
    echo "   Solution:"
    echo "   • Identify slow tests: bin/test-performance-monitor.sh slow-tests"
    echo "   • Run tests in parallel: --parallel-execution"
    echo "   • Optimize test setup and teardown"
    echo ""
    
    echo -e "${CYAN}5. Coverage issues${NC}"
    echo "   Problem: Coverage reports not generated"
    echo "   Solution:"
    echo "   • Enable coverage mode: --coverage-enabled"
    echo "   • Check xDebug configuration: bin/test-debug-profile.sh coverage-info"
    echo "   • Verify coverage thresholds: bin/manage-coverage-thresholds.sh check"
    echo ""
    
    echo -e "${CYAN}6. Debugging not working${NC}"
    echo "   Problem: xDebug debugging not functioning"
    echo "   Solution:"
    echo "   • Check debug setup: bin/test-debug-profile.sh setup-debug"
    echo "   • Verify IDE configuration: bin/test-debug-profile.sh debug-info"
    echo "   • Test debug connection: bin/test-debug-profile.sh debug-single test_sample"
    echo ""
    
    echo -e "${YELLOW}Getting Help:${NC}"
    echo "• Check environment status: bin/test-env-manager.sh status"
    echo "• Validate environment: bin/test-env-manager.sh validate"
    echo "• View detailed logs: --verbose flag"
    echo "• Check documentation: bin/test-docs.sh help"
    echo ""
}

# Show API reference
show_api_reference() {
    echo -e "${BLUE}📖 API Reference${NC}"
    echo "==============="
    echo ""
    
    echo -e "${YELLOW}Test Execution Scripts:${NC}"
    echo ""
    echo -e "${CYAN}bin/run-tests.sh [mode] [options]${NC}"
    echo "  Modes: quick, full, ci, coverage, thresholds, quality-gates, reports"
    echo "  Options: --test-filter, --test-group, --coverage-enabled, --verbose"
    echo ""
    echo -e "${CYAN}bin/test-interactive.sh [options]${NC}"
    echo "  Options: --debug, --profile, --coverage, --test-filter, --verbose"
    echo "  Interactive commands: run, debug, profile, coverage, filter, group, status, help, quit"
    echo ""
    echo -e "${CYAN}bin/test-debug-profile.sh [command] [options]${NC}"
    echo "  Commands: debug, profile, coverage, debug-single, profile-single, analyze-profile"
    echo "  Options: --test-filter, --memory-limit, --profile-output, --debug-port, --verbose"
    echo ""
    
    echo -e "${YELLOW}Development Scripts:${NC}"
    echo ""
    echo -e "${CYAN}bin/test-dev-helper.sh [command] [options]${NC}"
    echo "  Commands: create-test, create-factory, create-fixture, create-seeder"
    echo "  Commands: list-tests, list-factories, list-fixtures, validate-tests"
    echo "  Commands: generate-test-data, clean-test-data, test-coverage, test-performance"
    echo ""
    echo -e "${CYAN}bin/test-env-manager.sh [command] [options]${NC}"
    echo "  Commands: setup, teardown, reset, status, validate"
    echo "  Commands: clean-database, clean-coverage, clean-reports, clean-all"
    echo "  Commands: backup-env, restore-env"
    echo ""
    
    echo -e "${YELLOW}Performance Scripts:${NC}"
    echo ""
    echo -e "${CYAN}bin/test-performance-monitor.sh [command] [options]${NC}"
    echo "  Commands: monitor, analyze, benchmark, memory-test, cpu-test"
    echo "  Commands: slow-tests, memory-leaks, performance-report, optimize-suggestions"
    echo "  Options: --output-format, --output-file, --monitor-interval, --monitor-duration"
    echo ""
    
    echo -e "${YELLOW}Database Scripts:${NC}"
    echo ""
    echo -e "${CYAN}bin/manage-test-db.sh [command] [options]${NC}"
    echo "  Commands: setup, cleanup, reset, verify, seed, status"
    echo "  Options: --test-db-name, --test-db-user, --test-db-password, --test-db-host"
    echo ""
    
    echo -e "${YELLOW}Reporting Scripts:${NC}"
    echo ""
    echo -e "${CYAN}bin/generate-junit-report.sh [options]${NC}"
    echo "  Options: --output-dir, --junit-file, --coverage-dir, --verbose"
    echo ""
    echo -e "${CYAN}bin/generate-html-coverage.sh [options]${NC}"
    echo "  Options: --coverage-dir, --html-dir, --clover-file, --verbose"
    echo ""
    echo -e "${CYAN}bin/generate-coverage-dashboard.sh [options]${NC}"
    echo "  Options: --coverage-dir, --html-dir, --dashboard-file, --verbose"
    echo ""
}

# Show configuration options
show_configuration() {
    echo -e "${BLUE}⚙️  Configuration Options${NC}"
    echo "========================"
    echo ""
    
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo ""
    echo -e "${CYAN}Test Execution:${NC}"
    echo "  COVERAGE_ENABLED=true/false          # Enable code coverage"
    echo "  VERBOSE=true/false                   # Enable verbose output"
    echo "  TEST_FILTER=pattern                   # Test filter pattern"
    echo "  TEST_GROUP=group                      # Test group to run"
    echo "  MEMORY_LIMIT=2G                       # PHP memory limit"
    echo ""
    echo -e "${CYAN}Database Configuration:${NC}"
    echo "  TEST_DB_NAME=wordpress_tests          # Test database name"
    echo "  TEST_DB_USER=root                     # Test database user"
    echo "  TEST_DB_PASSWORD=password             # Test database password"
    echo "  TEST_DB_HOST=db                       # Test database host"
    echo ""
    echo -e "${CYAN}Coverage Configuration:${NC}"
    echo "  COVERAGE_DIR=coverage                 # Coverage output directory"
    echo "  THRESHOLDS_FILE=coverage-thresholds.json # Thresholds file"
    echo "  QUALITY_GATES_FILE=quality-gates.json # Quality gates file"
    echo ""
    echo -e "${CYAN}Debug Configuration:${NC}"
    echo "  DEBUG_MODE=true/false                 # Enable debug mode"
    echo "  PROFILE_MODE=true/false               # Enable profile mode"
    echo "  DEBUG_PORT=9003                       # Debug port"
    echo "  DEBUG_HOST=host.docker.internal       # Debug host"
    echo "  PROFILE_OUTPUT_DIR=/tmp/xdebug        # Profile output directory"
    echo ""
    
    echo -e "${YELLOW}Configuration Files:${NC}"
    echo ""
    echo -e "${CYAN}phpunit.xml.dist${NC}"
    echo "  Main PHPUnit configuration file"
    echo "  Contains test suite definitions, coverage settings, and reporting options"
    echo ""
    echo -e "${CYAN}coverage-thresholds.json${NC}"
    echo "  Coverage threshold definitions"
    echo "  Generated by: bin/manage-coverage-thresholds.sh init"
    echo ""
    echo -e "${CYAN}quality-gates.json${NC}"
    echo "  Quality gate definitions"
    echo "  Generated by: bin/manage-coverage-thresholds.sh init"
    echo ""
    echo -e "${CYAN}docker-compose.yml${NC}"
    echo "  Docker Compose configuration"
    echo "  Contains test service definition and environment variables"
    echo ""
}

# Show environment setup
show_environment_setup() {
    echo -e "${BLUE}🏗️  Environment Setup${NC}"
    echo "===================="
    echo ""
    
    echo -e "${YELLOW}Prerequisites:${NC}"
    echo "• Docker and Docker Compose installed"
    echo "• PHP 8.4+ (configured in Docker)"
    echo "• MySQL/MariaDB (configured in Docker)"
    echo "• Git (for version control)"
    echo ""
    
    echo -e "${YELLOW}Setup Steps:${NC}"
    echo ""
    echo -e "${CYAN}1. Clone Repository${NC}"
    echo "   git clone <repository-url>"
    echo "   cd wp-content/themes/power-of-families"
    echo ""
    echo -e "${CYAN}2. Start Docker Services${NC}"
    echo "   docker-compose up -d"
    echo ""
    echo -e "${CYAN}3. Setup Test Environment${NC}"
    echo "   bin/test-env-manager.sh setup"
    echo ""
    echo -e "${CYAN}4. Verify Setup${NC}"
    echo "   bin/test-env-manager.sh status"
    echo "   bin/test-env-manager.sh validate"
    echo ""
    echo -e "${CYAN}5. Run Initial Tests${NC}"
    echo "   npm test"
    echo ""
    
    echo -e "${YELLOW}IDE Configuration:${NC}"
    echo ""
    echo -e "${CYAN}PhpStorm/IntelliJ:${NC}"
    echo "• Install xDebug extension"
    echo "• Configure PHP interpreter (Docker)"
    echo "• Set up xDebug debugging"
    echo "• Configure test runner"
    echo ""
    echo -e "${CYAN}VS Code:${NC}"
    echo "• Install PHP extension"
    echo "• Install xDebug extension"
    echo "• Configure debugging settings"
    echo "• Set up test runner"
    echo ""
    
    echo -e "${YELLOW}Troubleshooting Setup:${NC}"
    echo "• Check Docker status: docker info"
    echo "• Check container status: docker-compose ps"
    echo "• Check environment: bin/test-env-manager.sh status"
    echo "• Validate setup: bin/test-env-manager.sh validate"
    echo ""
}

# Show debugging guide
show_debugging_guide() {
    echo -e "${BLUE}🐛 Debugging Guide${NC}"
    echo "=================="
    echo ""
    
    echo -e "${YELLOW}Debugging Setup:${NC}"
    echo ""
    echo -e "${CYAN}1. Setup Debug Environment${NC}"
    echo "   bin/test-debug-profile.sh setup-debug"
    echo ""
    echo -e "${CYAN}2. Configure IDE${NC}"
    echo "   bin/test-debug-profile.sh debug-info"
    echo ""
    echo -e "${CYAN}3. Start Debugging${NC}"
    echo "   bin/test-debug-profile.sh debug"
    echo ""
    
    echo -e "${YELLOW}Debugging Commands:${NC}"
    echo ""
    echo -e "${CYAN}Debug All Tests:${NC}"
    echo "   bin/test-debug-profile.sh debug"
    echo ""
    echo -e "${CYAN}Debug Specific Test:${NC}"
    echo "   bin/test-debug-profile.sh debug-single test_basic_functionality"
    echo ""
    echo -e "${CYAN}Debug with Filter:${NC}"
    echo "   bin/test-debug-profile.sh debug --test-filter ThemeSetupTest"
    echo ""
    
    echo -e "${YELLOW}Interactive Debugging:${NC}"
    echo ""
    echo -e "${CYAN}Start Interactive Mode:${NC}"
    echo "   bin/test-interactive.sh"
    echo ""
    echo -e "${CYAN}Interactive Commands:${NC}"
    echo "   debug                    # Toggle debug mode"
    echo "   run                      # Run tests with current settings"
    echo "   filter pattern           # Set test filter"
    echo "   status                   # Show current settings"
    echo ""
    
    echo -e "${YELLOW}Debugging Tips:${NC}"
    echo "• Set breakpoints in your test files"
    echo "• Use step debugging to trace execution"
    echo "• Inspect variables and call stack"
    echo "• Use conditional breakpoints for complex scenarios"
    echo "• Check debug logs for connection issues"
    echo ""
}

# Show profiling guide
show_profiling_guide() {
    echo -e "${BLUE}📊 Profiling Guide${NC}"
    echo "=================="
    echo ""
    
    echo -e "${YELLOW}Profiling Setup:${NC}"
    echo ""
    echo -e "${CYAN}1. Setup Profile Environment${NC}"
    echo "   bin/test-debug-profile.sh setup-profile"
    echo ""
    echo -e "${CYAN}2. Configure Profile Output${NC}"
    echo "   bin/test-debug-profile.sh profile-info"
    echo ""
    echo -e "${CYAN}3. Start Profiling${NC}"
    echo "   bin/test-debug-profile.sh profile"
    echo ""
    
    echo -e "${YELLOW}Profiling Commands:${NC}"
    echo ""
    echo -e "${CYAN}Profile All Tests:${NC}"
    echo "   bin/test-debug-profile.sh profile"
    echo ""
    echo -e "${CYAN}Profile Specific Test:${NC}"
    echo "   bin/test-debug-profile.sh profile-single test_basic_functionality"
    echo ""
    echo -e "${CYAN}Analyze Profile Results:${NC}"
    echo "   bin/test-debug-profile.sh analyze-profile"
    echo ""
    
    echo -e "${YELLOW}Profile Analysis Tools:${NC}"
    echo "• KCacheGrind (Linux/KDE)"
    echo "• QCacheGrind (Cross-platform)"
    echo "• WebGrind (Web-based)"
    echo "• PhpStorm built-in profiler"
    echo ""
    
    echo -e "${YELLOW}Profiling Tips:${NC}"
    echo "• Look for functions with high execution time"
    echo "• Identify memory-intensive operations"
    echo "• Find bottlenecks in test execution"
    echo "• Optimize slow test methods"
    echo "• Use profiling to guide performance improvements"
    echo ""
}

# Show coverage guide
show_coverage_guide() {
    echo -e "${BLUE}📈 Coverage Guide${NC}"
    echo "=================="
    echo ""
    
    echo -e "${YELLOW}Coverage Commands:${NC}"
    echo ""
    echo -e "${CYAN}Run Tests with Coverage:${NC}"
    echo "   npm run test:coverage"
    echo ""
    echo -e "${CYAN}Generate Coverage Reports:${NC}"
    echo "   bin/generate-html-coverage.sh"
    echo ""
    echo -e "${CYAN}Generate Coverage Dashboard:${NC}"
    echo "   bin/generate-coverage-dashboard.sh"
    echo ""
    echo -e "${CYAN}Check Coverage Thresholds:${NC}"
    echo "   npm run test:thresholds"
    echo ""
    
    echo -e "${YELLOW}Coverage Analysis:${NC}"
    echo ""
    echo -e "${CYAN}View Coverage Reports:${NC}"
    echo "   open coverage/html/index.html"
    echo ""
    echo -e "${CYAN}Coverage Dashboard:${NC}"
    echo "   open coverage/html/coverage-dashboard.html"
    echo ""
    echo -e "${CYAN}Coverage Thresholds:${NC}"
    echo "   bin/manage-coverage-thresholds.sh check"
    echo ""
    
    echo -e "${YELLOW}Coverage Best Practices:${NC}"
    echo "• Aim for high coverage on critical business logic"
    echo "• Focus on testing edge cases and error conditions"
    echo "• Don't test trivial getters/setters"
    echo "• Use coverage reports to identify gaps"
    echo "• Set appropriate coverage thresholds"
    echo "• Monitor coverage trends over time"
    echo ""
}

# Show performance guide
show_performance_guide() {
    echo -e "${BLUE}⚡ Performance Guide${NC}"
    echo "===================="
    echo ""
    
    echo -e "${YELLOW}Performance Monitoring:${NC}"
    echo ""
    echo -e "${CYAN}Monitor Test Performance:${NC}"
    echo "   bin/test-performance-monitor.sh monitor"
    echo ""
    echo -e "${CYAN}Identify Slow Tests:${NC}"
    echo "   bin/test-performance-monitor.sh slow-tests"
    echo ""
    echo -e "${CYAN}Check Memory Usage:${NC}"
    echo "   bin/test-performance-monitor.sh memory-test"
    echo ""
    echo -e "${CYAN}Check CPU Usage:${NC}"
    echo "   bin/test-performance-monitor.sh cpu-test"
    echo ""
    
    echo -e "${YELLOW}Performance Optimization:${NC}"
    echo ""
    echo -e "${CYAN}Run Performance Benchmarks:${NC}"
    echo "   bin/test-performance-monitor.sh benchmark"
    echo ""
    echo -e "${CYAN}Check for Memory Leaks:${NC}"
    echo "   bin/test-performance-monitor.sh memory-leaks"
    echo ""
    echo -e "${CYAN}Get Optimization Suggestions:${NC}"
    echo "   bin/test-performance-monitor.sh optimize-suggestions"
    echo ""
    
    echo -e "${YELLOW}Performance Best Practices:${NC}"
    echo "• Keep tests fast (ideally under 1 second each)"
    echo "• Use mocks for external dependencies"
    echo "• Avoid database operations in unit tests"
    echo "• Run tests in parallel when possible"
    echo "• Monitor memory usage to prevent leaks"
    echo "• Use appropriate memory limits"
    echo "• Optimize test setup and teardown"
    echo ""
}

# Generate comprehensive documentation
generate_documentation() {
    local output_file="${OUTPUT_FILE:-test-documentation-$(date +%Y%m%d-%H%M%S).html}"
    
    log_info "Generating comprehensive documentation..."
    
    cat > "$output_file" << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power of Families Test Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        .section h3 { color: #555; margin-top: 20px; }
        .code { background: #f8f8f8; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .command { background: #e8f4f8; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
        .highlight { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
        .error { background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; }
        ul, ol { margin: 10px 0; padding-left: 20px; }
        li { margin: 5px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Power of Families Test Documentation</h1>
        <p>Comprehensive guide for test development and execution</p>
        <p>Generated on: <script>document.write(new Date().toLocaleString());</script></p>
    </div>
    
    <div class="section">
        <h2>Quick Start Guide</h2>
        <div class="success">
            <h3>🚀 Getting Started</h3>
            <ol>
                <li>Setup test environment: <span class="command">bin/test-env-manager.sh setup</span></li>
                <li>Run tests: <span class="command">npm test</span></li>
                <li>Run tests with coverage: <span class="command">npm run test:coverage</span></li>
                <li>Debug tests: <span class="command">bin/test-debug-profile.sh debug</span></li>
                <li>Monitor performance: <span class="command">bin/test-performance-monitor.sh monitor</span></li>
            </ol>
        </div>
    </div>
    
    <div class="section">
        <h2>Available Commands</h2>
        <h3>Test Execution</h3>
        <ul>
            <li><span class="command">npm test</span> - Quick test execution</li>
            <li><span class="command">npm run test:coverage</span> - Test execution with coverage</li>
            <li><span class="command">npm run test:ci</span> - CI/CD optimized execution</li>
            <li><span class="command">bin/test-interactive.sh</span> - Interactive test execution</li>
        </ul>
        
        <h3>Development Tools</h3>
        <ul>
            <li><span class="command">bin/test-dev-helper.sh</span> - Test development utilities</li>
            <li><span class="command">bin/test-env-manager.sh</span> - Environment management</li>
            <li><span class="command">bin/test-debug-profile.sh</span> - Debug and profile tests</li>
            <li><span class="command">bin/test-performance-monitor.sh</span> - Performance monitoring</li>
        </ul>
        
        <h3>Database Management</h3>
        <ul>
            <li><span class="command">bin/setup-test-db.sh</span> - Setup test database</li>
            <li><span class="command">bin/cleanup-test-db.sh</span> - Cleanup test database</li>
            <li><span class="command">bin/manage-test-db.sh</span> - Unified database management</li>
        </ul>
        
        <h3>Reporting</h3>
        <ul>
            <li><span class="command">bin/generate-html-coverage.sh</span> - Generate HTML coverage</li>
            <li><span class="command">bin/generate-coverage-dashboard.sh</span> - Generate coverage dashboard</li>
            <li><span class="command">bin/generate-junit-report.sh</span> - Generate JUnit reports</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Best Practices</h2>
        <div class="highlight">
            <h3>✨ Testing Guidelines</h3>
            <ul>
                <li>Use descriptive test method names</li>
                <li>Keep tests independent and order-independent</li>
                <li>Use test data factories for consistent data creation</li>
                <li>Keep tests fast (ideally under 1 second each)</li>
                <li>Aim for high coverage on critical business logic</li>
                <li>Focus on testing edge cases and error conditions</li>
            </ul>
        </div>
    </div>
    
    <div class="section">
        <h2>Troubleshooting</h2>
        <div class="warning">
            <h3>🔧 Common Issues</h3>
            <ul>
                <li><strong>Tests not running:</strong> Check Docker status and test environment</li>
                <li><strong>Database errors:</strong> Setup test database with <span class="command">bin/setup-test-db.sh</span></li>
                <li><strong>Memory issues:</strong> Increase memory limit or check for leaks</li>
                <li><strong>Slow execution:</strong> Identify slow tests and optimize</li>
                <li><strong>Coverage issues:</strong> Enable coverage mode and check xDebug</li>
            </ul>
        </div>
    </div>
    
    <div class="section">
        <h2>Environment Configuration</h2>
        <h3>Environment Variables</h3>
        <table>
            <tr><th>Variable</th><th>Description</th><th>Default</th></tr>
            <tr><td>COVERAGE_ENABLED</td><td>Enable code coverage</td><td>false</td></tr>
            <tr><td>VERBOSE</td><td>Enable verbose output</td><td>false</td></tr>
            <tr><td>TEST_FILTER</td><td>Test filter pattern</td><td>-</td></tr>
            <tr><td>MEMORY_LIMIT</td><td>PHP memory limit</td><td>2G</td></tr>
            <tr><td>DEBUG_MODE</td><td>Enable debug mode</td><td>false</td></tr>
            <tr><td>PROFILE_MODE</td><td>Enable profile mode</td><td>false</td></tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Performance Monitoring</h2>
        <div class="success">
            <h3>📊 Performance Tools</h3>
            <ul>
                <li>Monitor test performance in real-time</li>
                <li>Identify slow-running tests</li>
                <li>Check for memory leaks</li>
                <li>Analyze CPU usage patterns</li>
                <li>Generate performance reports</li>
                <li>Get optimization suggestions</li>
            </ul>
        </div>
    </div>
    
    <div class="section">
        <h2>Getting Help</h2>
        <div class="highlight">
            <h3>📚 Resources</h3>
            <ul>
                <li>Check environment status: <span class="command">bin/test-env-manager.sh status</span></li>
                <li>Validate environment: <span class="command">bin/test-env-manager.sh validate</span></li>
                <li>View this documentation: <span class="command">bin/test-docs.sh help</span></li>
                <li>Check command help: <span class="command">[command] --help</span></li>
            </ul>
        </div>
    </div>
</body>
</html>
EOF

    log_success "Comprehensive documentation generated: $output_file"
}

# Main execution
main() {
    case "$COMMAND" in
        help)
            show_usage
            ;;
        quick-start)
            show_quick_start
            ;;
        commands)
            list_commands
            ;;
        examples)
            show_examples
            ;;
        best-practices)
            show_best_practices
            ;;
        troubleshooting)
            show_troubleshooting
            ;;
        api-reference)
            show_api_reference
            ;;
        configuration)
            show_configuration
            ;;
        environment)
            show_environment_setup
            ;;
        debugging)
            show_debugging_guide
            ;;
        profiling)
            show_profiling_guide
            ;;
        coverage)
            show_coverage_guide
            ;;
        performance)
            show_performance_guide
            ;;
        generate-docs)
            generate_documentation
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

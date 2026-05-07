#!/bin/bash

# Test Development Helper Script
# Provides utilities for test development and management

set -e

# Default values
VERBOSE="${VERBOSE:-false}"

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
    echo -e "${BLUE}Test Development Helper Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  create-test CLASS        Create a new test class"
    echo "  create-factory CLASS     Create a new test data factory"
    echo "  create-fixture NAME       Create a new test fixture"
    echo "  create-seeder NAME        Create a new test data seeder"
    echo "  list-tests               List all available tests"
    echo "  list-factories           List all test data factories"
    echo "  list-fixtures            List all test fixtures"
    echo "  validate-tests           Validate all test files"
    echo "  generate-test-data       Generate test data for development"
    echo "  clean-test-data          Clean up test data"
    echo "  test-coverage FILE       Show coverage for specific file"
    echo "  test-performance         Run performance tests"
    echo "  test-memory              Run memory usage tests"
    echo "  help                     Show this help message"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 create-test ThemeSetupTest           # Create new test class"
    echo "  $0 create-factory UserFactory           # Create new factory"
    echo "  $0 list-tests                           # List all tests"
    echo "  $0 validate-tests                       # Validate test files"
    echo "  $0 test-coverage functions.php          # Show file coverage"
}

# Parse command line arguments
COMMAND="${1:-help}"
shift

while [[ $# -gt 0 ]]; do
    case $1 in
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

# Create a new test class
create_test_class() {
    local class_name="$1"
    if [ -z "$class_name" ]; then
        log_error "Please specify a class name"
        return 1
    fi
    
    local test_file="wp-content/themes/power-of-families/tests/test-${class_name}.php"
    local class_name_lower=$(echo "$class_name" | tr '[:upper:]' '[:lower:]')
    
    if [ -f "$test_file" ]; then
        log_warning "Test file already exists: $test_file"
        return 1
    fi
    
    log_info "Creating test class: $class_name"
    
    cat > "$test_file" << EOF
<?php
/**
 * Test class for $class_name
 * 
 * @package Power_Of_Families
 */

class test_${class_name_lower} extends WP_UnitTestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        // Add setup code here
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void {
        // Add cleanup code here
        parent::tearDown();
    }

    /**
     * Test basic functionality
     */
    public function test_basic_functionality() {
        \$this->assertTrue( true );
    }

    /**
     * Test class instantiation
     */
    public function test_class_instantiation() {
        // Add test code here
        \$this->assertTrue( true );
    }

    /**
     * Test method functionality
     */
    public function test_method_functionality() {
        // Add test code here
        \$this->assertTrue( true );
    }
}
EOF

    log_success "Test class created: $test_file"
    log_info "Don't forget to implement the actual test logic!"
}

# Create a new test data factory
create_test_factory() {
    local factory_name="$1"
    if [ -z "$factory_name" ]; then
        log_error "Please specify a factory name"
        return 1
    fi
    
    local factory_file="wp-content/themes/power-of-families/tests/factories/${factory_name}.php"
    
    if [ -f "$factory_file" ]; then
        log_warning "Factory file already exists: $factory_file"
        return 1
    fi
    
    log_info "Creating test data factory: $factory_name"
    
    cat > "$factory_file" << EOF
<?php
/**
 * Test Data Factory for $factory_name
 * 
 * @package Power_Of_Families
 */

class ${factory_name} {

    /**
     * Create a new instance
     *
     * @param array \$attributes Optional attributes
     * @return mixed
     */
    public static function create(\$attributes = []) {
        // Add factory logic here
        return null;
    }

    /**
     * Create multiple instances
     *
     * @param int \$count Number of instances to create
     * @param array \$attributes Optional attributes
     * @return array
     */
    public static function create_many(\$count, \$attributes = []) {
        \$instances = [];
        for (\$i = 0; \$i < \$count; \$i++) {
            \$instances[] = self::create(\$attributes);
        }
        return \$instances;
    }

    /**
     * Make an instance without saving
     *
     * @param array \$attributes Optional attributes
     * @return mixed
     */
    public static function make(\$attributes = []) {
        // Add factory logic here
        return null;
    }
}
EOF

    log_success "Test data factory created: $factory_file"
    log_info "Don't forget to implement the actual factory logic!"
}

# Create a new test fixture
create_test_fixture() {
    local fixture_name="$1"
    if [ -z "$fixture_name" ]; then
        log_error "Please specify a fixture name"
        return 1
    fi
    
    local fixture_file="wp-content/themes/power-of-families/tests/fixtures/${fixture_name}.php"
    
    if [ -f "$fixture_file" ]; then
        log_warning "Fixture file already exists: $fixture_file"
        return 1
    fi
    
    log_info "Creating test fixture: $fixture_name"
    
    cat > "$fixture_file" << EOF
<?php
/**
 * Test Fixture for $fixture_name
 * 
 * @package Power_Of_Families
 */

class ${fixture_name} {

    /**
     * Set up fixture data
     *
     * @return array Fixture data
     */
    public static function setup() {
        return [
            // Add fixture data here
        ];
    }

    /**
     * Clean up fixture data
     */
    public static function cleanup() {
        // Add cleanup logic here
    }

    /**
     * Get fixture data by key
     *
     * @param string \$key Data key
     * @return mixed
     */
    public static function get(\$key) {
        \$data = self::setup();
        return \$data[\$key] ?? null;
    }
}
EOF

    log_success "Test fixture created: $fixture_file"
    log_info "Don't forget to implement the actual fixture data!"
}

# Create a new test data seeder
create_test_seeder() {
    local seeder_name="$1"
    if [ -z "$seeder_name" ]; then
        log_error "Please specify a seeder name"
        return 1
    fi
    
    local seeder_file="wp-content/themes/power-of-families/tests/seeders/${seeder_name}.php"
    
    if [ -f "$seeder_file" ]; then
        log_warning "Seeder file already exists: $seeder_file"
        return 1
    fi
    
    log_info "Creating test data seeder: $seeder_name"
    
    cat > "$seeder_file" << EOF
<?php
/**
 * Test Data Seeder for $seeder_name
 * 
 * @package Power_Of_Families
 */

class ${seeder_name} {

    /**
     * Seed test data
     *
     * @param array \$options Seeding options
     * @return bool Success status
     */
    public static function seed(\$options = []) {
        try {
            // Add seeding logic here
            
            return true;
        } catch (Exception \$e) {
            error_log('Seeder error: ' . \$e->getMessage());
            return false;
        }
    }

    /**
     * Clean up seeded data
     *
     * @return bool Success status
     */
    public static function cleanup() {
        try {
            // Add cleanup logic here
            
            return true;
        } catch (Exception \$e) {
            error_log('Seeder cleanup error: ' . \$e->getMessage());
            return false;
        }
    }
}
EOF

    log_success "Test data seeder created: $seeder_file"
    log_info "Don't forget to implement the actual seeding logic!"
}

# List all available tests
list_tests() {
    log_info "Available test files:"
    echo ""
    
    local test_files=$(find wp-content/themes/power-of-families/tests -name "test_*.php" -type f | sort)
    
    if [ -z "$test_files" ]; then
        log_warning "No test files found"
        return 0
    fi
    
    for test_file in $test_files; do
        local test_name=$(basename "$test_file" .php)
        local test_class=$(grep -o "class [a-zA-Z_][a-zA-Z0-9_]*" "$test_file" | head -1 | cut -d' ' -f2)
        local test_methods=$(grep -o "public function test_[a-zA-Z_][a-zA-Z0-9_]*" "$test_file" | wc -l)
        
        echo -e "  ${GREEN}$test_name${NC} (${CYAN}$test_class${NC}) - $test_methods test methods"
    done
    
    echo ""
    log_info "Total test files: $(echo "$test_files" | wc -l)"
}

# List all test data factories
list_factories() {
    log_info "Available test data factories:"
    echo ""
    
    local factory_files=$(find wp-content/themes/power-of-families/tests/factories -name "*.php" -type f | sort)
    
    if [ -z "$factory_files" ]; then
        log_warning "No factory files found"
        return 0
    fi
    
    for factory_file in $factory_files; do
        local factory_name=$(basename "$factory_file" .php)
        local factory_class=$(grep -o "class [a-zA-Z_][a-zA-Z0-9_]*" "$factory_file" | head -1 | cut -d' ' -f2)
        
        echo -e "  ${GREEN}$factory_name${NC} (${CYAN}$factory_class${NC})"
    done
    
    echo ""
    log_info "Total factory files: $(echo "$factory_files" | wc -l)"
}

# List all test fixtures
list_fixtures() {
    log_info "Available test fixtures:"
    echo ""
    
    local fixture_files=$(find wp-content/themes/power-of-families/tests/fixtures -name "*.php" -type f | sort)
    
    if [ -z "$fixture_files" ]; then
        log_warning "No fixture files found"
        return 0
    fi
    
    for fixture_file in $fixture_files; do
        local fixture_name=$(basename "$fixture_file" .php)
        local fixture_class=$(grep -o "class [a-zA-Z_][a-zA-Z0-9_]*" "$fixture_file" | head -1 | cut -d' ' -f2)
        
        echo -e "  ${GREEN}$fixture_name${NC} (${CYAN}$fixture_class${NC})"
    done
    
    echo ""
    log_info "Total fixture files: $(echo "$fixture_files" | wc -l)"
}

# Validate all test files
validate_tests() {
    log_info "Validating test files..."
    echo ""
    
    local test_files=$(find wp-content/themes/power-of-families/tests -name "test_*.php" -type f)
    local valid_count=0
    local invalid_count=0
    
    for test_file in $test_files; do
        local test_name=$(basename "$test_file")
        
        # Check for PHP syntax errors
        if php -l "$test_file" >/dev/null 2>&1; then
            log_success "$test_name - Syntax OK"
            ((valid_count++))
        else
            log_error "$test_name - Syntax Error"
            ((invalid_count++))
        fi
        
        # Check for required elements
        if grep -q "extends WP_UnitTestCase" "$test_file"; then
            log_verbose "$test_name - Extends WP_UnitTestCase"
        else
            log_warning "$test_name - Does not extend WP_UnitTestCase"
        fi
        
        # Check for test methods
        local test_methods=$(grep -o "public function test_[a-zA-Z_][a-zA-Z0-9_]*" "$test_file" | wc -l)
        if [ "$test_methods" -gt 0 ]; then
            log_verbose "$test_name - $test_methods test methods"
        else
            log_warning "$test_name - No test methods found"
        fi
    done
    
    echo ""
    log_info "Validation complete: $valid_count valid, $invalid_count invalid"
    
    if [ "$invalid_count" -gt 0 ]; then
        return 1
    fi
}

# Generate test data for development
generate_test_data() {
    log_info "Generating test data for development..."
    
    # Run the enhanced test data seeder
    if docker-compose run --rm test php -r "
        require_once '/tmp/wordpress-tests-lib/includes/functions.php';
        require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/functions.php';
        require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/seeders/EnhancedTestDataSeeder.php';
        
        \$seeder = new EnhancedTestDataSeeder();
        \$result = \$seeder->seed(['scenario' => 'development']);
        
        if (\$result) {
            echo 'Test data generated successfully' . PHP_EOL;
        } else {
            echo 'Failed to generate test data' . PHP_EOL;
            exit(1);
        }
    "; then
        log_success "Test data generated successfully"
    else
        log_error "Failed to generate test data"
        return 1
    fi
}

# Clean up test data
clean_test_data() {
    log_info "Cleaning up test data..."
    
    # Run the cleanup script
    if bin/cleanup-test-db.sh; then
        log_success "Test data cleaned up successfully"
    else
        log_error "Failed to clean up test data"
        return 1
    fi
}

# Show coverage for specific file
show_file_coverage() {
    local file_path="$1"
    if [ -z "$file_path" ]; then
        log_error "Please specify a file path"
        return 1
    fi
    
    if [ ! -f "$file_path" ]; then
        log_error "File not found: $file_path"
        return 1
    fi
    
    log_info "Showing coverage for: $file_path"
    
    # Run coverage analysis for specific file
    if docker-compose run --rm test phpunit --configuration phpunit.xml --coverage-text --filter "$file_path"; then
        log_success "Coverage analysis completed"
    else
        log_error "Coverage analysis failed"
        return 1
    fi
}

# Run performance tests
run_performance_tests() {
    log_info "Running performance tests..."
    
    local start_time=$(date +%s)
    
    # Run tests with performance monitoring
    if docker-compose run --rm test phpunit --configuration phpunit.xml --verbose; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        log_success "Performance tests completed in ${duration}s"
        log_info "Check test output for performance metrics"
    else
        log_error "Performance tests failed"
        return 1
    fi
}

# Run memory usage tests
run_memory_tests() {
    log_info "Running memory usage tests..."
    
    # Run tests with memory monitoring
    if docker-compose run --rm test php -d memory_limit=2G phpunit --configuration phpunit.xml --verbose; then
        log_success "Memory tests completed"
        log_info "Check test output for memory usage information"
    else
        log_error "Memory tests failed"
        return 1
    fi
}

# Main execution
main() {
    case "$COMMAND" in
        create-test)
            create_test_class "$1"
            ;;
        create-factory)
            create_test_factory "$1"
            ;;
        create-fixture)
            create_test_fixture "$1"
            ;;
        create-seeder)
            create_test_seeder "$1"
            ;;
        list-tests)
            list_tests
            ;;
        list-factories)
            list_factories
            ;;
        list-fixtures)
            list_fixtures
            ;;
        validate-tests)
            validate_tests
            ;;
        generate-test-data)
            generate_test_data
            ;;
        clean-test-data)
            clean_test_data
            ;;
        test-coverage)
            show_file_coverage "$1"
            ;;
        test-performance)
            run_performance_tests
            ;;
        test-memory)
            run_memory_tests
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

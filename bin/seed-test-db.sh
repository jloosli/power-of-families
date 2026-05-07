#!/bin/bash

# Test Database Seeding Script
# Seeds the test database with data for different test scenarios

set -e

# Default values
TEST_DB_NAME="${TEST_DB_NAME:-wordpress_tests}"
TEST_DB_USER="${TEST_DB_USER:-root}"
TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-password}"
TEST_DB_HOST="${TEST_DB_HOST:-db}"
SCENARIO="${SCENARIO:-minimal}"
VERBOSE="${VERBOSE:-false}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}Test Database Seeding Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [SCENARIO] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Scenarios:${NC}"
    echo "  minimal      Basic test data for simple tests (default)"
    echo "  blog         Complete blog with posts, pages, and users"
    echo "  theme        Theme-specific data and custom post types"
    echo "  complex      Complex relationships and hierarchies"
    echo "  performance  Large dataset for performance testing"
    echo "  security     Data with potential security issues"
    echo "  accessibility Content focused on accessibility testing"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --force      Force seeding without confirmation"
    echo "  --verbose    Enable verbose output"
    echo "  --cleanup    Clean up existing data before seeding"
    echo "  --verify     Verify data integrity after seeding"
    echo "  --help       Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  SCENARIO     Test scenario to seed (default: minimal)"
    echo "  TEST_DB_NAME Test database name (default: wordpress_tests)"
    echo "  TEST_DB_USER Test database user (default: root)"
    echo "  TEST_DB_PASSWORD Test database password (default: password)"
    echo "  TEST_DB_HOST Test database host (default: db)"
    echo "  VERBOSE      Enable verbose output (default: false)"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 minimal                    # Seed with minimal data"
    echo "  $0 blog --cleanup --verify   # Seed blog data with cleanup and verification"
    echo "  $0 performance --verbose     # Seed performance data with verbose output"
    echo "  SCENARIO=theme $0 --force    # Seed theme data using environment variable"
}

# Parse command line arguments
FORCE=false
CLEANUP=false
VERIFY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        minimal|blog|theme|complex|performance|security|accessibility)
            SCENARIO="$1"
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
        --cleanup)
            CLEANUP=true
            shift
            ;;
        --verify)
            VERIFY=true
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

# Validate scenario
validate_scenario() {
    case $SCENARIO in
        minimal|blog|theme|complex|performance|security|accessibility)
            return 0
            ;;
        *)
            log_error "Invalid scenario: $SCENARIO"
            show_usage
            exit 1
            ;;
    esac
}

# Wait for database to be ready
wait_for_database() {
    log_info "Waiting for database to be ready..."
    until mysqladmin ping -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 --silent; do
        log_verbose "   Waiting for database..."
        sleep 2
    done
    log_success "Database connection established"
}

# Clean up existing data
cleanup_existing_data() {
    log_info "Cleaning up existing test data..."
    
    if [ -f "bin/cleanup-test-db.sh" ]; then
        bash bin/cleanup-test-db.sh
        log_success "Existing data cleaned up"
    else
        log_warning "Cleanup script not found, skipping cleanup"
    fi
}

# Seed the database
seed_database() {
    log_info "Seeding database with scenario: $SCENARIO"
    
    # Create PHP script for seeding
    cat > /tmp/seed-database.php << 'EOF'
<?php
// Load WordPress test environment
require_once '/tmp/wordpress-tests-lib/includes/functions.php';

// Load theme functions
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/functions.php';

// Load test data classes
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/factories/TestDataFactory.php';
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/fixtures/TestFixtures.php';
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/seeders/TestDataSeeder.php';
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/seeders/TestSeederConfig.php';
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/tests/seeders/EnhancedTestDataSeeder.php';

// Get scenario from environment
$scenario = getenv('SCENARIO') ?: 'minimal';
$verbose = getenv('VERBOSE') === 'true';

try {
    if ($verbose) {
        echo "Starting database seeding for scenario: $scenario\n";
    }
    
    // Use enhanced seeder if available
    if (class_exists('EnhancedTestDataSeeder')) {
        $created = EnhancedTestDataSeeder::seed($scenario);
    } else {
        $created = TestDataSeeder::seed($scenario);
    }
    
    if ($verbose) {
        echo "Seeding completed successfully\n";
        echo "Created objects:\n";
        foreach ($created as $type => $objects) {
            if (is_array($objects)) {
                echo "  $type: " . count($objects) . " items\n";
            } else {
                echo "  $type: $objects\n";
            }
        }
    }
    
    echo "SUCCESS\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

    # Run the seeding script
    if docker-compose run --rm test php /tmp/seed-database.php; then
        log_success "Database seeded successfully"
    else
        log_error "Database seeding failed"
        exit 1
    fi
}

# Verify data integrity
verify_data_integrity() {
    log_info "Verifying data integrity..."
    
    # Create PHP script for verification
    cat > /tmp/verify-data.php << 'EOF'
<?php
// Load WordPress test environment
require_once '/tmp/wordpress-tests-lib/includes/functions.php';

// Load theme functions
require_once dirname(__DIR__) . '/wp-content/themes/power-of-families/functions.php';

$errors = 0;

// Check users
$users = get_users();
if (empty($users)) {
    echo "ERROR: No users found\n";
    $errors++;
} else {
    echo "Found " . count($users) . " users\n";
}

// Check posts
$posts = get_posts(['numberposts' => -1]);
if (empty($posts)) {
    echo "WARNING: No posts found\n";
} else {
    echo "Found " . count($posts) . " posts\n";
}

// Check pages
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
if (empty($pages)) {
    echo "WARNING: No pages found\n";
} else {
    echo "Found " . count($pages) . " pages\n";
}

// Check categories
$categories = get_categories();
if (empty($categories)) {
    echo "INFO: No categories found\n";
} else {
    echo "Found " . count($categories) . " categories\n";
}

// Check tags
$tags = get_tags();
if (empty($tags)) {
    echo "INFO: No tags found\n";
} else {
    echo "Found " . count($tags) . " tags\n";
}

if ($errors > 0) {
    echo "VERIFICATION_FAILED\n";
    exit(1);
} else {
    echo "VERIFICATION_SUCCESS\n";
}
EOF

    if docker-compose run --rm test php /tmp/verify-data.php; then
        log_success "Data integrity verified"
    else
        log_error "Data integrity verification failed"
        exit 1
    fi
}

# Show scenario information
show_scenario_info() {
    log_info "Scenario: $SCENARIO"
    
    case $SCENARIO in
        minimal)
            log_info "  Description: Basic test data for simple tests"
            log_info "  Users: 1, Posts: 1, Pages: 1"
            ;;
        blog)
            log_info "  Description: Complete blog with posts, pages, and users"
            log_info "  Users: 3, Posts: 5, Pages: 3, Categories: 3, Tags: 5"
            ;;
        theme)
            log_info "  Description: Theme-specific data and custom post types"
            log_info "  Users: 2, Posts: 3, Pages: 2, Custom Posts: 2"
            ;;
        complex)
            log_info "  Description: Complex relationships and hierarchies"
            log_info "  Users: 4, Posts: 8, Pages: 4, Categories: 5, Tags: 8"
            ;;
        performance)
            log_info "  Description: Large dataset for performance testing"
            log_info "  Users: 50, Posts: 100, Pages: 10"
            ;;
        security)
            log_info "  Description: Data with potential security issues"
            log_info "  Users: 2, Posts: 5, Pages: 2"
            ;;
        accessibility)
            log_info "  Description: Content focused on accessibility testing"
            log_info "  Users: 2, Posts: 3, Pages: 2"
            ;;
    esac
}

# Main execution
main() {
    echo -e "${BLUE}🌱 Test Database Seeding Script${NC}"
    echo ""
    
    # Validate scenario
    validate_scenario
    
    # Show scenario information
    show_scenario_info
    echo ""
    
    # Wait for database
    wait_for_database
    
    # Clean up if requested
    if [ "$CLEANUP" = true ]; then
        if confirm "Clean up existing test data before seeding?"; then
            cleanup_existing_data
        fi
    fi
    
    # Confirm seeding
    if ! confirm "Seed database with $SCENARIO scenario?"; then
        log_info "Seeding cancelled"
        exit 0
    fi
    
    # Seed database
    seed_database
    
    # Verify if requested
    if [ "$VERIFY" = true ]; then
        verify_data_integrity
    fi
    
    log_success "Database seeding completed successfully!"
    echo ""
    echo -e "${BLUE}💡 Next steps:${NC}"
    echo -e "  Run tests: ${GREEN}npm run test:php${NC}"
    echo -e "  Check data: ${GREEN}docker-compose run --rm test wp-cli post list${NC}"
    echo -e "  Clean up: ${GREEN}bash bin/cleanup-test-db.sh${NC}"
}

# Run main function
main

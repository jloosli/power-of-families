#!/bin/bash

# Test Database Isolation Verification Script
# Comprehensive verification of test database isolation

set -e

# Default values
TEST_DB_NAME="${TEST_DB_NAME:-wordpress_tests}"
DEV_DB_NAME="${DEV_DB_NAME:-poweroffamilies}"
TEST_DB_USER="${TEST_DB_USER:-root}"
TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-password}"
TEST_DB_HOST="${TEST_DB_HOST:-db}"
VERBOSE="${VERBOSE:-false}"
OUTPUT_FORMAT="${OUTPUT_FORMAT:-text}"

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
    echo -e "${BLUE}Test Database Isolation Verification Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --verbose       Enable verbose output"
    echo "  --json          Output results in JSON format"
    echo "  --xml           Output results in XML format"
    echo "  --html          Output results in HTML format"
    echo "  --report        Generate detailed report file"
    echo "  --help          Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  TEST_DB_NAME    Test database name (default: wordpress_tests)"
    echo "  DEV_DB_NAME     Development database name (default: poweroffamilies)"
    echo "  TEST_DB_USER    Test database user (default: root)"
    echo "  TEST_DB_PASSWORD Test database password (default: password)"
    echo "  TEST_DB_HOST    Test database host (default: db)"
    echo "  VERBOSE         Enable verbose output (default: false)"
    echo "  OUTPUT_FORMAT   Output format: text, json, xml, html (default: text)"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                              # Basic verification"
    echo "  $0 --verbose --json            # Verbose JSON output"
    echo "  $0 --html --report             # HTML report with file output"
    echo "  VERBOSE=true $0 --xml          # Verbose XML using environment variable"
}

# Parse command line arguments
VERBOSE_OUTPUT=false
JSON_OUTPUT=false
XML_OUTPUT=false
HTML_OUTPUT=false
GENERATE_REPORT=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --verbose)
            VERBOSE_OUTPUT=true
            shift
            ;;
        --json)
            JSON_OUTPUT=true
            OUTPUT_FORMAT="json"
            shift
            ;;
        --xml)
            XML_OUTPUT=true
            OUTPUT_FORMAT="xml"
            shift
            ;;
        --html)
            HTML_OUTPUT=true
            OUTPUT_FORMAT="html"
            shift
            ;;
        --report)
            GENERATE_REPORT=true
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
    if [ "$VERBOSE_OUTPUT" = true ] || [ "$OUTPUT_FORMAT" = "text" ]; then
        echo -e "${BLUE}ℹ️  $1${NC}"
    fi
}

log_success() {
    if [ "$VERBOSE_OUTPUT" = true ] || [ "$OUTPUT_FORMAT" = "text" ]; then
        echo -e "${GREEN}✅ $1${NC}"
    fi
}

log_warning() {
    if [ "$VERBOSE_OUTPUT" = true ] || [ "$OUTPUT_FORMAT" = "text" ]; then
        echo -e "${YELLOW}⚠️  $1${NC}"
    fi
}

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

log_verbose() {
    if [ "$VERBOSE_OUTPUT" = true ]; then
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Wait for database to be ready
wait_for_database() {
    log_info "Waiting for database to be ready..."
    until docker-compose exec -T db mysqladmin ping -h"localhost" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --silent 2>/dev/null; do
        log_verbose "   Waiting for database..."
        sleep 2
    done
    log_success "Database connection established"
}

# Run verification
run_verification() {
    log_info "Running database isolation verification..."
    
    # Create PHP script for verification
    cat > /tmp/verify-isolation.php << 'EOF'
<?php
// Load WordPress test environment
require_once '/tmp/wordpress-tests-lib/includes/functions.php';

// Load theme functions
require_once dirname(__DIR__) . '/power-of-families/functions.php';

// Load verification classes
require_once dirname(__DIR__) . '/power-of-families/tests/verification/DatabaseIsolationVerifier.php';

// Get options from environment
$options = [
    'test_db_name' => getenv('TEST_DB_NAME') ?: 'wordpress_tests',
    'dev_db_name' => getenv('DEV_DB_NAME') ?: 'poweroffamilies',
    'test_db_user' => getenv('TEST_DB_USER') ?: 'root',
    'test_db_password' => getenv('TEST_DB_PASSWORD') ?: 'password',
    'test_db_host' => getenv('TEST_DB_HOST') ?: 'db',
    'verbose' => getenv('VERBOSE') === 'true',
];

try {
    $results = DatabaseIsolationVerifier::verify_isolation($options);
    
    // Output results based on format
    $format = getenv('OUTPUT_FORMAT') ?: 'text';
    
    switch ($format) {
        case 'json':
            echo json_encode($results, JSON_PRETTY_PRINT);
            break;
        case 'xml':
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<verification>\n";
            echo "  <overall_status>" . htmlspecialchars($results['overall_status']) . "</overall_status>\n";
            echo "  <checks>\n";
            foreach ($results['checks'] as $name => $check) {
                echo "    <check name=\"" . htmlspecialchars($name) . "\" status=\"" . htmlspecialchars($check['status']) . "\">\n";
                foreach ($check['details'] as $detail) {
                    echo "      <detail>" . htmlspecialchars($detail) . "</detail>\n";
                }
                foreach ($check['errors'] as $error) {
                    echo "      <error>" . htmlspecialchars($error) . "</error>\n";
                }
                echo "    </check>\n";
            }
            echo "  </checks>\n";
            echo "  <summary>\n";
            echo "    <total_checks>" . $results['summary']['total_checks'] . "</total_checks>\n";
            echo "    <passed_checks>" . $results['summary']['passed_checks'] . "</passed_checks>\n";
            echo "    <failed_checks>" . $results['summary']['failed_checks'] . "</failed_checks>\n";
            echo "    <warnings>" . $results['summary']['warnings'] . "</warnings>\n";
            echo "  </summary>\n";
            echo "</verification>\n";
            break;
        case 'html':
            echo "<!DOCTYPE html>\n";
            echo "<html><head><title>Database Isolation Verification Report</title></head><body>\n";
            echo "<h1>Database Isolation Verification Report</h1>\n";
            echo "<p><strong>Overall Status:</strong> <span style=\"color: " . ($results['overall_status'] === 'PASS' ? 'green' : 'red') . "\">" . $results['overall_status'] . "</span></p>\n";
            echo "<h2>Checks</h2>\n";
            echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">\n";
            echo "<tr><th>Check</th><th>Status</th><th>Details</th><th>Errors</th></tr>\n";
            foreach ($results['checks'] as $name => $check) {
                echo "<tr>\n";
                echo "<td>" . htmlspecialchars($name) . "</td>\n";
                echo "<td style=\"color: " . ($check['status'] === 'PASS' ? 'green' : 'red') . "\">" . $check['status'] . "</td>\n";
                echo "<td><ul>";
                foreach ($check['details'] as $detail) {
                    echo "<li>" . htmlspecialchars($detail) . "</li>";
                }
                echo "</ul></td>\n";
                echo "<td><ul>";
                foreach ($check['errors'] as $error) {
                    echo "<li style=\"color: red\">" . htmlspecialchars($error) . "</li>";
                }
                echo "</ul></td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
            echo "<h2>Summary</h2>\n";
            echo "<p>Total Checks: " . $results['summary']['total_checks'] . "</p>\n";
            echo "<p>Passed: " . $results['summary']['passed_checks'] . "</p>\n";
            echo "<p>Failed: " . $results['summary']['failed_checks'] . "</p>\n";
            echo "<p>Warnings: " . $results['summary']['warnings'] . "</p>\n";
            echo "</body></html>\n";
            break;
        default: // text
            echo "OVERALL_STATUS: " . $results['overall_status'] . "\n";
            echo "CHECKS:\n";
            foreach ($results['checks'] as $name => $check) {
                echo "  $name: " . $check['status'] . "\n";
                foreach ($check['details'] as $detail) {
                    echo "    - $detail\n";
                }
                foreach ($check['errors'] as $error) {
                    echo "    ERROR: $error\n";
                }
            }
            echo "SUMMARY:\n";
            echo "  Total Checks: " . $results['summary']['total_checks'] . "\n";
            echo "  Passed: " . $results['summary']['passed_checks'] . "\n";
            echo "  Failed: " . $results['summary']['failed_checks'] . "\n";
            echo "  Warnings: " . $results['summary']['warnings'] . "\n";
            break;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

    # Run the verification script
    if docker-compose run --rm test php /tmp/verify-isolation.php; then
        log_success "Verification completed successfully"
        return 0
    else
        log_error "Verification failed"
        return 1
    fi
}

# Generate report file
generate_report() {
    if [ "$GENERATE_REPORT" = true ]; then
        local timestamp=$(date +"%Y%m%d_%H%M%S")
        local report_file="test-isolation-report-${timestamp}.${OUTPUT_FORMAT}"
        
        log_info "Generating report file: $report_file"
        
        # Run verification and save to file
        docker-compose run --rm test php /tmp/verify-isolation.php > "$report_file"
        
        if [ $? -eq 0 ]; then
            log_success "Report generated: $report_file"
        else
            log_error "Failed to generate report"
            return 1
        fi
    fi
}

# Main execution
main() {
    echo -e "${BLUE}🔍 Test Database Isolation Verification${NC}"
    echo ""
    
    # Wait for database
    wait_for_database
    
    # Run verification
    if run_verification; then
        # Generate report if requested
        generate_report
        
        log_success "Database isolation verification completed!"
        
        if [ "$OUTPUT_FORMAT" = "text" ]; then
            echo ""
            echo -e "${BLUE}💡 Next steps:${NC}"
            echo -e "  Run tests: ${GREEN}npm run test:php${NC}"
            echo -e "  Clean up: ${GREEN}bash bin/cleanup-test-db.sh${NC}"
            echo -e "  Reset: ${GREEN}bash bin/reset-test-db.sh${NC}"
        fi
    else
        log_error "Database isolation verification failed!"
        exit 1
    fi
}

# Run main function
main

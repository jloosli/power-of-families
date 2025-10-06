#!/bin/bash

# Test execution script for Docker container
# Supports different test modes: unit, coverage, debug, integration

set -e

# Default values
TEST_MODE="unit"
COVERAGE_THRESHOLD=80
VERBOSE=false
DEBUG=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --mode)
            TEST_MODE="$2"
            shift 2
            ;;
        --coverage-threshold)
            COVERAGE_THRESHOLD="$2"
            shift 2
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --debug)
            DEBUG=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  --mode MODE              Test mode: unit, coverage, debug, integration (default: unit)"
            echo "  --coverage-threshold N   Minimum coverage percentage (default: 80)"
            echo "  --verbose                Enable verbose output"
            echo "  --debug                  Enable debug mode"
            echo "  --help                   Show this help message"
            exit 0
            ;;
        *)
            # Pass unknown arguments to PHPUnit
            PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS $1"
            shift
            ;;
    esac
done

# Set PHPUnit options based on mode
case $TEST_MODE in
    "unit")
        # Default unit mode - preserve any passed arguments
        ;;
    "coverage")
        PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS --coverage-html /var/www/html/coverage --coverage-clover /var/www/html/test-reports/coverage.xml"
        ;;
    "debug")
        PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS --debug"
        DEBUG=true
        ;;
    "integration")
        PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS --group integration"
        ;;
    *)
        echo "Invalid test mode: $TEST_MODE"
        echo "Valid modes: unit, coverage, debug, integration"
        exit 1
        ;;
esac

# Add verbose option if requested
if [ "$VERBOSE" = true ]; then
    PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS --debug"
fi

# Set up WordPress test environment
echo "Setting up WordPress test environment..."
bash /usr/local/bin/install-wp-tests.sh \
    "${TEST_DB_NAME:-wordpress_tests}" \
    "${TEST_DB_USER:-root}" \
    "${TEST_DB_PASSWORD:-password}" \
    "${TEST_DB_HOST:-db}" \
    "${WP_VERSION:-latest}" \
    false

# Change to theme directory
cd /var/www/html/wp-content/themes/power-of-families

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Run PHPUnit tests
echo "Running PHPUnit tests in $TEST_MODE mode..."
echo "Command: phpunit --configuration phpunit.xml $PHPUNIT_OPTIONS"

# Check if phpunit.xml exists
if [ ! -f "phpunit.xml" ]; then
    echo "❌ phpunit.xml not found in current directory!"
    echo "Current directory: $(pwd)"
    echo "Contents:"
    ls -la
    exit 1
fi

if phpunit --configuration phpunit.xml $PHPUNIT_OPTIONS; then
    echo "✅ Tests passed successfully!"
    
    # Check coverage if in coverage mode
    if [ "$TEST_MODE" = "coverage" ]; then
        echo "📊 Coverage report generated in /var/www/html/coverage"
        echo "📄 Coverage XML generated in /var/www/html/test-reports/coverage.xml"
    fi
    
    exit 0
else
    echo "❌ Tests failed!"
    exit 1
fi


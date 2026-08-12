#!/bin/bash

# Container-side CI test run: provision the WordPress test suite, install the
# theme's Composer dependencies, then run PHPUnit.
#
# This script only ever runs *inside* the test container -- it assumes
# /var/www/html is the WordPress root and that install-wp-tests.sh is on PATH.
# The host-side dispatcher is bin/run-tests.sh; the two are deliberately
# separate because their argument contracts differ.

set -e

# Paths are resolved against the WordPress root rather than the CWD, because we
# chdir into the theme directory before invoking PHPUnit. ./coverage and
# ./test-reports under this root are bind-mounted to the host.
WP_ROOT="/var/www/html"

COVERAGE_ENABLED=false
GENERATE_REPORTS=false
COVERAGE_DIR="coverage"
TEST_REPORTS_DIR="test-reports"
TEST_FILTER=""
TEST_GROUP=""
TEST_SUITE=""

show_usage() {
    echo "Usage: ci-test.sh [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --coverage-enabled        Emit clover + HTML + text coverage"
    echo "  --generate-reports        Emit a JUnit XML report"
    echo "  --coverage-dir DIR        Coverage output dir (default: coverage)"
    echo "  --test-reports-dir DIR    Report output dir (default: test-reports)"
    echo "  --test-filter FILTER      PHPUnit --filter pattern"
    echo "  --test-group GROUP        PHPUnit --group"
    echo "  --test-suite SUITE        PHPUnit --testsuite"
    echo "  --help                    Show this message"
}

# Resolve a possibly-relative path against the WordPress root.
resolve_path() {
    case "$1" in
        /*) echo "$1" ;;
        *) echo "$WP_ROOT/$1" ;;
    esac
}

while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage-enabled)
            COVERAGE_ENABLED=true
            shift
            ;;
        --generate-reports)
            GENERATE_REPORTS=true
            shift
            ;;
        --coverage-dir)
            COVERAGE_DIR="$2"
            shift 2
            ;;
        --test-reports-dir)
            TEST_REPORTS_DIR="$2"
            shift 2
            ;;
        --test-filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --test-group)
            TEST_GROUP="$2"
            shift 2
            ;;
        --test-suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo "ci-test.sh: unknown option: $1" >&2
            show_usage >&2
            exit 1
            ;;
    esac
done

COVERAGE_DIR="$(resolve_path "$COVERAGE_DIR")"
TEST_REPORTS_DIR="$(resolve_path "$TEST_REPORTS_DIR")"

echo "Setting up WordPress test environment..."
bash /usr/local/bin/install-wp-tests.sh \
    "${TEST_DB_NAME:-wordpress_tests}" \
    "${TEST_DB_USER:-root}" \
    "${TEST_DB_PASSWORD:-password}" \
    "${TEST_DB_HOST:-db}" \
    "${WP_VERSION:-latest}" \
    false

cd "$WP_ROOT/wp-content/themes/power-of-families"

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

phpunit_args=(--configuration phpunit.xml)

if [[ "$COVERAGE_ENABLED" == true ]]; then
    mkdir -p "$COVERAGE_DIR"
    phpunit_args+=(
        "--coverage-clover=$COVERAGE_DIR/clover.xml"
        "--coverage-html=$COVERAGE_DIR/html"
        --coverage-text
    )
fi

if [[ "$GENERATE_REPORTS" == true ]]; then
    mkdir -p "$TEST_REPORTS_DIR"
    phpunit_args+=("--log-junit=$TEST_REPORTS_DIR/junit.xml")
fi

[ -n "$TEST_FILTER" ] && phpunit_args+=(--filter "$TEST_FILTER")
[ -n "$TEST_GROUP" ] && phpunit_args+=(--group "$TEST_GROUP")
[ -n "$TEST_SUITE" ] && phpunit_args+=(--testsuite "$TEST_SUITE")

exec phpunit "${phpunit_args[@]}"

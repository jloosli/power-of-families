#!/bin/bash

# Test database setup script
# Creates an isolated test database for running tests

set -e

# Default values
TEST_DB_NAME="${TEST_DB_NAME:-wordpress_tests}"
TEST_DB_USER="${TEST_DB_USER:-root}"
TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-password}"
TEST_DB_HOST="${TEST_DB_HOST:-db}"

echo "Setting up test database..."

# Wait for database to be ready
echo "Waiting for database to be ready..."
until mysqladmin ping -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 --silent; do
    echo "Waiting for database..."
    sleep 2
done

# Drop existing test database if it exists
echo "Dropping existing test database if it exists..."
mysqladmin -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 drop "$TEST_DB_NAME" --force 2>/dev/null || true

# Create test database
echo "Creating test database: $TEST_DB_NAME"
mysqladmin -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 create "$TEST_DB_NAME"

echo "✅ Test database setup complete!"
echo "Database: $TEST_DB_NAME"
echo "Host: $TEST_DB_HOST"
echo "User: $TEST_DB_USER"


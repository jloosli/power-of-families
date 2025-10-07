#!/bin/bash

# Test Database Cleanup Script
# Removes all test data and resets the test database to a clean state

set -e

# Default values
TEST_DB_NAME="${TEST_DB_NAME:-wordpress_tests}"
TEST_DB_USER="${TEST_DB_USER:-root}"
TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-password}"
TEST_DB_HOST="${TEST_DB_HOST:-db}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧹 Cleaning up test database...${NC}"

# Wait for database to be ready
echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
until mysqladmin ping -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 --silent; do
    echo -e "${YELLOW}   Waiting for database...${NC}"
    sleep 2
done

echo -e "${GREEN}✅ Database connection established${NC}"

# Drop and recreate test database
echo -e "${YELLOW}🗑️  Dropping test database...${NC}"
mysqladmin -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 drop "$TEST_DB_NAME" --force 2>/dev/null || true

echo -e "${YELLOW}📦 Creating fresh test database...${NC}"
mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "CREATE DATABASE \`$TEST_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Clean up WordPress test tables if they exist
echo -e "${YELLOW}🧽 Cleaning up WordPress test tables...${NC}"
mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "USE \`$TEST_DB_NAME\`; DROP TABLE IF EXISTS wptests_posts, wptests_postmeta, wptests_comments, wptests_commentmeta, wptests_terms, wptests_term_taxonomy, wptests_term_relationships, wptests_termmeta, wptests_users, wptests_usermeta, wptests_links, wptests_options;" 2>/dev/null || true

# Clean up any custom tables
echo -e "${YELLOW}🧽 Cleaning up custom tables...${NC}"
mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "USE \`$TEST_DB_NAME\`; SHOW TABLES;" | grep -v "Tables_in" | while read table; do
    if [ ! -z "$table" ]; then
        echo -e "${YELLOW}   Dropping table: $table${NC}"
        mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "USE \`$TEST_DB_NAME\`; DROP TABLE IF EXISTS \`$table\`;"
    fi
done

# Reset WordPress options
echo -e "${YELLOW}⚙️  Resetting WordPress options...${NC}"
mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "USE \`$TEST_DB_NAME\`; DELETE FROM options WHERE option_name LIKE 'power_of_families_%';" 2>/dev/null || true

# Clean up test files
echo -e "${YELLOW}📁 Cleaning up test files...${NC}"
rm -f /tmp/test-db-config.php 2>/dev/null || true
rm -f /tmp/wordpress-tests-lib/wp-tests-config.php 2>/dev/null || true

# Clean up xDebug logs
echo -e "${YELLOW}📝 Cleaning up xDebug logs...${NC}"
rm -f xdebug-logs/*.log 2>/dev/null || true
rm -f xdebug-profiles/* 2>/dev/null || true

# Clean up test reports
echo -e "${YELLOW}📊 Cleaning up test reports...${NC}"
rm -rf coverage/* 2>/dev/null || true
rm -rf test-reports/* 2>/dev/null || true

echo -e "${GREEN}✅ Test database cleanup complete!${NC}"
echo -e "${BLUE}📊 Cleanup Summary:${NC}"
echo -e "   Database: ${GREEN}$TEST_DB_NAME${NC} (recreated)"
echo -e "   WordPress tables: ${GREEN}Cleared${NC}"
echo -e "   Custom tables: ${GREEN}Cleared${NC}"
echo -e "   Theme options: ${GREEN}Cleared${NC}"
echo -e "   Test files: ${GREEN}Cleared${NC}"
echo -e "   Logs: ${GREEN}Cleared${NC}"
echo -e "   Reports: ${GREEN}Cleared${NC}"

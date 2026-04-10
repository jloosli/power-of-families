#!/bin/bash

# Test Database Reset Script
# Completely resets the test database and environment

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

echo -e "${BLUE}🔄 Resetting test database and environment...${NC}"

# Step 1: Clean up existing test database
echo -e "${YELLOW}📋 Step 1: Cleaning up existing test database...${NC}"
bash bin/cleanup-test-db.sh

# Step 2: Stop and restart database container
echo -e "${YELLOW}📋 Step 2: Restarting database container...${NC}"
docker-compose restart db

# Wait for database to be ready
echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
until mysqladmin ping -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 --silent; do
    echo -e "${YELLOW}   Waiting for database...${NC}"
    sleep 2
done

# Step 3: Set up fresh test database
echo -e "${YELLOW}📋 Step 3: Setting up fresh test database...${NC}"
bash bin/setup-test-db.sh

# Step 4: Clean up Docker containers and volumes
echo -e "${YELLOW}📋 Step 4: Cleaning up Docker environment...${NC}"

# Stop test containers
echo -e "${YELLOW}   Stopping test containers...${NC}"
docker-compose stop test 2>/dev/null || true

# Remove test containers
echo -e "${YELLOW}   Removing test containers...${NC}"
docker-compose rm -f test 2>/dev/null || true

# Clean up unused Docker resources
echo -e "${YELLOW}   Cleaning up unused Docker resources...${NC}"
docker system prune -f --volumes 2>/dev/null || true

# Step 5: Rebuild test container
echo -e "${YELLOW}📋 Step 5: Rebuilding test container...${NC}"
docker-compose build test

# Step 6: Verify test environment
echo -e "${YELLOW}📋 Step 6: Verifying test environment...${NC}"

# Test database connection
echo -e "${YELLOW}   Testing database connection...${NC}"
mysql -h"$TEST_DB_HOST" -u"$TEST_DB_USER" -p"$TEST_DB_PASSWORD" --ssl=0 -e "USE \`$TEST_DB_NAME\`; SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✅ Database connection successful${NC}"
else
    echo -e "${RED}   ❌ Database connection failed${NC}"
    exit 1
fi

# Test container build
echo -e "${YELLOW}   Testing container build...${NC}"
docker-compose run --rm test php --version > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✅ Test container working${NC}"
else
    echo -e "${RED}   ❌ Test container failed${NC}"
    exit 1
fi

# Step 7: Run basic test to verify everything works
echo -e "${YELLOW}📋 Step 7: Running basic test verification...${NC}"
if docker-compose run --rm test phpunit --version > /dev/null 2>&1; then
    echo -e "${GREEN}   ✅ PHPUnit available${NC}"
else
    echo -e "${RED}   ❌ PHPUnit not available${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Test database reset complete!${NC}"
echo -e "${BLUE}📊 Reset Summary:${NC}"
echo -e "   Database: ${GREEN}Fresh and clean${NC}"
echo -e "   Containers: ${GREEN}Rebuilt${NC}"
echo -e "   Environment: ${GREEN}Reset${NC}"
echo -e "   Verification: ${GREEN}Passed${NC}"
echo ""
echo -e "${BLUE}💡 Next steps:${NC}"
echo -e "   Run tests: ${GREEN}npm run test:php${NC}"
echo -e "   Debug tests: ${GREEN}docker-compose run --rm test --mode debug${NC}"
echo -e "   Coverage: ${GREEN}docker-compose run --rm test --mode coverage${NC}"

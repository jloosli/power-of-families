# Tasks: xDebug Unit Testing with Docker Container

## Relevant Files

- `docker-compose.yml` - Main Docker Compose configuration file that needs a new test service added
- `docker/test.dockerfile` - New Dockerfile for the testing container with PHP and xDebug
- `docker/test-xdebug.ini` - xDebug configuration file specifically for testing environment
- `.github/workflows/testing.yml` - GitHub Actions workflow that needs to be updated to use Docker-based testing
- `phpunit.xml.dist` - PHPUnit configuration that needs coverage reporting and JUnit XML output
- `power-of-families/tests/bootstrap.php` - Test bootstrap file that may need updates for Docker environment
- `bin/run-tests.sh` - New shell script for easy test execution with different options
- `bin/setup-test-db.sh` - Script to set up isolated test database
- `.env.example` - Example environment file showing required variables for testing
- `coverage/` - Directory for HTML coverage reports (generated)
- `test-reports/` - Directory for JUnit XML test reports (generated)

### Notes

- The testing container will use the PHP version from the project's .env file for consistency
- xDebug will be configured with all features enabled (develop, coverage, debug, profile)
- Test reports will be generated in both HTML (coverage) and JUnit XML formats
- The container will have its own isolated test database separate from the main development database

## Tasks

- [x] 1.0 Create Docker Testing Container Infrastructure
    - [x] 1.1 Create Dockerfile for testing container with PHP and xDebug
    - [x] 1.2 Create xDebug configuration file for testing environment
    - [x] 1.3 Add test service to docker-compose.yml with proper dependencies
    - [x] 1.4 Create directories for test reports and coverage output
    - [x] 1.5 Create test execution script with multiple modes (unit, coverage, debug)
    - [x] 1.6 Create test database setup script
    - [x] 1.7 Create .env.example file with required environment variables
    - [x] 1.8 Update .gitignore to exclude generated test reports
    - [x] 1.9 Build and verify test container works correctly
- [x] 2.0 Configure xDebug for Development Environment (`wordpress` container)
- [ ] 3.0 Set Up Test Database and Data Management
- [ ] 4.0 Update PHPUnit Configuration for Coverage and Reporting
- [ ] 5.0 Create Developer-Friendly Test Execution Scripts (`test` container)
- [ ] 6.0 Update GitHub Actions Workflow for Docker-Based Testing

# PRD: xDebug Unit Testing with Docker Container

## Introduction/Overview

This feature will implement a comprehensive unit testing solution using xDebug in a separate Docker container for the Power of Families WordPress theme. The current testing setup has issues with GitHub Actions integration and lacks proper debugging capabilities. This solution will provide developers with easy-to-run unit tests that work consistently across local development environments and CI/CD pipelines, while offering full xDebug debugging capabilities for development.

The goal is to create a robust, containerized testing environment that enables fast test execution, comprehensive code coverage reporting, and seamless debugging capabilities for theme development.

## Goals

1. **Containerized Testing Environment**: Create a dedicated Docker container for running PHPUnit tests with xDebug enabled
2. **CI/CD Integration**: Ensure the testing solution works reliably in GitHub Actions workflows
3. **Developer Experience**: Provide simple commands for running tests locally with debugging capabilities
4. **Performance Optimization**: Implement fast test execution with efficient resource management
5. **Code Coverage**: Generate comprehensive HTML coverage reports for all theme functionality
6. **Multi-Environment Support**: Support both local development and CI/CD environments seamlessly

## User Stories

**As a developer**, I want to run unit tests in a Docker container so that I can ensure consistent test environments across different machines.

**As a developer**, I want to debug tests using xDebug so that I can step through code and identify issues quickly.

**As a developer**, I want to generate code coverage reports so that I can see which parts of my code are tested.

**As a developer**, I want to run tests with a simple command so that I don't have to remember complex setup procedures.

**As a CI/CD system**, I want to run tests automatically on pull requests so that I can catch regressions before they reach production.

**As a project maintainer**, I want tests to block deployment when they fail so that I can maintain code quality.

## Functional Requirements

1. **Docker Container Setup**
    - The system must provide a dedicated Docker container for running PHPUnit tests
    - The container must use the PHP version specified in the project's .env file
    - The container must include xDebug extension enabled
    - The container must have its own isolated test database

2. **xDebug Configuration**
    - The system must enable all xDebug features (develop, coverage, debug, profile)
    - xDebug must be configurable via environment variables
    - The system must support step debugging for development environments
    - The system must generate code coverage reports in HTML format

3. **Test Execution**
    - The system must provide a simple `docker-compose run test` command for basic test execution
    - The system must support multiple test types (unit, integration, coverage) with different flags
    - The system must provide interactive debugging mode capabilities
    - The system must run tests against all theme functionality including custom PHP classes

4. **CI/CD Integration**
    - The system must work reliably in GitHub Actions workflows
    - The system must replace the current non-working testing configuration
    - The system must block deployment when tests fail
    - The system must generate test reports for CI environments

5. **Performance & Resource Management**
    - The system must optimize for fast test execution
    - The system must use fresh test database for each test run
    - The system must efficiently manage Docker resources
    - The system must provide configurable memory limits

6. **Reporting & Output**
    - The system must provide console output for test results
    - The system must generate HTML coverage reports
    - The system must support JUnit XML output for CI integration
    - The system must display test execution time and performance metrics

## Non-Goals (Out of Scope)

1. **End-to-End Testing**: This feature will not include browser-based testing or full WordPress integration tests
2. **Performance Testing**: Load testing and performance benchmarking are not included
3. **Cross-Browser Testing**: Browser compatibility testing is out of scope
4. **Database Migration Testing**: Testing database schema changes is not included
5. **Plugin Testing**: Testing of WordPress plugins is not included in this scope
6. **Manual Testing Automation**: Automated manual testing workflows are not included

## Design Considerations

### Docker Configuration

- Use multi-stage Docker builds for optimal image size
- Implement health checks for container dependencies
- Use Docker volumes for test data persistence
- Configure proper networking between containers

### xDebug Integration

- Enable xDebug only when needed to avoid performance impact
- Configure proper IDE key mapping for debugging
- Set up proper log file handling and rotation
- Implement conditional xDebug activation based on environment

### Test Data Management

- Use database fixtures for consistent test data
- Implement test data cleanup between test runs
- Create reusable test data factories
- Ensure test isolation and independence

## Technical Considerations

### Dependencies

- Must integrate with existing `phpunit.xml.dist` configuration
- Must work with existing WordPress test framework (`WP_UnitTestCase`)
- Must support existing test files in `/power-of-families/tests/`
- Must integrate with existing Composer autoloading

### Environment Configuration

- Support both local development and CI/CD environments
- Use the PHP version from the project's .env file for consistency
- Implement proper secret management for CI/CD
- Support different PHP versions via matrix testing in CI/CD

### Performance Optimization

- Use PHP OPcache for faster test execution
- Implement parallel test execution where possible
- Optimize Docker layer caching
- Use efficient database connection pooling

### Integration Points

- Must work with existing `docker-compose.yml` setup
- Must integrate with existing GitHub Actions workflows
- Must support existing WordPress testing infrastructure
- Must work with existing theme structure and autoloading

## Success Metrics

1. **Test Execution Speed**: Tests should complete within 2 minutes for the full test suite
2. **CI/CD Reliability**: GitHub Actions test runs should have 99%+ success rate
3. **Developer Adoption**: 100% of developers should be able to run tests with a single command
4. **Code Coverage**: Achieve minimum 80% code coverage for core theme functionality
5. **Debugging Effectiveness**: Developers should be able to debug tests within 30 seconds of setup
6. **Deployment Safety**: Zero deployments should proceed with failing tests

## Open Questions

1. **Test Database Strategy**: Should we use in-memory databases for faster test execution, or maintain MySQL compatibility?
2. **Coverage Thresholds**: What minimum code coverage percentage should be enforced in CI/CD?
3. **Test Parallelization**: Should we implement parallel test execution, and if so, what's the optimal worker count?
4. **Debugging Port Management**: How should we handle port conflicts when multiple developers are debugging simultaneously?
5. **Test Data Cleanup**: Should we implement automatic cleanup of test data, or rely on fresh database per run?
6. **CI/CD Reporting**: Should we integrate with external services like Codecov for coverage reporting?
7. **Performance Monitoring**: Should we track and report test execution performance metrics over time?
8. **Rollback Strategy**: What should happen if the new testing setup breaks existing workflows?

---

**Target Audience**: Junior developers who need clear, actionable requirements for implementing a Docker-based testing solution with xDebug integration.

**Priority**: High - This addresses critical issues with the current testing setup and improves developer productivity.

**Estimated Complexity**: Medium-High - Requires Docker expertise, PHP testing knowledge, and CI/CD integration skills.

# GitHub Actions Workflows

This document describes the GitHub Actions workflows for the Power of Families project.

## Workflow Overview

### 1. Quick Tests (`quick-tests.yml`)

**Purpose:** Fast test execution for pull requests and pushes
**Triggers:**

- Pull requests to `main` or `master`
- Pushes to `main` or `master`

**Features:**

- Docker-based test execution
- Quick test validation
- Fast feedback for developers

### 2. Comprehensive Testing (`comprehensive-tests.yml`)

**Purpose:** Full test suite with all analysis features
**Triggers:**

- Daily schedule (2 AM UTC)
- Manual workflow dispatch
- Configurable test types

**Features:**

- Unit tests
- Coverage analysis
- Performance testing
- Integration tests
- Security tests
- Test result reporting
- Artifact upload

### 3. Docker-Based Testing (`testing.yml`)

**Purpose:** Complete CI/CD pipeline with Docker
**Triggers:**

- Pull requests to `main` or `master`
- Pushes to `main` or `master`
- Manual workflow dispatch

**Features:**

- Test environment setup
- Unit tests
- Coverage analysis
- Performance testing
- Integration tests
- Security tests
- Test summary generation
- PR comments
- Build and deploy (main branch only)

### 4. Deploy via SSH (`deploy.yml`)

**Purpose:** Deploy to production after testing
**Triggers:**

- Pushes to `main` branch
- Manual workflow dispatch

**Features:**

- Pre-deployment testing
- Build and deployment
- SSH-based deployment

## Workflow Status Badges

Add these badges to your README.md:

```markdown
![Quick Tests](https://github.com/your-username/power-of-families/workflows/Quick%20Tests/badge.svg)
![Comprehensive Tests](https://github.com/your-username/power-of-families/workflows/Comprehensive%20Testing/badge.svg)
![Docker-Based Testing](https://github.com/your-username/power-of-families/workflows/Docker-Based%20Testing/badge.svg)
![Deploy](https://github.com/your-username/power-of-families/workflows/Deploy%20via%20SSH/badge.svg)
```

## Environment Variables

The workflows use the following environment variables:

### Required Secrets

- `SSH_HOST`: SSH host for deployment
- `SSH_USER`: SSH user for deployment
- `SSH_KEY`: SSH private key for deployment
- `DEST_PATH`: Destination path on remote server

### Optional Environment Variables

- `DOCKER_COMPOSE_VERSION`: Docker Compose version (default: 2.24.0)
- `PHP_VERSION`: PHP version (default: 8.4)
- `NODE_VERSION`: Node.js version (default: 20)

## Workflow Features

### Test Environment Management

- Automatic Docker service startup
- Test environment setup and validation
- Service health checks
- Automatic cleanup

### Test Execution

- Unit tests with PHPUnit
- Coverage analysis with xDebug
- Performance monitoring
- Integration testing
- Security testing

### Reporting

- JUnit XML test results
- HTML coverage reports
- Performance benchmarks
- Test summaries
- PR comments with results

### Artifacts

- Test results
- Coverage reports
- Performance data
- Security scan results
- Test summaries

## Manual Workflow Triggers

### Comprehensive Testing

You can manually trigger comprehensive testing with options:

- Run coverage analysis: `true/false`
- Run performance tests: `true/false`
- Run integration tests: `true/false`
- Run security tests: `true/false`

### Deploy

Manual deployment is available for emergency deployments.

## Troubleshooting

### Common Issues

1. **Docker services not starting**
    - Check Docker Compose version compatibility
    - Verify service dependencies
    - Check resource availability

2. **Test failures**
    - Review test logs in artifacts
    - Check test environment setup
    - Verify database connectivity

3. **Coverage issues**
    - Ensure xDebug is properly configured
    - Check coverage thresholds
    - Verify test execution

4. **Performance issues**
    - Review performance reports
    - Check memory usage
    - Identify slow tests

### Debugging Steps

1. Check workflow logs for specific job failures
2. Download and review artifacts
3. Test locally with same Docker setup
4. Verify environment variables and secrets
5. Check service health and connectivity

## Best Practices

1. **Pull Request Workflow**
    - Quick tests run automatically
    - Comprehensive tests can be triggered manually
    - All tests must pass before merge

2. **Main Branch Workflow**
    - All tests run automatically
    - Deployment only occurs after successful tests
    - Artifacts are preserved for 30 days

3. **Scheduled Testing**
    - Comprehensive tests run daily
    - Issues are created for failures
    - Performance trends are monitored

4. **Security**
    - Secrets are properly configured
    - SSH keys are managed securely
    - Test data is isolated

## Monitoring and Alerts

- Workflow status badges in README
- PR comments with test results
- GitHub Issues for test failures
- Artifact retention for debugging
- Performance trend monitoring

## Future Enhancements

- Matrix testing for multiple PHP versions
- Parallel test execution
- Advanced security scanning
- Performance regression detection
- Automated dependency updates

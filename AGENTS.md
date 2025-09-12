# Agent Onboarding Instructions

## Repository Summary

This repository contains the full codebase for the Power of Families WordPress site, including:

- Custom theme (`power-of-families`) as a child of the [Genesis Framework](https://studiopress.github.io/genesis/)
- WordPress core files (`wordpress/`)
- Database and backup scripts
- Docker setup for local development
- CI/CD workflows for deployment and testing using GitHub Actions
- Further instructions in `README.md`.

The main purpose is to provide a modern, maintainable, and extensible WordPress environment for Power of Families, leveraging custom themes and plugins.

## High-Level Information

- **Languages:** PHP, TypeScript, JavaScript, HTML, CSS
- **Frameworks:** WordPress, [Genesis Framework](https://studiopress.github.io/genesis/) (parent theme)
- **Build Tools:** npm, Docker
- **Repo Size:** Large, with multiple subprojects and plugins
- **Target Runtime:** PHP 8.4+ (WordPress), Node.js (build tools)

## Build, Bootstrap, and Validation Steps

### Environment Setup

- Always run `docker-compose up -d wordpress` before any build or test steps. This starts the WordPress, database, and phpMyAdmin containers.
- Use `phpMyAdmin` at [http://localhost:8180](http://localhost:8180) for DB management. Credentials are in `docker-compose.yml`.
- Import database backups using rsync and phpMyAdmin as described in the root `README.md`.

### Theme Development

**Theme (`power-of-families`):**

- Build JS/CSS: `npm install` (in repo root), then `npm run build` or `npm run start`.
- Lint: Use Prettier config (`prettier.config.js`) and WordPress coding standards (`phpcs.xml.dist`). Use spaces instead of tabs.

### Testing and Validation

- No automated test suite is present; manual validation is required.
- For JS/CSS linting, use npm scripts as defined in each `package.json`.
- Always validate changes by running the site locally in Docker and checking for errors in the browser and logs.

### CI/CD and Deployment

- GitHub Actions workflow (`.github/workflows/deploy.yml`) builds with Node.js 20, runs `npm install` and `npm run build`, then deploys via rsync over SSH.
- GitHub Actions workflow (`.github/workflows/test.yml`) builds with Node.js 20, runs `npm install` and `npm run build`, then tests PHP and Typescript.
- Always ensure the build passes locally before pushing changes.
- The workflow expects secrets for SSH deployment; see workflow file for details.

## Project Layout and Key Files

- **Root:** `README.md`, `docker-compose.yml`, `package.json`, `tsconfig.json`, `prettier.config.js`
- **Theme:** `power-of-families/` (main theme code, build scripts, config, linting)
- **Parent Theme:** `wordpress/wp-content/themes/genesis/` (Genesis Framework files - parent theme)
- **CSS:** `power-of-families/assets/css/` (CSS files for the theme)
- **PHP:** `power-of-families/inc/` (PHP files for the theme)
- **Wordpress:** `wordpress/` (core files, `wp-content/themes/`, `wp-content/plugins/`)
- **Database:** `db-data/`, `db-backups/`
- **Tasks:** `tasks/` (project requirements and task lists)
- **CI/CD:** `.github/workflows/deploy.yml`

## Configuration Files

- **Linting:** `prettier.config.js`, `.phpcs.xml.dist`
- **Docker:** `docker-compose.yml`
- **PostCSS:** `postcss.config.js`
- **PHPUnit:** `phpunit.xml.dist` and `.phpcs.xml.dist`
- **Composer:** `composer.json` and `composer.lock`
- **Node:** `package.json` and `package-lock.json`

## Validation and Checks Before Commit

- Always run `npm install` and `npm run build` before pushing changes.
- For JS/CSS changes, run lint scripts as defined in `package.json`.
- Validate the site in Docker before pushing.
- Ensure CI/CD workflow passes on GitHub after push.

## Development Standards

- **Commit Messages:** Follow conventional commits format (feat:, fix:, docs:, etc.)
- **Branch Strategy:** Use feature branches with format `feature/description` or `fix/description`
- **Code Review Requirements:** List required approvers and acceptance criteria
- **Documentation:** Requirements for inline comments, README updates, and changelog entries

## Common Issues and Solutions

- Database connection errors: Check docker-compose logs and ensure MySQL is running
- Memory limits: Adjust PHP memory settings in docker-compose.yml
- Build failures: Common npm and composer issues and their solutions
- Local development gotchas: Known issues with file permissions, cache, etc.

## Performance Guidelines

- WordPress-specific performance best practices

## PRDs and Tasks

- Use the agent defined in `.agents` to [generate Product Requirements Documents (PRDs)](.agents/create-prd.md), [generate tasks](.agents/create-task.md), and [process the task list based on user prompts](.agents/process-tasks.md).

## Additional Notes

- The theme is a child of the Genesis Framework; see links in `README.md` for documentation.
- Trust these instructions for build, validation, and layout. Only search the codebase if information here is incomplete or in error.

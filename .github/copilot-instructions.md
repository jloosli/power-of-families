# Copilot Coding Agent Onboarding Instructions

## Repository Summary

This repository contains the full codebase for the Power of Families Wordpress site, including:

- Custom theme (`power-of-families`)
- Wordpress core files (`wordpress/`)
- Database and backup scripts
- Docker setup for local development

The main purpose is to provide a modern, maintainable, and extensible Wordpress environment for Power of Families, leveraging custom themes and plugins.

## High-Level Information

- **Languages:** PHP, TypeScript, JavaScript, CSS
- **Frameworks:** Wordpress, Genesis Framework (child theme)
- **Build Tools:** npm, Docker
- **Repo Size:** Large, with multiple subprojects and plugins
- **Target Runtime:** PHP 7.4+ (Wordpress), Node.js (build tools)

## Build, Bootstrap, and Validation Steps

### Environment Setup

- Always run `docker-compose up -d wordpress` before any build or test steps. This starts the Wordpress, database, and phpMyAdmin containers.
- Use `phpMyAdmin` at [http://localhost:8180](http://localhost:8180) for DB management. Credentials are in `docker-compose.yml`.
- Import database backups using rsync and phpMyAdmin as described in the root `README.md`.

### Theme Development

- **Theme (`power-of-families`):**
    - Build JS/CSS: `npm install` (in repo root), then `npm run build` or `npm run start`.
    - Lint: Use Prettier config (`prettier.config.js`) and WordPress coding standards (`phpcs.xml.dist`).

### Testing and Validation

- No automated test suite is present; manual validation is required.
- For JS/CSS linting, use npm scripts as defined in each `package.json`.
- Always validate changes by running the site locally in Docker and checking for errors in the browser and logs.

### CI/CD and Deployment

- GitHub Actions workflow (`.github/workflows/deploy.yml`) builds with Node.js 20, runs `npm install` and `npm run build`, then deploys via rsync over SSH.
- Always ensure the build passes locally before pushing changes.
- The workflow expects secrets for SSH deployment; see workflow file for details.

## Project Layout and Key Files

- **Root:** `README.md`, `docker-compose.yml`, `package.json`, `tsconfig.json`, `prettier.config.js`
- **Theme:** `power-of-families/` (main theme code, build scripts, config, linting)
- **Wordpress:** `wordpress/` (core files, `wp-content/themes/`, `wp-content/plugins/`)
- **Database:** `db_data/`, `db-backups/`
- **Tasks:** `tasks/` (project requirements and task lists)
- **CI/CD:** `.github/workflows/deploy.yml`

## Configuration Files

- **Linting:** `prettier.config.js` (theme)
- **Docker:** `docker-compose.yml`

## Validation and Checks Before Commit

- Always run `npm install` and `npm run build` before pushing changes.
- For JS/CSS changes, run lint scripts as defined in `package.json`.
- Validate the site in Docker before pushing.
- Ensure CI/CD workflow passes on GitHub after push.

## Additional Notes

- The theme is a child of the Genesis Framework; see links in `README.md` for documentation.
- Trust these instructions for build, validation, and layout. Only search the codebase if information here is incomplete or in error.

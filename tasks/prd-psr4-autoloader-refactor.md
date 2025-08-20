# PRD: PSR-4 Autoloader Refactor

## 1. Introduction/Overview

This document outlines the requirements for refactoring the `power-of-families` theme to use Composer's PSR-4 autoloader instead of the legacy `Autoloader.class.php`. This change will improve code organization, adhere to modern PHP standards, and make the codebase more maintainable.

## 2. Goals

*   Replace the custom `Autoloader.class.php` with Composer's PSR-4 autoloader.
*   Make all PHP files in the `power-of-families/inc` directory PSR-4 compliant.
*   Improve code organization and maintainability.
*   Ensure the website functions identically to how it did before the refactor.

## 3. User Stories

*   **As a developer,** I want to use Composer's autoloader so that I can easily manage dependencies and follow modern PHP standards.
*   **As a developer,** I want the codebase to be more organized and easier to navigate so that I can find and modify code more efficiently.

## 4. Functional Requirements

1.  The `power-of-families/composer.json` file must be updated to define a PSR-4 namespace for the `inc` directory.
2.  All PHP files in the `power-of-families/inc` directory must be updated to use the `PowerOfFamilies` namespace.
3.  The `Autoloader.class.php` file must be removed.
4.  All code that includes or uses `Autoloader.class.php` must be removed.
5.  The `composer dump-autoload` command must be run to generate the new autoloader.
6.  The website must function without any errors after the changes are implemented.

## 5. Non-Goals (Out of Scope)

*   This refactoring will not include the `pof-bloom-plugin`.
*   This refactoring will not include any other directories or files besides those in `power-of-families/inc`.
*   No new features will be added.

## 6. Technical Considerations

*   The base namespace will be `PowerOfFamilies`.
*   The `composer.json` file in the `power-of-families` theme directory will be used, not the root `composer.json`.

## 7. Success Metrics

*   The `Autoloader.class.php` file is successfully removed.
*   The website functions as it did before the changes, with no visible errors or issues.
*   The `inc` directory and its files are namespaced and autoloaded correctly.

## 8. Open Questions

*   None at this time.

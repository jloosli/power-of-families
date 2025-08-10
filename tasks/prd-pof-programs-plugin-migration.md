# PRD: Migrate "pof-programs-plugin" into "power-of-families" Theme

**Author:** Gemini
**Date:** 2025-08-10

## 1. Overview

This document outlines the requirements for migrating the functionality of the `pof-programs-plugin` directly into the `power-of-families` WordPress theme. This will streamline the codebase, reduce maintenance overhead, and simplify the development workflow by consolidating functionality into a single, unified location.

## 2. Project Goals

*   **Consolidate Codebase:** Move all functionality from the `pof-programs-plugin` into the `power-of-families` theme.
*   **Simplify Build Process:** Eliminate the separate build process for the plugin and integrate its assets into the theme's existing `npm run build` and `npm run start` scripts.
*   **Improve Performance:** Reduce the number of active plugins, which can lead to minor performance improvements.
*   **Reduce Maintenance:** A single codebase is easier to maintain and update.

## 3. Scope

### In Scope

*   **Migrate all PHP files:** All PHP files from the plugin's `lib` directory will be moved into the theme's `inc` directory, following the existing autoloader structure.
*   **Migrate main plugin file:** The functionality of `power-of-families-programs.php` will be integrated into the theme's `functions.php` or a new file within the `inc` directory.
*   **Migrate and compile assets:** All SASS/SCSS and TypeScript/JavaScript files from the plugin's `admin` and `public` directories will be moved to the theme's `src` directory and compiled with the theme's existing build process. SCSS files should be converted to CSS.

### Out of Scope

*   **Build process files:** The `Gruntfile.js` and `package.json` from the plugin will not be migrated.
*   **Plugin activation/deactivation hooks:** These will be removed, as the functionality will be part of the theme.
*   **No new functionality:** This project is strictly a migration. No new features will be added.

## 4. Functional Requirements

*   All functionality currently provided by the `pof-programs-plugin` must be present and working correctly after the migration.
*   The theme must continue to build successfully with `npm run build` and `npm run start`.
*   Admin and public assets (CSS and JS) from the plugin must be loaded correctly within the theme.

## 5. Technical Requirements

1.  **File Migration:**
    *   The contents of `pof-programs-plugin/lib/` will be moved to `power-of-families/inc/programs/`.
    *   The main plugin file `power-of-families-programs.php` will be analyzed, and its logic will be moved into `power-of-families/functions.php` or a new file in `power-of-families/inc/`.
    *   The contents of `pof-programs-plugin/admin/js/admin.js` will be moved to `power-of-families/src/programs/`.
    *   The SASS files from `pof-programs-plugin/public/sass/` will be converted to css and moved to `power-of-families/assets/css/pof-programs`.

2.  **Build Integration:**
    *   The theme's `package.json` and build scripts will be updated to include the new SASS and TypeScript files from the plugin.
    *   The theme's build process must compile the new assets into the `dist` directory.

3.  **Code Integration:**
    *   The theme's `functions.php` will be updated to include the new PHP files from the plugin.
    *   Any `wp_enqueue_style` or `wp_enqueue_script` calls will be updated to point to the new asset locations.

## 6. Assumptions

*   The existing build process in the `power-of-families` theme is capable of handling the SASS and TypeScript files from the plugin.
*   The plugin does not have any external dependencies that are not already met by the theme or WordPress core.

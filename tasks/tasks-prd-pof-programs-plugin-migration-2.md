# Tasks for `pof-programs-plugin` Migration (v2)

This document lists the tasks required to migrate the `pof-programs-plugin` into the `power-of-families` theme.

## 1. File Migration

- [x] **Verify Directories:**
    - [x] Verify that `power-of-families/inc/programs` exists for PHP files.
    - [x] Verify that `power-of-families/src/programs` exists for TypeScript/JavaScript files.
    - [x] Verify that `power-of-families/assets/css/pof-programs` exists for the converted SASS files.

- [x] **Migrate PHP Files:**
    - [x] Copy the contents of `pof-programs-plugin/lib/` to `power-of-families/inc/programs/` and modify the files and namespaces so it can use the existing autoloader in `power-of-families/inc/Autoloader.class.php`.

- [x] **Migrate JavaScript Files:**
    - [x] Copy `pof-programs-plugin/admin/js/admin.js` to `power-of-families/src/programs/`.

- [x] **Migrate and Convert SASS to CSS:**
    - [x] Convert SASS files from `pof-programs-plugin/public/sass/` to standard CSS.
    - [x] Copy the converted CSS files to `power-of-families/assets/css/pof-programs`.

## 2. Code Integration

- [x] **Integrate Main Plugin File:**
    - [x] Analyze `power-of-families-programs.php` and move its logic into `power-of-families/functions.php` or a new file in `power-of-families/inc/`.

- [x] **Update Autoloader:**
    - [x] Ensure the theme's autoloader in `power-of-families/inc/Autoloader.class.php` correctly includes the new files from `power-of-families/inc/programs`.

- [x] **Update Asset Enqueueing:**
    - [x] Update any `wp_enqueue_style` and `wp_enqueue_script` calls in the theme to point to the new locations of the CSS and JS files.

## 3. Build Process Integration

- [x] **Update `package.json`:**
    - [x] Add any necessary dependencies from the plugin's `package.json` to the theme's `package.json`.
    - [x] Update the build scripts (`build` and `start`) in the theme's `package.json` to compile the new TypeScript/JavaScript and CSS files.

- [x] **Verify Build:**
    - [x] Run `npm install` and `npm run build` to ensure the theme compiles without errors.
    - [x] Run `npm run start` and check that the site is working as expected.

## 4. Cleanup

- [x] **Remove Plugin Directory:**
    - [x] Once all functionality is migrated and verified, delete the `pof-programs-plugin` directory.

- [x] **Review and Remove Unnecessary Files:**
    - [x] Remove any redundant files from the theme that are no longer needed after the migration.

## 5. Validation

- [x] **Manual Testing:**
    - [x] Thoroughly test all functionality that was previously handled by the plugin to ensure it works correctly within the theme.
    - [x] Check for any visual regressions or console errors.

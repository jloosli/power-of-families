# Tasks for `pof-programs-plugin` Migration

This document lists the tasks required to migrate the `pof-programs-plugin` into the `power-of-families` theme.

## 1. File Migration

- [ ] **Create New Directories:**
    - [ ] Create `power-of-families/inc/programs` for PHP files.
    - [ ] Create `power-of-families/src/programs` for TypeScript/JavaScript files.
    - [ ] Create `power-of-families/assets/css/pof-programs` for the converted SASS files.

- [ ] **Migrate PHP Files:**
    - [ ] Move the contents of `pof-programs-plugin/lib/` to `power-of-families/inc/programs/`.

- [ ] **Migrate JavaScript Files:**
    - [ ] Move `pof-programs-plugin/admin/js/admin.js` to `power-of-families/src/programs/`.

- [ ] **Migrate and Convert SASS to CSS:**
    - [ ] Convert SASS files from `pof-programs-plugin/public/sass/` to standard CSS.
    - [ ] Move the converted CSS files to `power-of-families/assets/css/pof-programs`.

## 2. Code Integration

- [ ] **Integrate Main Plugin File:**
    - [ ] Analyze `power-of-families-programs.php` and move its logic into `power-of-families/functions.php` or a new file in `power-of-families/inc/`.

- [ ] **Update Autoloader:**
    - [ ] Ensure the theme's autoloader in `power-of-families/inc/Autoloader.class.php` correctly includes the new files from `power-of-families/inc/programs`.

- [ ] **Update Asset Enqueueing:**
    - [ ] Update any `wp_enqueue_style` and `wp_enqueue_script` calls in the theme to point to the new locations of the CSS and JS files.

## 3. Build Process Integration

- [ ] **Update `package.json`:**
    - [ ] Add any necessary dependencies from the plugin's `package.json` to the theme's `package.json`.
    - [ ] Update the build scripts (`build` and `start`) in the theme's `package.json` to compile the new TypeScript/JavaScript and CSS files.

- [ ] **Verify Build:**
    - [ ] Run `npm install` and `npm run build` to ensure the theme compiles without errors.
    - [ ] Run `npm run start` and check that the site is working as expected.

## 4. Cleanup

- [ ] **Remove Plugin Directory:**
    - [ ] Once all functionality is migrated and verified, delete the `pof-programs-plugin` directory.

- [ ] **Review and Remove Unnecessary Files:**
    - [ ] Remove any redundant files from the theme that are no longer needed after the migration.

## 5. Validation

- [ ] **Manual Testing:**
    - [ ] Thoroughly test all functionality that was previously handled by the plugin to ensure it works correctly within the theme.
    - [ ] Check for any visual regressions or console errors.

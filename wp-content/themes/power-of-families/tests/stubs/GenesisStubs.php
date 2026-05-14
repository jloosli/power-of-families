<?php

/**
 * Genesis Framework function stubs for unit testing.
 *
 * Allows theme PHP logic to be exercised without the Genesis parent theme
 * present in the test environment. Each stub provides the minimal behaviour
 * the theme code depends on. Stubs that are used as hook callbacks (e.g.
 * genesis_do_nav) are no-ops; stubs that return data return safe defaults.
 *
 * Every stub is guarded with function_exists() so this file is safe to load
 * even when Genesis (or another consumer that defines these symbols) is
 * present.
 *
 * @package Power_Of_Families
 */

// Prevent ThemeBootstrap from loading Genesis's lib/init.php.
if ( ! defined( 'PHPUNIT_RUNNING' ) ) {
    define( 'PHPUNIT_RUNNING', true );
}

if ( ! function_exists( 'genesis_register_sidebar' ) ) {
    /**
     * Register a Genesis sidebar/widget area.
     * Delegates to register_sidebar() so tests can exercise widget areas
     * (e.g. by activating them via wp_set_sidebars_widgets()).
     *
     * @param array $args Sidebar arguments.
     */
    function genesis_register_sidebar( $args = [] ) {
        return register_sidebar( $args );
    }
}

if ( ! function_exists( 'genesis_get_config' ) ) {
    /**
     * Return a theme config array by name.
     * Returns the real theme-supports values so register_theme_support() can be
     * tested without loading Genesis.
     *
     * @param string $config_name Config file name (without .php extension).
     * @return array
     */
    function genesis_get_config( $config_name = '' ) {
        if ( 'theme-supports' === $config_name ) {
            return [
                'genesis-responsive-viewport' => true,
                'genesis-footer-widgets'      => 3,
                'genesis-menus'               => [
                    'primary'   => 'Primary Navigation Menu',
                    'secondary' => 'Secondary Navigation Menu',
                    'tertiary'  => 'Footer Navigation Menu',
                ],
            ];
        }
        return [];
    }
}

if ( ! function_exists( 'genesis_html5' ) ) {
    /**
     * Return whether the theme declares HTML5 support for a given feature.
     *
     * @param string $feature Feature to check.
     * @return bool
     */
    function genesis_html5( $feature = '' ) {
        return true;
    }
}

if ( ! function_exists( 'genesis_get_custom_field' ) ) {
    /**
     * Return the value of a Genesis custom field on a post.
     *
     * @param string   $field   Field name.
     * @param int|null $post_id Post ID, or null for current post.
     * @return string
     */
    function genesis_get_custom_field( $field, $post_id = null ) {
        return '';
    }
}

if ( ! function_exists( 'genesis_get_option' ) ) {
    /**
     * Return a Genesis option value.
     *
     * @param string      $key       Option key.
     * @param string|null $setting   Settings field name.
     * @param bool        $use_cache Whether to use the option cache.
     * @return string
     */
    function genesis_get_option( $key, $setting = null, $use_cache = true ) {
        return '';
    }
}

if ( ! function_exists( 'genesis_search_form' ) ) {
    /**
     * Return a Genesis search form HTML string.
     *
     * @param bool   $hidden  Whether to return a hidden search form.
     * @param string $context Context for the form.
     * @return string
     */
    function genesis_search_form( $hidden = false, $context = '' ) {
        return '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">'
            . '<input type="search" name="s" value="" />'
            . '</form>';
    }
}

if ( ! function_exists( 'genesis_widget_area' ) ) {
    /**
     * Output a registered Genesis widget area.
     * Mirrors real Genesis: bail when the sidebar isn't active so tests
     * exercise the same code path as production. Tests that need wrappers
     * to render must register the sidebar AND activate it (e.g. with
     * wp_set_sidebars_widgets()).
     *
     * @param string $id   Widget area ID.
     * @param array  $args Output arguments (before, after).
     * @return bool
     */
    function genesis_widget_area( $id, $args = [] ) {
        if ( ! $id || ! is_active_sidebar( $id ) ) {
            return false;
        }
        echo isset( $args['before'] ) ? $args['before'] : '';
        dynamic_sidebar( $id );
        echo isset( $args['after'] ) ? $args['after'] : '';
        return true;
    }
}

if ( ! function_exists( 'genesis_do_author_box_single' ) ) {
    /**
     * Output the Genesis author box on single posts.
     * No-op; used only as a hook callback.
     */
    function genesis_do_author_box_single() {}
}

if ( ! function_exists( 'genesis_do_footer' ) ) {
    /**
     * Output the Genesis default footer.
     * No-op; the theme replaces this with its own footer.
     */
    function genesis_do_footer() {}
}

if ( ! function_exists( 'genesis_do_nav' ) ) {
    /**
     * Output the Genesis primary navigation menu.
     * No-op; the theme relocates this hook.
     */
    function genesis_do_nav() {}
}

if ( ! function_exists( 'genesis_do_post_image' ) ) {
    /**
     * Output the post featured image in Genesis entry markup.
     * No-op; the theme repositions this hook.
     */
    function genesis_do_post_image() {}
}

if ( ! function_exists( 'genesis_post_meta' ) ) {
    /**
     * Output the Genesis post meta footer.
     * No-op; the theme removes this hook.
     */
    function genesis_post_meta() {}
}

if ( ! function_exists( '__genesis_return_full_width_content' ) ) {
    /**
     * Return the Genesis full-width-content layout identifier.
     * Used as a filter callback on genesis_pre_get_option_site_layout.
     *
     * @return string
     */
    function __genesis_return_full_width_content() {
        return 'full-width-content';
    }
}

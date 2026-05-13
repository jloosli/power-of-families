<?php

namespace PowerOfFamilies;

class ThemeBootstrap
{
    public function __construct()
    {
        if (!defined('PHPUNIT_RUNNING')) {
            require_once get_template_directory() . '/lib/init.php';
        }

        $this->register_theme_support();

        add_theme_support('genesis-structural-wraps', ['header', 'menu-secondary', 'footer-widgets', 'footer']);

        add_image_size('archive', 170, 170, true);
        add_image_size('ubermenu', 137, 137, true);
        add_image_size('sidebar', 300, 300, true);
        add_image_size('featured-posts', 60, 60, true);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 0);

        $this->hideAdminBarFromSubscribers(wp_get_current_user());
    }

    public function register_theme_support(): void
    {
        $theme_supports = genesis_get_config('theme-supports');

        foreach ($theme_supports as $feature => $args) {
            add_theme_support($feature, $args);
        }
    }

    public function enqueue_assets(): void
    {
        $js_asset = require get_theme_file_path('dist/main.ts.asset.php');
        wp_enqueue_script(
            'pof_theme_scripts',
            get_stylesheet_directory_uri() . '/dist/main.ts.js',
            $js_asset['dependencies'],
            $js_asset['version']
        );

        wp_enqueue_style('custom-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Playfair+Display:wght@400;700&display=swap', false);
        wp_enqueue_style(
            'power_of_families_styles',
            get_stylesheet_directory_uri() . '/dist/main.ts.css',
            [],
            $js_asset['version']
        );
    }

    public function hideAdminBarFromSubscribers(?\WP_User $current_user = null): bool
    {
        if ($current_user && $current_user->exists()) {
            if (in_array('subscriber', $current_user->roles)) {
                show_admin_bar(false);
                return false;
            }
        }
        return true;
    }
}

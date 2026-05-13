<?php

namespace PowerOfFamilies\POF;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Main class for managing the Power of Families plugin programs.
 *
 * Handles registration of custom post types and taxonomies, loading of settings,
 * and enqueuing of admin scripts. This class serves as the core of the migrated
 * Power of Families plugin, providing integration points and administrative functionality.
 *
 * @since 1.0.0
 */
class PowerOfFamiliesPrograms
{

    /**
     * The token.
     * @since   1.0.0
     */
    public const TOKEN = 'Power_of_Families_Programs';

    /**
     * Settings class object
     * @var     Settings
     * @access  public
     * @since   1.0.0
     */
    public ?Settings $settings = null;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Renderer for admin settings fields; null outside admin context.
     */
    public ?AdminAPI $admin = null;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     *
     */
    public function __construct()
    {
        // Renderer must exist before Settings so register_settings can bind to it.
        if (is_admin()) {
            $this->admin = new AdminAPI();
        }

        $this->settings = new Settings(self::TOKEN, $this->admin);

        add_action('admin_enqueue_scripts', [$this, 'admin_register_scripts'], 10, 1);
    }

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_register_scripts(): void
    {
        $js_asset = include get_stylesheet_directory() . '/dist/admin.ts.asset.php';
        $url = get_stylesheet_directory_uri() . '/dist/admin.ts.js';
        wp_enqueue_script(
            self::TOKEN . '-admin',
            $url, $js_asset['dependencies'],
            $js_asset['version']
        );

    }

}
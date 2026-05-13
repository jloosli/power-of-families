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
     * @var bool|AdminAPI
     * @access public
     * @since 3.0.0
     */
    public bool|AdminAPI $admin = false;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     *
     */
    public function __construct()
    {
        // Load Settings
        $this->settings = new Settings($this);

        // Load admin JS & CSS
        add_action('admin_enqueue_scripts', [$this, 'admin_register_scripts'], 10, 1);

        // Load API for generic admin functions
        if (is_admin()) {
            $this->admin = new AdminAPI();
        }
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
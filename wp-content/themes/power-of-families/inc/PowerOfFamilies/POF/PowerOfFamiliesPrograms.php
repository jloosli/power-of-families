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
     * Wrapper function to register a new post type
     *
     * @param  string $post_type Post type name
     * @param  string $plural Post type item plural name
     * @param  string $single Post type item single name
     * @param  string $description Description of post type
     *
     * @return object              Post type class object
     */
    public function register_post_type($post_type = '', $plural = '', $single = '', $description = ''): ?object
    {

        if (!$post_type || !$plural || !$single) {
            return null;
        }

        $post_type = new Post_Type($post_type, $plural, $single, $description);

        return $post_type;
    }

    /**
     * Wrapper function to register a new taxonomy
     *
     * @param  string $taxonomy Taxonomy name
     * @param  string $plural Taxonomy single name
     * @param  string $single Taxonomy plural name
     * @param  array $post_types Post types to which this taxonomy applies
     *
     * @return object             Taxonomy class object
     */
    public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array()): ?object
    {

        if (!$taxonomy || !$plural || !$single) {
            return null;
        }

        $taxonomy = new Taxonomy($taxonomy, $plural, $single, $post_types);

        return $taxonomy;
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

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone(): void
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), '1.0.0');
    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup(): void
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), '1.0.0');
    } // End __wakeup ()



}
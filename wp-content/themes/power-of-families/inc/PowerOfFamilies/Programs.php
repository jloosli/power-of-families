<?php

namespace PowerOfFamilies;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Composition root for the Power of Families admin programs feature.
 *
 * Wires the admin {@see Settings} screen and its {@see FieldRenderer},
 * cascades `register()` to both, and enqueues the admin script bundle.
 * Per-program post types and taxonomies are registered by the individual
 * program classes loaded from `inc/programs/`, not by this class.
 *
 * @since 1.0.0
 */
class Programs implements HookRegistrar
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
    public ?FieldRenderer $fieldRenderer = null;

    public function __construct()
    {
        // Renderer must exist before Settings so register_settings can bind to it.
        if (is_admin()) {
            $this->fieldRenderer = new FieldRenderer();
        }

        $this->settings = new Settings(self::TOKEN, $this->fieldRenderer);
    }

    public function register(): void
    {
        $this->fieldRenderer?->register();
        $this->settings->register();
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
<?php

namespace POF;

if (!defined('ABSPATH')) {
    exit;
}


class Power_of_Families_Programs
{

    /**
     * Settings class object
     * @var     Settings
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $token;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $plugin_dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The programs directory.
     * @var     string
     * @access  public
     * @since   2.0.0
     */
    public $programs_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * The Admin Directory
     * @var string
     * @since 3.0.0
     */
    public $admin_dir;

    /**
     * The Admin url
     * @var string
     * @access public
     * @since 3.0.0
     */
    public $admin_url;


    /**
     * The Public directory
     * @var string
     */
    public $public_dir;

    /**
     * @var string
     * @access public
     * @since 3.0.0
     */
    public $front_url;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     *
     */
    public function __construct($plugin_dir)
    {
        $this->_version = self::getVersion();
        $this->token = 'Power_of_Families_Programs';

        // Load Settings
        $this->settings = new Settings($this);
        $this->plugin_dir = $plugin_dir;

        register_activation_hook(__FILE__, array($this, 'install'));

        // Load frontend JS & CSS
        add_action('wp_enqueue_scripts', array($this, 'register_styles'), 10);
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 10);

        // Load admin JS & CSS
        add_action('admin_enqueue_scripts', array($this, 'admin_register_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_register_styles'), 10, 1);

        // Load API for generic admin functions
        if (is_admin()) {
            $this->admin = new Admin_API();
        }

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action('init', array($this, 'load_localisation'), 0);
    } // End __construct ()

    public static function getVersion()
    {
        $version = '1.0.0';
        $version_file = dirname(__FILE__) . '/../../version.txt';
        if (file_exists($version_file) && is_readable($version_file)) {
            $fh = fopen($version_file, 'r');
            while (!feof($fh)) {
                $line = fgets($fh);
                if ($line) {
                    $version = $line;
                }
            }
            fclose($fh);
        }
        return $version;
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
    public function register_post_type($post_type = '', $plural = '', $single = '', $description = '')
    {

        if (!$post_type || !$plural || !$single) {
            return;
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
    public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array())
    {

        if (!$taxonomy || !$plural || !$single) {
            return;
        }

        $taxonomy = new Taxonomy($taxonomy, $plural, $single, $post_types);

        return $taxonomy;
    }

    /**
     * Load frontend CSS.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function register_styles()
    {
        $url = plugins_url('public/css/public.css', $this->plugin_dir . 'bob');
        wp_register_style($this->token . '-frontend', $url, array(),
            $this->_version);
    }

    /**
     * Load frontend Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function register_scripts()
    {
        $url = plugins_url('public/js/public.min.js', $this->plugin_dir . 'bob');
        wp_register_script($this->token . '-frontend',
            $url, ['jquery'],
            $this->_version, true);
        wp_register_script('requirejs',
            'https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.5/require.min.js',
            [], null,
            true);
    }

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_register_styles()
    {
        $url = plugins_url('admin/css/admin.css', $this->plugin_dir . 'bob');
        wp_register_style($this->token . '-admin', $url, array(),
            $this->_version);
        wp_enqueue_style($this->token . '-admin');
    } // End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_register_scripts()
    {
        $url = plugins_url('admin/js/admin.js', $this->plugin_dir . 'bob');
        wp_register_script($this->token . '-admin',
            $url, array(),
            $this->_version);
        wp_enqueue_script($this->token . '-admin');
//        wp_register_script('requirejs',
//            'https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.5/require.min.js',
//            [], null,
//            true);
    } // End admin_enqueue_scripts ()

    /**
     * Load plugin localisation
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_localisation()
    {
        load_plugin_textdomain('power-of-families-programs', false, $this->plugin_dir . '/lang/');
    } // End load_localisation ()

    /**
     * Load plugin textdomain
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain()
    {
        $domain = 'power-of-families-programs';

        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, $this->plugin_dir . '/lang/');
    } // End load_plugin_textdomain ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    } // End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install()
    {
        $this->_log_version_number();
    } // End install ()

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number()
    {
        update_option($this->token . '_version', $this->_version);
    } // End _log_version_number ()

}
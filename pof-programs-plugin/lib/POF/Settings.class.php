<?php

namespace POF;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{

    /**
     * The single instance of Power_of_Moms_Programs_Settings.
     * @var    object
     * @access   private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The main plugin object.
     * @var    Power_of_Families_Programs
     * @access   public
     * @since    1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    /**
     * Active programs
     * @var array
     * @access public
     * @since  2.0.0
     */
    public $programs = array();


    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'pof_';

        // Initialise settings
        add_action('init', array($this, 'init_settings'), 11);

        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings page to menu
        add_action('admin_menu', array($this, 'add_menu_item'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(Power_of_Families_Programs::class), array(
            $this,
            'add_settings_link'
        ));

        // Load up active programs
        $this->programs = $this->loadActivePrograms();

    }

    /**
     * Initialise settings
     * @return void
     */
    public function init_settings()
    {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu
     * @return void
     */
    public function add_menu_item()
    {
        $page = add_options_page(
            __('POF Settings', 'power-of-families-programs'),
            __('POF Settings', 'power-of-families-programs'),
            'manage_options',
            $this->parent->token . '_settings', array(
            $this,
            'settings_page'
        ));
//        add_action('admin_print_styles-' . $page, array($this, 'settings_assets'));
    }

    /**
     * Load settings JS & CSS
     * @return void
     */
    public function settings_assets()
    {

        // We're including the farbtastic script & styles here because they're needed for the colour picker
        // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');

        // We're including the WP media scripts here because they're needed for the image upload field
        // If you're not including an image upload then you can leave this function call out
        wp_enqueue_media();

        wp_register_script($this->parent->token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array(
            'farbtastic',
            'jquery'
        ), '1.0.0');
        wp_enqueue_script($this->parent->token . '-settings-js');
    }

    /**
     * Add settings link to plugin list table
     *
     * @param  array $links Existing links
     *
     * @return array        Modified links
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=' . $this->parent->token . '_settings">' . __('Settings', 'power-of-families-programs') . '</a>';
        array_push($links, $settings_link);

        return $links;
    }

    /**
     * Get available programs from the programs directory
     * @return array
     */
    private function getAvailablePrograms()
    {
        // Get the files
        $programsDirectory = dirname(__FILE__) . '/Programs';
        $files = scandir($programsDirectory);

        // Return only the classes
        $files = array_filter($files, function ($file) {
            return strstr($file, 'class');
        });

        // Format the programs
        $programs = [];
        foreach ($files as $file) {
            $key = str_replace(".class.php", "", $file);
            $program = ucwords(str_replace("_", " ", $key));
            if (strpos($key, "_Settings")) {
                $programs[str_replace("_Settings", "", $key)]['has-settings'] = true;
            } else {
                $programs[$key]['name'] = $program;
                if (empty($programs[$key]['has-settings'])) {
                    $programs[$key]['has-settings'] = false;
                }
            }
        }

        return $programs;
    }

    public function getActivePrograms()
    {
        return get_option('pof_active_programs', []);
    }

    public function loadActivePrograms()
    {
        $programs = [];
        foreach ($this->getActivePrograms() as $program) {
            if (array_key_exists($program, $this->getAvailablePrograms())) {
                $ClassName = '\\POF\\Programs\\' . $program;
                $programs[$program] = new $ClassName($this->parent);
            }
        }

        return $programs;
    }


    /**
     * Build settings fields
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields()
    {

        $availablePrograms = array_map(function ($program) {
            return $program['name'];
        },
            $this->getAvailablePrograms()
        );
        $settings['standard'] = array(
            'title' => __('Active Programs', 'power-of-families-programs'),
            'description' => __('Select all the active programs you want to have active.', 'power-of-families-programs'),
            'fields' => array(
                array(
                    'id' => 'active_programs',
                    'label' => __('Active Programs', 'power-of-families-programs'),
                    'description' => __('Select the programs you want to be active.', 'power-of-families-programs'),
                    'type' => 'checkbox_multi',
                    'options' => $availablePrograms,
                    'default' => array()
                )
            )
        );

//        $settings['extra'] = array(
//            'title'       => __( 'Extra', 'power-of-families-programs' ),
//            'description' => __( 'These are some extra input fields that maybe aren\'t as common as the others.', 'power-of-families-programs' ),
//            'fields'      => array(
//                array(
//                    'id'          => 'number_field',
//                    'label'       => __( 'A Number', 'power-of-families-programs' ),
//                    'description' => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'power-of-families-programs' ),
//                    'type'        => 'number',
//                    'default'     => '',
//                    'placeholder' => __( '42', 'power-of-families-programs' )
//                ),
//                array(
//                    'id'          => 'colour_picker',
//                    'label'       => __( 'Pick a colour', 'power-of-families-programs' ),
//                    'description' => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'power-of-families-programs' ),
//                    'type'        => 'color',
//                    'default'     => '#21759B'
//                ),
//                array(
//                    'id'          => 'an_image',
//                    'label'       => __( 'An Image', 'power-of-families-programs' ),
//                    'description' => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'power-of-families-programs' ),
//                    'type'        => 'image',
//                    'default'     => '',
//                    'placeholder' => ''
//                ),
//                array(
//                    'id'          => 'multi_select_box',
//                    'label'       => __( 'A Multi-Select Box', 'power-of-families-programs' ),
//                    'description' => __( 'A standard multi-select box - the saved data is stored as an array.', 'power-of-families-programs' ),
//                    'type'        => 'select_multi',
//                    'options'     => array( 'linux' => 'Linux', 'mac' => 'Mac', 'windows' => 'Windows' ),
//                    'default'     => array( 'linux' )
//                )
//            )
//        );

        foreach ($this->getActivePrograms() as $program) {
            $programs = $this->getAvailablePrograms();
            if ($programs[$program]['has-settings']) {
                $theProgramSettings = $this->programs[$program]->getSettingsInstance();
                $settings[$program] = $theProgramSettings->getSettings();
            }
        }

        $settings = apply_filters($this->parent->token . '_settings_fields', $settings);

        return $settings;
    }

    /**
     * Register plugin settings
     * @return void
     */
    public function register_settings()
    {
        if (is_array($this->settings)) {

            // Check posted/selected tab
            $current_section = '';
            if (isset($_POST['tab']) && $_POST['tab']) {
                $current_section = $_POST['tab'];
            } else {
                if (isset($_GET['tab']) && $_GET['tab']) {
                    $current_section = $_GET['tab'];
                }
            }

            foreach ($this->settings as $section => $data) {

                if ($current_section && $current_section != $section) {
                    continue;
                }

                // Add section to page
                add_settings_section($section, $data['title'], array(
                    $this,
                    'settings_section'
                ), $this->parent->token . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field
                    $option_name = $this->base . $field['id'];
                    register_setting($this->parent->token . '_settings', $option_name, $validation);

                    // Add field to page
                    add_settings_field($field['id'], $field['label'], array(
                        $this->parent->admin,
                        'display_field'
                    ), $this->parent->token . '_settings', $section, array(
                        'field' => $field,
                        'prefix' => $this->base
                    ));
                }

                if (!$current_section) {
                    break;
                }
            }
        }
    }

    public function settings_section($section)
    {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo $html;
    }

    /**
     * Load settings page content
     * @return void
     */
    public function settings_page()
    {

        // Build page HTML
        $html = '<div class="wrap" id="' . $this->parent->token . '_settings">' . "\n";
        $html .= '<h2>' . __('POF Settings', 'power-of-families-programs') . '</h2>' . "\n";

        $tab = '';
        if (isset($_GET['tab']) && $_GET['tab']) {
            $tab .= $_GET['tab'];
        }

        // Show page tabs
        if (is_array($this->settings) && 1 < count($this->settings)) {

            $html .= '<h2 class="nav-tab-wrapper">' . "\n";

            $c = 0;
            foreach ($this->settings as $section => $data) {

                // Set tab class
                $class = 'nav-tab';
                if (!isset($_GET['tab'])) {
                    if (0 == $c) {
                        $class .= ' nav-tab-active';
                    }
                } else {
                    if (isset($_GET['tab']) && $section == $_GET['tab']) {
                        $class .= ' nav-tab-active';
                    }
                }

                // Set tab link
                $tab_link = add_query_arg(array('tab' => $section));
                if (isset($_GET['settings-updated'])) {
                    $tab_link = remove_query_arg('settings-updated', $tab_link);
                }

                // Output tab
                $html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

                ++$c;
            }

            $html .= '</h2>' . "\n";
        }

        $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

        // Get settings fields
        ob_start();
        settings_fields($this->parent->token . '_settings');
        do_settings_sections($this->parent->token . '_settings');
        $html .= ob_get_clean();

        $html .= '<p class="submit">' . "\n";
        $html .= '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />' . "\n";
        $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Save Settings', 'power-of-families-programs')) . '" />' . "\n";
        $html .= '</p>' . "\n";
        $html .= '</form>' . "\n";
        $html .= '</div>' . "\n";
        echo $html;
        do_action('pof_programs_settings_admin_end');
    }

    /**
     * Main Power_of_Moms_Programs_Settings Instance
     *
     * Ensures only one instance of Power_of_Moms_Programs_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see   Power_of_Moms_Programs()
     * @return Main Power_of_Moms_Programs_Settings instance
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }

        return self::$_instance;
    } // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->parent->_version);
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->parent->_version);
    } // End __wakeup()

}
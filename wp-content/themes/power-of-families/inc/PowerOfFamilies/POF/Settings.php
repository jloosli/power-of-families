<?php

namespace PowerOfFamilies\POF;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{

    /**
     * The main plugin object.
     * @var    Power_of_Families_Programs
     * @access   public
     * @since    1.0.0
     */
    public ?PowerOfFamiliesPrograms $parent = null;

    /**
     * Prefix for plugin settings.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public string $base = '';

    /**
     * Available settings for plugin.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public array $settings = [];

    /**
     * Active programs
     * @var array
     * @access public
     * @since  2.0.0
     */
    public array $programs = [];


    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'pof_';

        // Initialise settings
        add_action('init', [$this, 'init_settings'], 11);

        // Register plugin settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add settings page to menu
        add_action('admin_menu', [$this, 'add_menu_item']);

        // Load up active programs
        $this->programs = $this->loadActivePrograms();

    }

    /**
     * Initialise settings
     * @return void
     */
    public function init_settings(): void
    {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu
     * @return void
     */
    public function add_menu_item(): void
    {
        $page = add_options_page(
            __('POF Settings', 'power-of-families-programs'),
            __('POF Settings', 'power-of-families-programs'),
            'manage_options',
            $this->parent::TOKEN . '_settings',
            [$this, 'settings_page']
        );
    }

    /**
     * Get available programs from the programs directory
     * @return array
     */
    private function getAvailablePrograms(): array
    {
        return [
            'Affiliate_Linker' => ['name' => 'Affiliate Linker', 'has-settings' => true],
            'My_Programs'      => ['name' => 'My Programs',      'has-settings' => false],
        ];
    }

    public function getActivePrograms(): array
    {
        return get_option('pof_active_programs', []);
    }

    public function loadActivePrograms(): array
    {
        $allowed = [ 'Affiliate_Linker', 'My_Programs' ];
        $programs = [];
        foreach ($this->getActivePrograms() as $program) {
            if (
                in_array( $program, $allowed, true ) &&
                array_key_exists( $program, $this->getAvailablePrograms() )
            ) {
                $ClassName = '\PowerOfFamilies\POF\Programs\\' . $program;
                $programs[$program] = new $ClassName($this->parent);
            }
        }
        return $programs;
    }


    /**
     * Build settings fields
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields(): array
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

        foreach ($this->getActivePrograms() as $program) {
            $programs = $this->getAvailablePrograms();
            if (isset($programs[$program]) && $programs[$program]['has-settings']) {
                $theProgramSettings = $this->programs[$program]->getSettingsInstance();
                $settings[$program] = $theProgramSettings->getSettings();
            }
        }

        $settings = apply_filters($this->parent::TOKEN . '_settings_fields', $settings);

        return $settings;
    }

    /**
     * Register plugin settings
     * @return void
     */
    public function register_settings(): void
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
                add_settings_section($section, $data['title'], [$this, 'settings_section'], $this->parent::TOKEN . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field
                    $option_name = $this->base . $field['id'];
                    register_setting($this->parent::TOKEN . '_settings', $option_name, $validation);

                    // Add field to page
                    add_settings_field($field['id'], $field['label'], array(
                        $this->parent->admin,
                        'display_field'
                    ), $this->parent::TOKEN . '_settings', $section, array(
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

    public function settings_section(array $section): void
    {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo $html;
    }

    /**
     * Load settings page content
     * @return void
     */
    public function settings_page(): void
    {

        // Build page HTML
        $html = '<div class="wrap" id="' . $this->parent::TOKEN . '_settings">' . "\n";
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
        settings_fields($this->parent::TOKEN . '_settings');
        do_settings_sections($this->parent::TOKEN . '_settings');
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

}

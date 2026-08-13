<?php

namespace PowerOfFamilies;

if (!defined('ABSPATH')) {
    exit;
}

class Settings implements HookRegistrar
{

    /**
     * Settings page slug / option prefix root.
     */
    public string $token;

    /**
     * Renderer used as the callback for add_settings_field.
     */
    public ?FieldRenderer $renderer;

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


    public function __construct(string $token, ?FieldRenderer $renderer = null)
    {
        $this->token = $token;
        $this->renderer = $renderer;
        $this->base = 'pof_';
        $this->programs = $this->loadActivePrograms();
    }

    public function register(): void
    {
        add_action('init', [$this, 'init_settings'], 11);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_menu_item']);

        foreach ($this->programs as $program) {
            if ($program instanceof HookRegistrar) {
                $program->register();
            }
        }
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
            $this->token . '_settings',
            [$this, 'settings_page']
        );
    }

    /**
     * Get available programs from the programs directory
     * @return array
     */
    private function getAvailablePrograms(): array
    {
        // Outer keys match values stored in the pof_active_programs option
        // and must stay stable for backward compatibility; the `class` field
        // is the actual PHP class name in the PowerOfFamilies\Programs namespace.
        return [
            'Affiliate_Linker' => ['class' => 'AffiliateLinker', 'name' => 'Affiliate Linker', 'has-settings' => true],
            'My_Programs'      => ['class' => 'MyPrograms',      'name' => 'My Programs',      'has-settings' => false],
        ];
    }

    public function getActivePrograms(): array
    {
        return get_option('pof_active_programs', []);
    }

    public function loadActivePrograms(): array
    {
        $available = $this->getAvailablePrograms();
        $programs = [];
        foreach ($this->getActivePrograms() as $program) {
            if (array_key_exists($program, $available)) {
                $ClassName = '\PowerOfFamilies\Programs\\' . $available[$program]['class'];
                // Some program classes accept the token (e.g. for script handle
                // namespacing), others take no constructor args. Inspect the
                // ctor and pass the token only when it's expected so we don't
                // emit "Too many arguments" warnings under PHP 8.4+.
                $ctor = ( new \ReflectionClass( $ClassName ) )->getConstructor();
                $programs[$program] = ( $ctor && $ctor->getNumberOfParameters() > 0 )
                    ? new $ClassName( $this->token )
                    : new $ClassName();
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

        $settings = apply_filters($this->token . '_settings_fields', $settings);

        return $settings;
    }

    /**
     * Register plugin settings
     * @return void
     */
    /**
     * Read and validate the requested settings tab from $_POST/$_GET.
     *
     * Returns the section key only if it matches a known settings section
     * (defined in $this->settings). Empty string otherwise. Prevents
     * arbitrary user input from flowing into add_settings_section() and
     * the rendered nav.
     */
    private function get_current_tab(): string
    {
        $allowed = is_array($this->settings) ? array_keys($this->settings) : [];

        $tab = '';
        if (!empty($_POST['tab'])) {
            $tab = sanitize_text_field(wp_unslash($_POST['tab']));
        } elseif (!empty($_GET['tab'])) {
            $tab = sanitize_text_field(wp_unslash($_GET['tab']));
        }

        return in_array($tab, $allowed, true) ? $tab : '';
    }

    public function register_settings(): void
    {
        if (is_array($this->settings)) {

            $current_section = $this->get_current_tab();

            foreach ($this->settings as $section => $data) {

                if ($current_section && $current_section != $section) {
                    continue;
                }

                // Add section to page
                add_settings_section($section, $data['title'], [$this, 'settings_section'], $this->token . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field
                    $option_name = $this->base . $field['id'];
                    register_setting($this->token . '_settings', $option_name, $validation);

                    // Add field to page
                    add_settings_field($field['id'], $field['label'], array(
                        $this->renderer,
                        'display_field'
                    ), $this->token . '_settings', $section, array(
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
        $html = '<div class="wrap" id="' . $this->token . '_settings">' . "\n";
        $html .= '<h2>' . __('POF Settings', 'power-of-families-programs') . '</h2>' . "\n";

        $tab = $this->get_current_tab();

        // Show page tabs
        if (is_array($this->settings) && 1 < count($this->settings)) {

            $html .= '<h2 class="nav-tab-wrapper">' . "\n";

            $c = 0;
            foreach ($this->settings as $section => $data) {

                $class = 'nav-tab';
                if ('' === $tab) {
                    if (0 === $c) {
                        $class .= ' nav-tab-active';
                    }
                } elseif ($section === $tab) {
                    $class .= ' nav-tab-active';
                }

                // Set tab link. add_query_arg() with no URL argument builds
                // from $_SERVER['REQUEST_URI'], which WordPress does not
                // escape — esc_url() below is what makes it safe to output.
                $tab_link = add_query_arg(array('tab' => $section));
                if (isset($_GET['settings-updated'])) {
                    $tab_link = remove_query_arg('settings-updated', $tab_link);
                }

                // Output tab
                $html .= '<a href="' . esc_url($tab_link) . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

                ++$c;
            }

            $html .= '</h2>' . "\n";
        }

        $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

        // Get settings fields
        ob_start();
        settings_fields($this->token . '_settings');
        do_settings_sections($this->token . '_settings');
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

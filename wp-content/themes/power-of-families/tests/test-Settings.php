<?php

/**
 * Tests for the Settings class.
 *
 * @package Power_Of_Families
 */
class test_Settings extends WP_UnitTestCase {

    private ?\PowerOfFamilies\Settings $settings;

    protected function setUp(): void {
        parent::setUp();
        require_once ABSPATH . 'wp-admin/includes/template.php';
        $this->settings = new \PowerOfFamilies\Settings( 'pof' );

        // Two sections, so settings_page() renders the tab nav at all.
        $this->settings->settings = [
            'standard' => [ 'title' => 'Standard', 'description' => 'First', 'fields' => [] ],
            'extra'    => [ 'title' => 'Extra', 'description' => 'Second', 'fields' => [] ],
        ];
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_URI'], $_GET['tab'] );
        $this->settings = null;
        parent::tearDown();
    }

    private function render(): string {
        ob_start();
        $this->settings->settings_page();
        return ob_get_clean();
    }

    public function test_tab_links_are_rendered_for_each_section() {
        $_SERVER['REQUEST_URI'] = '/wp-admin/options-general.php?page=pof_settings';

        $html = $this->render();

        $this->assertStringContainsString( 'tab=standard', $html );
        $this->assertStringContainsString( 'tab=extra', $html );
    }

    /**
     * add_query_arg() with no URL argument builds from $_SERVER['REQUEST_URI'].
     * It re-encodes the query string, but passes the path segment through
     * verbatim — so a payload placed *before* the "?" reaches the href raw and
     * breaks out of the attribute unless esc_url() is applied. Putting the
     * payload in the query string instead would not catch this regression.
     */
    public function test_tab_link_does_not_break_out_of_href_attribute() {
        $_SERVER['REQUEST_URI'] = '/wp-admin/"><script>alert(1)</script>/options-general.php?page=pof_settings';

        $html = $this->render();

        $this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
        $this->assertStringNotContainsString( '"><script', $html );
    }

    /**
     * get_current_tab() whitelists against known sections, so an unknown tab
     * never reaches the rendered nav or the hidden form field.
     */
    public function test_unknown_tab_is_rejected() {
        $_SERVER['REQUEST_URI'] = '/wp-admin/options-general.php?page=pof_settings';
        $_GET['tab']            = 'not-a-real-section';

        $html = $this->render();

        $this->assertStringNotContainsString( 'value="not-a-real-section"', $html );
        $this->assertStringContainsString( 'name="tab" value=""', $html );
    }
}

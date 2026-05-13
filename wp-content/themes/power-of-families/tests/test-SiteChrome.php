<?php

/**
 * Tests for the SiteChrome class.
 *
 * @package Power_Of_Families
 */
class test_SiteChrome extends WP_UnitTestCase {

    private ?\PowerOfFamilies\SiteChrome $chrome;

    protected function setUp(): void {
        parent::setUp();
        $this->chrome = new \PowerOfFamilies\SiteChrome();
    }

    protected function tearDown(): void {
        wp_set_current_user( 0 );
        $this->chrome = null;
        parent::tearDown();
    }

    public function test_construct_registers_expected_hooks() {
        $this->assertNotFalse( has_action( 'wp_head', [ $this->chrome, 'pre_load_favicon' ] ) );
        $this->assertNotFalse( has_action( 'admin_head', [ $this->chrome, 'pre_load_favicon' ] ) );
        $this->assertNotFalse( has_action( 'genesis_footer', [ $this->chrome, 'footer_copyright' ] ) );
    }

    public function test_pre_load_favicon_outputs_link_tags() {
        ob_start();
        $this->chrome->pre_load_favicon();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'favicon.ico', $output );
        $this->assertStringContainsString( '<link rel="shortcut icon"', $output );
        $this->assertStringContainsString( 'apple-touch-icon', $output );
        $this->assertStringContainsString( 'rel="icon" type="image/png"', $output );
    }

    public function test_footer_copyright_outputs_copyright() {
        ob_start();
        $this->chrome->footer_copyright();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Copyright', $output );
        $this->assertStringContainsString( 'poweroffamilies.com', $output );
        $this->assertStringContainsString( (string) date( 'Y' ), $output );
    }
}

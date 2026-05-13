<?php

/**
 * Tests for the ThemeBootstrap class.
 *
 * @package Power_Of_Families
 */
class test_ThemeBootstrap extends WP_UnitTestCase {

    private ?\PowerOfFamilies\ThemeBootstrap $bootstrap;

    protected function setUp(): void {
        parent::setUp();
        $this->bootstrap = new \PowerOfFamilies\ThemeBootstrap();
        $this->bootstrap->register();
    }

    protected function tearDown(): void {
        wp_set_current_user( 0 );
        $this->bootstrap = null;
        parent::tearDown();
    }

    public function test_construct() {
        $this->assertInstanceOf( \PowerOfFamilies\ThemeBootstrap::class, $this->bootstrap );
    }

    public function test_enqueue_assets_registers_script_and_style() {
        $asset_file = get_theme_file_path( 'dist/main.ts.asset.php' );
        if ( ! file_exists( $asset_file ) ) {
            $this->markTestSkipped( "Theme build artifact missing ({$asset_file}); run `npm run build` to exercise this test." );
        }

        $this->assertFalse( wp_script_is( 'pof_theme_scripts' ) );
        $this->bootstrap->enqueue_assets();

        $this->assertTrue( wp_script_is( 'pof_theme_scripts' ) );
        $this->assertTrue( wp_style_is( 'power_of_families_styles' ) );
    }

    public function test_hideAdminBarFromSubscribers() {
        $default_role_user = $this->factory->user->create_and_get();
        $admin_user        = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
        $subscriber        = $this->factory->user->create_and_get( [ 'role' => 'subscriber' ] );
        $nonexistent_user  = new WP_User( 0 );

        $this->assertFalse( $this->bootstrap->hideAdminBarFromSubscribers( $default_role_user ) );
        $this->assertTrue( $this->bootstrap->hideAdminBarFromSubscribers( $admin_user ) );
        $this->assertFalse( $this->bootstrap->hideAdminBarFromSubscribers( $subscriber ) );

        $this->assertTrue( $this->bootstrap->hideAdminBarFromSubscribers( $nonexistent_user ) );
        $this->assertTrue( $this->bootstrap->hideAdminBarFromSubscribers( null ) );
    }

    public function test_register_theme_support_adds_genesis_features() {
        $this->assertTrue( current_theme_supports( 'genesis-responsive-viewport' ) );

        $footer_widgets = get_theme_support( 'genesis-footer-widgets' );
        $this->assertSame( 3, $footer_widgets[0] );

        $menus = get_theme_support( 'genesis-menus' );
        $this->assertArrayHasKey( 'primary', $menus[0] );
        $this->assertArrayHasKey( 'tertiary', $menus[0] );
    }
}

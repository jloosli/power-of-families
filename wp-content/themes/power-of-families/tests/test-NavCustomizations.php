<?php

/**
 * Tests for the NavCustomizations class.
 *
 * @package Power_Of_Families
 */
class test_NavCustomizations extends WP_UnitTestCase {

    private ?\PowerOfFamilies\NavCustomizations $nav;

    protected function setUp(): void {
        parent::setUp();
        $this->nav = new \PowerOfFamilies\NavCustomizations();
        $this->nav->register();
    }

    protected function tearDown(): void {
        wp_set_current_user( 0 );
        $this->nav = null;
        parent::tearDown();
    }

    public function test_construct_registers_expected_hooks() {
        $this->assertNotFalse( has_action( 'genesis_header_right', 'genesis_do_nav' ) );
        $this->assertNotFalse( has_filter( 'genesis_nav_items', [ $this->nav, 'be_follow_icons' ] ) );
        $this->assertNotFalse( has_filter( 'wp_nav_menu_items', [ $this->nav, 'be_follow_icons' ] ) );
        $this->assertNotFalse( has_action( 'genesis_footer', [ $this->nav, 'footer_menu' ] ) );
    }

    public function test_be_follow_icons_non_primary_location_returns_menu_unchanged() {
        $menu = '<li class="menu-item"><a href="/">Home</a></li>';
        $args = [ 'theme_location' => 'secondary' ];

        $result = $this->nav->be_follow_icons( $menu, $args );

        $this->assertSame( $menu, $result );
    }

    public function test_be_follow_icons_primary_logged_out_appends_login_link() {
        wp_set_current_user( 0 );

        $menu = '<li class="menu-item"><a href="/">Home</a></li>';
        $args = [ 'theme_location' => 'primary' ];

        $result = $this->nav->be_follow_icons( $menu, $args );

        $this->assertStringContainsString( 'login-bar', $result );
        $this->assertStringContainsString( 'Log In', $result );
        $this->assertStringNotContainsString( 'My Account', $result );
    }

    public function test_be_follow_icons_primary_logged_in_appends_account_menu() {
        $user_id = $this->factory->user->create();
        wp_set_current_user( $user_id );

        $menu = '<li class="menu-item"><a href="/">Home</a></li>';
        $args = [ 'theme_location' => 'primary' ];

        $result = $this->nav->be_follow_icons( $menu, $args );

        $this->assertStringContainsString( 'My Account', $result );
        $this->assertStringContainsString( '/my-account/', $result );
        $this->assertStringContainsString( 'Logout', $result );
        $this->assertStringNotContainsString( 'login-bar', $result );
    }
}

<?php

/**
 * Tests for the ThemeSetup class.
 *
 * @package Power_Of_Families
 */
class test_ThemeSetup extends WP_UnitTestCase {

    private ?\PowerOfFamilies\Avanti\ThemeSetup $theme_setup;

    protected function setUp(): void {
        parent::setUp();
        $this->theme_setup = new \PowerOfFamilies\Avanti\ThemeSetup();
    }

    protected function tearDown(): void {
        wp_set_current_user( 0 );
        $this->theme_setup = null;
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Instantiation
    // -------------------------------------------------------------------------

    public function test_construct() {
        $this->assertInstanceOf( \PowerOfFamilies\Avanti\ThemeSetup::class, $this->theme_setup );
    }

    // -------------------------------------------------------------------------
    // Script / style enqueuing
    // -------------------------------------------------------------------------

    public function test_custom_load_styles_and_scripts() {
        // Method `require`s dist/main.ts.asset.php (produced by `npm run build`,
        // gitignored). Skip rather than fatal on a fresh checkout.
        $asset_file = get_theme_file_path( 'dist/main.ts.asset.php' );
        if ( ! file_exists( $asset_file ) ) {
            $this->markTestSkipped( "Theme build artifact missing ({$asset_file}); run `npm run build` to exercise this test." );
        }

        $this->assertFalse( wp_script_is( 'pof_theme_scripts' ) );
        $this->theme_setup->custom_load_styles_and_scripts();

        $this->assertTrue( wp_script_is( 'pof_theme_scripts' ) );
        $this->assertTrue( wp_style_is( 'power_of_families_styles' ) );
    }

    // -------------------------------------------------------------------------
    // Admin bar visibility
    // -------------------------------------------------------------------------

    public function test_hideAdminBarFromSubscribers() {
        $default_role_user = $this->factory->user->create_and_get();
        $admin_user        = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
        $subscriber        = $this->factory->user->create_and_get( [ 'role' => 'subscriber' ] );
        $nonexistent_user  = new WP_User( 0 );

        // Default new-user role is 'subscriber', so this hits the subscriber branch.
        $this->assertFalse( $this->theme_setup->hideAdminBarFromSubscribers( $default_role_user ) );
        $this->assertTrue( $this->theme_setup->hideAdminBarFromSubscribers( $admin_user ) );
        $this->assertFalse( $this->theme_setup->hideAdminBarFromSubscribers( $subscriber ) );

        // Non-existent / unauthenticated users fall through and keep the admin bar shown.
        $this->assertTrue( $this->theme_setup->hideAdminBarFromSubscribers( $nonexistent_user ) );
        $this->assertTrue( $this->theme_setup->hideAdminBarFromSubscribers( null ) );
    }

    // -------------------------------------------------------------------------
    // Hook / filter registration
    // -------------------------------------------------------------------------

    public function test_child_theme_setup_registers_expected_hooks() {
        $this->theme_setup->child_theme_setup();

        // Primary nav relocated into header-right area.
        $this->assertNotFalse( has_action( 'genesis_header_right', 'genesis_do_nav' ) );

        // Author avatars wired to entry header.
        $this->assertNotFalse( has_action( 'genesis_entry_header', [ $this->theme_setup, 'wpsites_post_author_avatars' ] ) );

        // Featured image moved from entry content to entry header.
        $this->assertNotFalse( has_action( 'genesis_entry_header', 'genesis_do_post_image' ) );

        // Custom footer callbacks replace genesis_do_footer.
        $this->assertNotFalse( has_action( 'genesis_footer', [ $this->theme_setup, 'power_of_families_footer' ] ) );
        $this->assertNotFalse( has_action( 'genesis_footer', [ $this->theme_setup, 'power_of_families_footer_menu' ] ) );

        // Genesis filters.
        $this->assertNotFalse( has_filter( 'genesis_nav_items', [ $this->theme_setup, 'be_follow_icons' ] ) );
        $this->assertNotFalse( has_filter( 'genesis_post_info', [ $this->theme_setup, 'sp_post_info_filter' ] ) );
        $this->assertNotFalse( has_filter( 'genesis_pre_load_favicon', [ $this->theme_setup, 'pre_load_favicon' ] ) );
    }

    public function test_display_author_box_registers_hooks() {
        $this->theme_setup->display_author_box_on_single_posts();

        $this->assertNotFalse( has_filter( 'get_the_author_genesis_author_box_single', '__return_true' ) );
        $this->assertNotFalse( has_action( 'genesis_entry_content', 'genesis_do_author_box_single' ) );
    }

    public function test_createWidgets_registers_home_hooks() {
        $this->theme_setup->createWidgets();

        $this->assertNotFalse( has_action( 'genesis_before_content', [ $this->theme_setup, 'home_large_featured' ] ) );
        $this->assertNotFalse( has_action( 'genesis_before_content', [ $this->theme_setup, 'home_featured_widgets' ] ) );
    }

    // -------------------------------------------------------------------------
    // Theme support registration
    // -------------------------------------------------------------------------

    public function test_register_theme_support_adds_genesis_features() {
        $this->theme_setup->register_theme_support();

        $this->assertTrue( current_theme_supports( 'genesis-responsive-viewport' ) );

        $footer_widgets = get_theme_support( 'genesis-footer-widgets' );
        $this->assertSame( 3, $footer_widgets[0] );

        $menus = get_theme_support( 'genesis-menus' );
        $this->assertArrayHasKey( 'primary', $menus[0] );
        $this->assertArrayHasKey( 'tertiary', $menus[0] );
    }

    // -------------------------------------------------------------------------
    // Post info filter
    // -------------------------------------------------------------------------

    public function test_sp_post_info_filter_on_archive_page() {
        // is_single() is false in the default test context (no query set up).
        $result = $this->theme_setup->sp_post_info_filter( '' );

        $this->assertStringContainsString( '[post_author_posts_link]', $result );
        $this->assertStringContainsString( '[post_date', $result );
        // Archive format omits categories and comments.
        $this->assertStringNotContainsString( '[post_categories', $result );
        $this->assertStringNotContainsString( '[post_comments]', $result );
    }

    public function test_sp_post_info_filter_on_single_post() {
        $post_id = $this->factory->post->create();
        $this->go_to( get_permalink( $post_id ) );

        $result = $this->theme_setup->sp_post_info_filter( '' );

        $this->assertStringContainsString( '[post_author_posts_link]', $result );
        $this->assertStringContainsString( '[post_categories', $result );
        $this->assertStringContainsString( '[post_comments]', $result );
    }

    // -------------------------------------------------------------------------
    // Navigation icon filter
    // -------------------------------------------------------------------------

    public function test_be_follow_icons_non_primary_location_returns_menu_unchanged() {
        $menu   = '<li class="menu-item"><a href="/">Home</a></li>';
        $args   = [ 'theme_location' => 'secondary' ];

        $result = $this->theme_setup->be_follow_icons( $menu, $args );

        $this->assertSame( $menu, $result );
    }

    public function test_be_follow_icons_primary_logged_out_appends_login_link() {
        wp_set_current_user( 0 );

        $menu   = '<li class="menu-item"><a href="/">Home</a></li>';
        $args   = [ 'theme_location' => 'primary' ];

        $result = $this->theme_setup->be_follow_icons( $menu, $args );

        $this->assertStringContainsString( 'login-bar', $result );
        $this->assertStringContainsString( 'Log In', $result );
        $this->assertStringNotContainsString( 'My Account', $result );
    }

    public function test_be_follow_icons_primary_logged_in_appends_account_menu() {
        $user_id = $this->factory->user->create();
        wp_set_current_user( $user_id );

        $menu   = '<li class="menu-item"><a href="/">Home</a></li>';
        $args   = [ 'theme_location' => 'primary' ];

        $result = $this->theme_setup->be_follow_icons( $menu, $args );

        $this->assertStringContainsString( 'My Account', $result );
        $this->assertStringContainsString( '/my-account/', $result );
        $this->assertStringContainsString( 'Logout', $result );
        $this->assertStringNotContainsString( 'login-bar', $result );
    }

    // -------------------------------------------------------------------------
    // Favicon output
    // -------------------------------------------------------------------------

    public function test_pre_load_favicon_outputs_link_tags() {
        ob_start();
        $this->theme_setup->pre_load_favicon();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'favicon.ico', $output );
        $this->assertStringContainsString( '<link rel="shortcut icon"', $output );
        $this->assertStringContainsString( 'apple-touch-icon', $output );
        $this->assertStringContainsString( 'rel="icon" type="image/png"', $output );
    }

    // -------------------------------------------------------------------------
    // Footer output
    // -------------------------------------------------------------------------

    public function test_power_of_families_footer_outputs_copyright() {
        ob_start();
        $this->theme_setup->power_of_families_footer();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Copyright', $output );
        $this->assertStringContainsString( 'poweroffamilies.com', $output );
        $this->assertStringContainsString( (string) date( 'Y' ), $output );
    }

    // -------------------------------------------------------------------------
    // Home widget areas
    // -------------------------------------------------------------------------

    public function test_home_large_featured_outputs_nothing_when_not_home() {
        // Default test context has no active query, so is_home() is false.
        ob_start();
        $this->theme_setup->home_large_featured();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_home_large_featured_outputs_wrapper_when_home() {
        $this->factory->post->create();
        $this->go_to( '/' );

        ob_start();
        $this->theme_setup->home_large_featured();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'home-large-featured', $output );
    }

    public function test_home_featured_widgets_outputs_nothing_when_not_home() {
        ob_start();
        $this->theme_setup->home_featured_widgets();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_home_featured_widgets_outputs_three_widget_areas_when_home() {
        $this->factory->post->create();
        $this->go_to( '/' );

        ob_start();
        $this->theme_setup->home_featured_widgets();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'home-featured-widgets', $output );
        $this->assertStringContainsString( 'home-featured-widget-1', $output );
        $this->assertStringContainsString( 'home-featured-widget-2', $output );
        $this->assertStringContainsString( 'home-featured-widget-3', $output );
    }

    // -------------------------------------------------------------------------
    // Custom gravatar defaults
    // -------------------------------------------------------------------------

    public function test_newgravatar_adds_custom_avatar_to_defaults() {
        $defaults = [ 'mystery' => 'Mystery Person' ];

        $result = $this->theme_setup->newgravatar( $defaults );

        $this->assertCount( 2, $result );
        $this->assertContains( 'Power of Families Avatar', $result );
    }

    // -------------------------------------------------------------------------
    // Secondary nav search form
    // -------------------------------------------------------------------------

    public function test_genesis_search_secondary_nav_non_secondary_location_unchanged() {
        $menu   = '<li>Item</li>';
        $args   = (object) [ 'theme_location' => 'primary' ];

        $result = $this->theme_setup->genesis_search_secondary_nav_menu( $menu, $args );

        $this->assertSame( $menu, $result );
    }

    public function test_genesis_search_secondary_nav_appends_search_form() {
        // genesis_get_option() stub returns '' (falsy), so the early return is skipped.
        $menu   = '<li>Item</li>';
        $args   = (object) [ 'theme_location' => 'secondary' ];

        $result = $this->theme_setup->genesis_search_secondary_nav_menu( $menu, $args );

        $this->assertStringContainsString( 'secondary-search', $result );
        $this->assertStringContainsString( 'search-form', $result );
    }

    // -------------------------------------------------------------------------
    // Protected page metadata filter
    // -------------------------------------------------------------------------

    public function test_hide_on_protected_pages_ignores_unrelated_meta_keys() {
        $result = $this->theme_setup->hide_on_protected_pages( null, 1, 'some_other_key', true );

        $this->assertNull( $result );
    }

    public function test_hide_on_protected_pages_essb_off_with_no_protected_pages_returns_null() {
        // Without wlmapi functions installed there are no protected pages,
        // so the method should return null (no override).
        $result = $this->theme_setup->hide_on_protected_pages( null, 1, 'essb_off', true );

        $this->assertNull( $result );
    }
}

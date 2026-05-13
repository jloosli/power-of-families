<?php

/**
 * Tests for the HomepageWidgets class.
 *
 * @package Power_Of_Families
 */
class test_HomepageWidgets extends WP_UnitTestCase {

    private ?\PowerOfFamilies\HomepageWidgets $widgets;

    protected function setUp(): void {
        parent::setUp();
        $this->widgets = new \PowerOfFamilies\HomepageWidgets();
    }

    protected function tearDown(): void {
        $this->widgets = null;
        parent::tearDown();
    }

    public function test_createWidgets_registers_home_hooks() {
        $this->widgets->createWidgets();

        $this->assertNotFalse( has_action( 'genesis_before_content', [ $this->widgets, 'home_large_featured' ] ) );
        $this->assertNotFalse( has_action( 'genesis_before_content', [ $this->widgets, 'home_featured_widgets' ] ) );
    }

    public function test_home_large_featured_outputs_nothing_when_not_home() {
        ob_start();
        $this->widgets->home_large_featured();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_home_large_featured_outputs_nothing_when_sidebar_inactive() {
        $this->factory->post->create();
        $this->go_to( '/' );
        $this->widgets->createWidgets();

        ob_start();
        $this->widgets->home_large_featured();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_home_large_featured_outputs_wrapper_when_sidebar_active() {
        $this->factory->post->create();
        $this->go_to( '/' );
        $this->widgets->createWidgets();
        wp_set_sidebars_widgets( [ 'home_large_featured' => [ 'stub-widget' ] ] );

        ob_start();
        $this->widgets->home_large_featured();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'home-large-featured', $output );
    }

    public function test_home_featured_widgets_outputs_nothing_when_not_home() {
        ob_start();
        $this->widgets->home_featured_widgets();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_home_featured_widgets_outputs_only_outer_wrapper_when_sidebars_inactive() {
        $this->factory->post->create();
        $this->go_to( '/' );
        $this->widgets->createWidgets();

        ob_start();
        $this->widgets->home_featured_widgets();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'home-featured-widgets', $output );
        $this->assertStringNotContainsString( 'home-featured-widget-1', $output );
        $this->assertStringNotContainsString( 'home-featured-widget-2', $output );
        $this->assertStringNotContainsString( 'home-featured-widget-3', $output );
    }

    public function test_home_featured_widgets_outputs_three_widget_areas_when_sidebars_active() {
        $this->factory->post->create();
        $this->go_to( '/' );
        $this->widgets->createWidgets();
        wp_set_sidebars_widgets( [
            'home_left'   => [ 'stub-widget' ],
            'home_middle' => [ 'stub-widget' ],
            'home_right'  => [ 'stub-widget' ],
        ] );

        ob_start();
        $this->widgets->home_featured_widgets();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'home-featured-widgets', $output );
        $this->assertStringContainsString( 'home-featured-widget-1', $output );
        $this->assertStringContainsString( 'home-featured-widget-2', $output );
        $this->assertStringContainsString( 'home-featured-widget-3', $output );
    }
}

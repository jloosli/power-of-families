<?php

/**
 * Tests for the PostDisplay class.
 *
 * @package Power_Of_Families
 */
class test_PostDisplay extends WP_UnitTestCase {

    private ?\PowerOfFamilies\Avanti\PostDisplay $display;

    protected function setUp(): void {
        parent::setUp();
        $this->display = new \PowerOfFamilies\Avanti\PostDisplay();
    }

    protected function tearDown(): void {
        $this->display = null;
        parent::tearDown();
    }

    public function test_construct_registers_expected_hooks() {
        $this->assertNotFalse( has_filter( 'genesis_post_info', [ $this->display, 'sp_post_info_filter' ] ) );
        $this->assertNotFalse( has_action( 'genesis_entry_header', [ $this->display, 'wpsites_post_author_avatars' ] ) );
        $this->assertNotFalse( has_action( 'genesis_entry_header', 'genesis_do_post_image' ) );
        $this->assertNotFalse( has_filter( 'avatar_defaults', [ $this->display, 'newgravatar' ] ) );

        $this->assertNotFalse( has_filter( 'get_the_author_genesis_author_box_single', '__return_true' ) );
        $this->assertNotFalse( has_action( 'genesis_entry_content', 'genesis_do_author_box_single' ) );
    }

    public function test_sp_post_info_filter_on_archive_page() {
        $result = $this->display->sp_post_info_filter( '' );

        $this->assertStringContainsString( '[post_author_posts_link]', $result );
        $this->assertStringContainsString( '[post_date', $result );
        $this->assertStringNotContainsString( '[post_categories', $result );
        $this->assertStringNotContainsString( '[post_comments]', $result );
    }

    public function test_sp_post_info_filter_on_single_post() {
        $post_id = $this->factory->post->create();
        $this->go_to( get_permalink( $post_id ) );

        $result = $this->display->sp_post_info_filter( '' );

        $this->assertStringContainsString( '[post_author_posts_link]', $result );
        $this->assertStringContainsString( '[post_categories', $result );
        $this->assertStringContainsString( '[post_comments]', $result );
    }

    public function test_newgravatar_adds_custom_avatar_to_defaults() {
        $defaults = [ 'mystery' => 'Mystery Person' ];

        $result = $this->display->newgravatar( $defaults );

        $this->assertCount( 2, $result );
        $this->assertContains( 'Power of Families Avatar', $result );
    }
}

<?php

/**
 * Tests for the SharingControls class.
 *
 * @package Power_Of_Families
 */
class test_SharingControls extends WP_UnitTestCase {

    private ?\PowerOfFamilies\SharingControls $sharing;

    protected function setUp(): void {
        parent::setUp();
        $this->sharing = new \PowerOfFamilies\SharingControls();
    }

    protected function tearDown(): void {
        delete_transient( 'pof_protected_pages' );
        $this->sharing = null;
        parent::tearDown();
    }

    public function test_construct_registers_expected_hooks() {
        $this->assertNotFalse( has_filter( 'get_post_metadata', [ $this->sharing, 'hide_on_protected_pages' ] ) );
        $this->assertNotFalse( has_filter( 'get_page_metadata', [ $this->sharing, 'hide_on_protected_pages' ] ) );
    }

    public function test_hide_on_protected_pages_ignores_unrelated_meta_keys() {
        $result = $this->sharing->hide_on_protected_pages( null, 1, 'some_other_key', true );

        $this->assertNull( $result );
    }

    public function test_hide_on_protected_pages_essb_off_with_no_protected_pages_returns_null() {
        $result = $this->sharing->hide_on_protected_pages( null, 1, 'essb_off', true );

        $this->assertNull( $result );
    }
}

<?php

/**
 * Tests for the Affiliate_Linker class.
 *
 * Regression coverage: the daily cron hook (POF_Affiliate_Linker_CRON)
 * was scheduled but never wired to a callback, so the Amazon tagging
 * job silently did nothing.
 *
 * @package Power_Of_Families
 */
class test_Affiliate_Linker extends WP_UnitTestCase {

    private ?\PowerOfFamilies\POF\Programs\Affiliate_Linker $linker;

    protected function setUp(): void {
        parent::setUp();
        $this->linker = new \PowerOfFamilies\POF\Programs\Affiliate_Linker();
    }

    protected function tearDown(): void {
        wp_clear_scheduled_hook( 'POF_Affiliate_Linker_CRON' );
        $this->linker = null;
        parent::tearDown();
    }

    public function test_cron_action_is_bound_to_add_amazon() {
        $this->assertNotFalse(
            has_action( 'POF_Affiliate_Linker_CRON', [ $this->linker, 'add_amazon' ] ),
            'POF_Affiliate_Linker_CRON must be wired to Affiliate_Linker::add_amazon; otherwise scheduled cron fires are no-ops.'
        );
    }

    public function test_activation_schedules_daily_cron() {
        $this->assertFalse( wp_next_scheduled( 'POF_Affiliate_Linker_CRON' ) );

        $this->linker->activation();

        $this->assertNotFalse( wp_next_scheduled( 'POF_Affiliate_Linker_CRON' ) );
    }

    public function test_activation_is_idempotent() {
        $this->linker->activation();
        $first = wp_next_scheduled( 'POF_Affiliate_Linker_CRON' );

        $this->linker->activation();
        $second = wp_next_scheduled( 'POF_Affiliate_Linker_CRON' );

        $this->assertSame( $first, $second, 'Re-running activation should not reschedule the cron.' );
    }

    /**
     * Settings::loadActivePrograms() does `new $ClassName($this->parent)`,
     * so the constructor must tolerate a parent argument. If it doesn't,
     * PHP raises ArgumentCountError and the cron-hook wiring never runs.
     */
    public function test_constructor_accepts_parent_argument() {
        $parent = new \stdClass();
        $linker = new \PowerOfFamilies\POF\Programs\Affiliate_Linker( $parent );
        $this->assertInstanceOf( \PowerOfFamilies\POF\Programs\Affiliate_Linker::class, $linker );
    }
}

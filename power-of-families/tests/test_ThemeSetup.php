<?php
/**
 * Class SampleTest
 *
 * @package Power_Of_Families
 */

/**
 * Class ThemeSetupTest
 *
 * @package Power_Of_Families
 */

/**
 * Test case for the ThemeSetup class.
 */
class test_ThemeSetup extends WP_UnitTestCase {

	private ?\PowerOfFamilies\Avanti\ThemeSetup $theme_setup;

	protected function setUp(): void {
		parent::setUp();
		$this->theme_setup = new \PowerOfFamilies\Avanti\ThemeSetup();
	}

	protected function tearDown(): void {
		$this->theme_setup = null;
		parent::tearDown();
	}

	public function test_construct() {
		$this->assertInstanceOf( \PowerOfFamilies\Avanti\ThemeSetup::class, $this->theme_setup );
	}

	public function test_custom_load_styles_and_scripts() {

		$this->assertFalse(wp_script_is( 'pof_theme_scripts' ));
		$this->theme_setup->custom_load_styles_and_scripts();

		$this->assertTrue( wp_script_is( 'pof_theme_scripts' ) );
		$this->assertTrue( wp_style_is( 'power_of_families_styles' ) );
	}

	public function test_hideAdminBarFromSubscribers() {
		$no_user = $this->factory->user->create_and_get();
		$admin_user = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );
		$subscriber_user = $this->factory->user->create_and_get( array( 'role' => 'subscriber' ) );

		// No User
		$this->assertFalse($this->theme_setup->hideAdminBarFromSubscribers( $no_user ));

		// Administrator
		$this->assertTrue($this->theme_setup->hideAdminBarFromSubscribers( $admin_user ));

		// Subscriber
		$this->assertFalse($this->theme_setup->hideAdminBarFromSubscribers( $subscriber_user ));
	}
}

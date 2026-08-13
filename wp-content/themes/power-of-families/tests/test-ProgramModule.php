<?php

use PowerOfFamilies\Programs\AffiliateLinker;
use PowerOfFamilies\Programs\AffiliateLinkerSettings;
use PowerOfFamilies\Programs\MyPrograms;
use PowerOfFamilies\Programs\ProgramModule;
use PowerOfFamilies\Programs\ProgramSettings;
use PowerOfFamilies\Settings;

/**
 * Tests for the program-module contract.
 *
 * Closes issue #80. Settings used to reach for a module's shape at runtime:
 * ReflectionClass decided whether the constructor wanted the token, and a
 * `has-settings` flag in the registry decided whether to call
 * `getSettingsInstance()` -- a method nothing declared, so a registry entry
 * flagged `true` for a class without it fataled every admin request.
 * ProgramModule declares both halves, and these tests hold the registry to
 * them.
 *
 * @package Power_Of_Families
 */
class test_ProgramModule extends WP_UnitTestCase {

    protected function tearDown(): void {
        delete_option( 'pof_active_programs' );
        parent::tearDown();
    }

    /**
     * The registry is private, and deliberately read here rather than
     * duplicated: a module added to it in future is covered by these tests
     * without anyone remembering to list it twice.
     *
     * @return string[]
     */
    private function registry_keys(): array {
        $available = new ReflectionMethod( Settings::class, 'getAvailablePrograms' );
        $available->setAccessible( true );

        return array_keys( $available->invoke( new Settings( 'pof' ) ) );
    }

    /**
     * @param string[] $keys
     */
    private function activate( array $keys, string $token = 'pof' ): Settings {
        update_option( 'pof_active_programs', $keys );

        return new Settings( $token );
    }

    /**
     * The guard that replaces the latent fatal: a class listed in the
     * registry that does not implement the contract cannot be constructed
     * with the token, and cannot answer settings(). Both are declared now,
     * so this fails at the registry rather than on an admin request.
     */
    public function test_every_registered_program_implements_the_contract() {
        $keys = $this->registry_keys();

        $settings = $this->activate( $keys );

        $this->assertCount( count( $keys ), $settings->programs );
        foreach ( $keys as $key ) {
            $this->assertInstanceOf(
                ProgramModule::class,
                $settings->programs[ $key ],
                sprintf( 'Program "%s" is listed in the registry but does not implement ProgramModule.', $key )
            );
        }
    }

    /**
     * The reflection sniff existed to avoid passing the token to a module
     * that took no constructor arguments. Every module takes it now -- and
     * MyPrograms, which actually uses it, still receives it.
     */
    public function test_active_programs_are_constructed_with_the_token() {
        $settings = $this->activate( array( 'My_Programs' ), 'pof_token' );

        wp_register_script( 'pof_token-frontend', 'https://example.org/frontend.js', array(), '1', true );
        $settings->programs['My_Programs']->enqueue_scripts();

        $this->assertTrue(
            wp_script_is( 'pof_token-frontend', 'enqueued' ),
            'MyPrograms enqueues <token>-frontend, so a missing handle means the token never reached the constructor.'
        );
    }

    public function test_module_with_a_settings_screen_declares_it() {
        $linker = new AffiliateLinker( 'pof' );

        $screen = $linker->settings();

        $this->assertInstanceOf( ProgramSettings::class, $screen );
        $this->assertInstanceOf( AffiliateLinkerSettings::class, $screen );
    }

    public function test_settings_screen_is_built_once_per_module() {
        $linker = new AffiliateLinker( 'pof' );

        $this->assertSame( $linker->settings(), $linker->settings() );
    }

    /**
     * The screen used to live in a static property, so every AffiliateLinker
     * in the process shared one -- state that outlived the object holding it.
     */
    public function test_settings_screens_are_not_shared_between_modules() {
        $this->assertNotSame(
            ( new AffiliateLinker( 'pof' ) )->settings(),
            ( new AffiliateLinker( 'pof' ) )->settings()
        );
    }

    public function test_module_without_a_settings_screen_says_so() {
        $this->assertNull( ( new MyPrograms( 'pof' ) )->settings() );
    }

    /**
     * The end of the chain: what a module declares is what the settings page
     * shows. Affiliate_Linker contributes a tab, My_Programs does not.
     */
    public function test_only_modules_with_a_screen_contribute_a_tab() {
        $settings = $this->activate( array( 'Affiliate_Linker', 'My_Programs' ) );

        $settings->init_settings();

        $this->assertArrayHasKey( 'Affiliate_Linker', $settings->settings );
        $this->assertSame( 'Amazon Affiliate Linker', $settings->settings['Affiliate_Linker']['title'] );
        $this->assertArrayNotHasKey( 'My_Programs', $settings->settings );
    }

    public function test_inactive_module_contributes_no_tab() {
        $settings = $this->activate( array( 'My_Programs' ) );

        $settings->init_settings();

        $this->assertArrayNotHasKey( 'Affiliate_Linker', $settings->settings );
    }
}

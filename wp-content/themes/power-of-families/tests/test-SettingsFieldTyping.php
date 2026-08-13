<?php

/**
 * Tests for the Settings screen's field typing.
 *
 * @package Power_Of_Families
 */
class test_SettingsFieldTyping extends WP_UnitTestCase {

    protected function tearDown(): void {
        delete_option( 'pof_active_programs' );
        remove_all_filters( 'Power_of_Families_Programs_settings_fields' );
        parent::tearDown();
    }

    public function test_settings_fields_are_typed() {
        $settings = new \PowerOfFamilies\Settings( 'Power_of_Families_Programs', new \PowerOfFamilies\FieldRenderer() );
        $settings->init_settings();

        $this->assertNotEmpty( $settings->settings['standard']['fields'] );

        foreach ( $settings->settings['standard']['fields'] as $field ) {
            $this->assertInstanceOf( \PowerOfFamilies\FieldDefinition::class, $field );
        }
    }

    public function test_filter_supplied_fields_are_typed_too() {
        add_filter(
            'Power_of_Families_Programs_settings_fields',
            function ( $settings ) {
                $settings['extra'] = [
                    'title'       => 'Extra',
                    'description' => 'Added by a third party',
                    'fields'      => [ [ 'id' => 'extra_field', 'type' => 'text' ] ],
                ];

                return $settings;
            }
        );

        $settings = new \PowerOfFamilies\Settings( 'Power_of_Families_Programs', new \PowerOfFamilies\FieldRenderer() );
        $settings->init_settings();

        $this->assertInstanceOf(
            \PowerOfFamilies\FieldDefinition::class,
            $settings->settings['extra']['fields'][0],
            'Fields contributed through the filter must be typed on the same path as ours.'
        );
    }

    public function test_field_renderer_no_longer_registers_hooks() {
        $this->assertFalse(
            method_exists( \PowerOfFamilies\FieldRenderer::class, 'register' ),
            'FieldRenderer::register() only wired the deleted metabox path.'
        );
        $this->assertFalse(
            method_exists( \PowerOfFamilies\FieldRenderer::class, 'save_meta_boxes' ),
            'The metabox path has no registrant anywhere in the install.'
        );
    }
}

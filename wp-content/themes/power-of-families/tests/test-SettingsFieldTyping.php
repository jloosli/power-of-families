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

    /**
     * A third party's mistake must degrade their own field, not the site.
     *
     * settings_fields() runs on `init`, so an uncontained throw here would
     * fatal every page load rather than one settings control.
     *
     * @dataProvider malformed_field_provider
     */
    public function test_a_malformed_filter_field_is_dropped_not_fatal( $malformed ) {
        $this->setExpectedIncorrectUsage( 'PowerOfFamilies\Settings::settings_fields' );

        add_filter(
            'Power_of_Families_Programs_settings_fields',
            function ( $settings ) use ( $malformed ) {
                $settings['extra'] = [
                    'title'       => 'Extra',
                    'description' => 'Added by a third party',
                    'fields'      => [ $malformed, [ 'id' => 'good_field', 'type' => 'text' ] ],
                ];

                return $settings;
            }
        );

        $settings = new \PowerOfFamilies\Settings( 'Power_of_Families_Programs', new \PowerOfFamilies\FieldRenderer() );
        $settings->init_settings();

        $fields = $settings->settings['extra']['fields'];

        $this->assertCount( 1, $fields, 'The malformed field should be dropped.' );
        $this->assertInstanceOf( \PowerOfFamilies\FieldDefinition::class, $fields[0] );
        $this->assertSame(
            'good_field',
            $fields[0]->id,
            'A sibling field must survive its neighbour being malformed.'
        );
    }

    public function malformed_field_provider(): array {
        return [
            'not an array'      => [ 'just a string' ],
            'missing id'        => [ [ 'type' => 'text' ] ],
            'unrecognised type' => [ [ 'id' => 'typo_field', 'type' => 'txet' ] ],
            // (string) on an object raises a plain \Error -- not a \TypeError --
            // so an unguarded cast escapes a catch naming TypeError and fatals
            // the request. Arrays are the milder sibling: a warning, which this
            // suite turns into an exception via convertWarningsToExceptions.
            'object id'      => [ [ 'id' => new \stdClass() ] ],
            'object type'    => [ [ 'id' => 'obj_type', 'type' => new \stdClass() ] ],
            'object label'   => [ [ 'id' => 'obj_label', 'type' => 'text', 'label' => new \stdClass() ] ],
            'array type'     => [ [ 'id' => 'arr_type', 'type' => [ 'nested' ] ] ],
            'array label'    => [ [ 'id' => 'arr_label', 'type' => 'text', 'label' => [ 'nested' ] ] ],
        ];
    }

    public function test_a_non_array_section_does_not_fatal() {
        $this->setExpectedIncorrectUsage( 'PowerOfFamilies\Settings::settings_fields' );

        add_filter(
            'Power_of_Families_Programs_settings_fields',
            function ( $settings ) {
                // An object section: isset($data['fields']) on this raises
                // "Cannot use object of type stdClass as array".
                $settings['bogus'] = new \stdClass();

                return $settings;
            }
        );

        $settings = new \PowerOfFamilies\Settings( 'Power_of_Families_Programs', new \PowerOfFamilies\FieldRenderer() );
        $settings->init_settings();

        $this->assertInstanceOf(
            \PowerOfFamilies\FieldDefinition::class,
            $settings->settings['standard']['fields'][0],
            'A malformed section must not stop the well-formed ones being typed.'
        );
    }

    public function test_the_standard_section_still_types_when_a_filter_field_is_malformed() {
        $this->setExpectedIncorrectUsage( 'PowerOfFamilies\Settings::settings_fields' );

        add_filter(
            'Power_of_Families_Programs_settings_fields',
            function ( $settings ) {
                $settings['extra'] = [
                    'title'  => 'Extra',
                    'fields' => [ [ 'type' => 'text' ] ],
                ];

                return $settings;
            }
        );

        $settings = new \PowerOfFamilies\Settings( 'Power_of_Families_Programs', new \PowerOfFamilies\FieldRenderer() );
        $settings->init_settings();

        $this->assertInstanceOf(
            \PowerOfFamilies\FieldDefinition::class,
            $settings->settings['standard']['fields'][0],
            "One section's bad field must not stop another section being typed."
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

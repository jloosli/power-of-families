<?php

/**
 * Tests for the FieldType enum.
 *
 * The predicates replace in_array() checks that were previously
 * scattered through FieldRenderer::display_field().
 *
 * @package Power_Of_Families
 */
class test_FieldType extends WP_UnitTestCase {

    public function test_every_type_in_the_legacy_switch_has_a_case() {
        $legacy = [
            'text', 'url', 'email', 'password', 'number', 'hidden',
            'text_secret', 'textarea', 'checkbox', 'checkbox_multi',
            'radio', 'select', 'select_multi', 'image', 'color',
        ];

        foreach ( $legacy as $value ) {
            $this->assertInstanceOf(
                \PowerOfFamilies\FieldType::class,
                \PowerOfFamilies\FieldType::tryFrom( $value ),
                sprintf( 'FieldType must cover the legacy "%s" field type.', $value )
            );
        }
    }

    public function test_none_is_a_distinct_case() {
        $this->assertSame( 'none', \PowerOfFamilies\FieldType::None->value );
    }

    public function test_is_multi_value_is_true_only_for_array_valued_types() {
        $expected = [ 'checkbox_multi', 'select_multi' ];

        foreach ( \PowerOfFamilies\FieldType::cases() as $case ) {
            $this->assertSame(
                in_array( $case->value, $expected, true ),
                $case->isMultiValue(),
                sprintf( 'isMultiValue() wrong for %s', $case->value )
            );
        }
    }

    public function test_describes_below_control_matches_legacy_in_array_check() {
        $expected = [ 'checkbox_multi', 'radio', 'select_multi' ];

        foreach ( \PowerOfFamilies\FieldType::cases() as $case ) {
            $this->assertSame(
                in_array( $case->value, $expected, true ),
                $case->describesBelowControl(),
                sprintf( 'describesBelowControl() wrong for %s', $case->value )
            );
        }
    }
}

<?php

/**
 * Tests for the FieldDefinition value object.
 *
 * Regression coverage: AffiliateLinkerSettings defines a field with only
 * `id` and `label`, and FieldRenderer read `type`, `description` and
 * `placeholder` unguarded -- so rendering that tab emitted PHP warnings
 * from committed production data.
 *
 * @package Power_Of_Families
 */
class test_FieldDefinition extends WP_UnitTestCase {

    public function test_missing_id_throws() {
        $this->expectException( \InvalidArgumentException::class );

        \PowerOfFamilies\FieldDefinition::fromArray( [ 'type' => 'text' ] );
    }

    public function test_empty_id_throws() {
        $this->expectException( \InvalidArgumentException::class );

        \PowerOfFamilies\FieldDefinition::fromArray( [ 'id' => '', 'type' => 'text' ] );
    }

    public function test_missing_type_becomes_none() {
        $field = \PowerOfFamilies\FieldDefinition::fromArray(
            [ 'id' => 'pof_amazon_affiliate_run_now', 'label' => '<a class="button">Run</a>' ]
        );

        $this->assertSame( \PowerOfFamilies\FieldType::None, $field->type );
    }

    public function test_unknown_type_throws_naming_the_field_and_the_value() {
        try {
            \PowerOfFamilies\FieldDefinition::fromArray( [ 'id' => 'amazon_affiliate_id', 'type' => 'txet' ] );
            $this->fail( 'Expected InvalidArgumentException for an unknown field type.' );
        } catch ( \InvalidArgumentException $e ) {
            $this->assertStringContainsString( 'amazon_affiliate_id', $e->getMessage() );
            $this->assertStringContainsString( 'txet', $e->getMessage() );
        }
    }

    public function test_optional_string_keys_default_to_empty_string() {
        $field = \PowerOfFamilies\FieldDefinition::fromArray( [ 'id' => 'x', 'type' => 'text' ] );

        $this->assertSame( '', $field->label );
        $this->assertSame( '', $field->description );
        $this->assertSame( '', $field->placeholder );
    }

    public function test_options_defaults_to_empty_array() {
        $field = \PowerOfFamilies\FieldDefinition::fromArray( [ 'id' => 'x', 'type' => 'select' ] );

        $this->assertSame( [], $field->options );
    }

    public function test_nullable_keys_default_to_null() {
        $field = \PowerOfFamilies\FieldDefinition::fromArray( [ 'id' => 'x', 'type' => 'number' ] );

        $this->assertNull( $field->default );
        $this->assertNull( $field->min );
        $this->assertNull( $field->max );
        $this->assertNull( $field->callback );
    }

    public function test_supplied_values_are_preserved() {
        $field = \PowerOfFamilies\FieldDefinition::fromArray(
            [
                'id'          => 'active_programs',
                'label'       => 'Active Programs',
                'description' => 'Select the programs you want to be active.',
                'type'        => 'checkbox_multi',
                'options'     => [ 'Affiliate_Linker' => 'Affiliate Linker' ],
                'default'     => [],
            ]
        );

        $this->assertSame( 'active_programs', $field->id );
        $this->assertSame( \PowerOfFamilies\FieldType::CheckboxMulti, $field->type );
        $this->assertSame( 'Active Programs', $field->label );
        $this->assertSame( [ 'Affiliate_Linker' => 'Affiliate Linker' ], $field->options );
        $this->assertSame( [], $field->default );
    }

    public function test_from_array_is_idempotent_over_a_round_trip() {
        $source = [ 'id' => 'x', 'type' => 'text', 'placeholder' => 'hi' ];
        $first  = \PowerOfFamilies\FieldDefinition::fromArray( $source );
        $second = \PowerOfFamilies\FieldDefinition::fromArray( $source );

        $this->assertEquals( $first, $second );
    }
}

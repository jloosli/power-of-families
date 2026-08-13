<?php

/**
 * Tests for FieldRenderer.
 *
 * The renderer had no tests at all before this. Coverage focuses on the
 * behaviours that were previously implicit: default fallback, description
 * placement, and that a typeless field renders no control.
 *
 * @package Power_Of_Families
 */
class test_FieldRenderer extends WP_UnitTestCase {

    private \PowerOfFamilies\FieldRenderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->renderer = new \PowerOfFamilies\FieldRenderer();
    }

    protected function tearDown(): void {
        delete_option( 'pof_greeting' );
        parent::tearDown();
    }

    private function render( array $field, string $prefix = 'pof_' ): string {
        ob_start();
        $this->renderer->display_field( [ 'field' => $field, 'prefix' => $prefix ] );
        return ob_get_clean();
    }

    public function test_text_field_renders_saved_option() {
        update_option( 'pof_greeting', 'hello' );

        $html = $this->render( [ 'id' => 'greeting', 'type' => 'text' ] );

        $this->assertStringContainsString( 'name="pof_greeting"', $html );
        $this->assertStringContainsString( 'value="hello"', $html );
    }

    public function test_text_field_falls_back_to_default_when_unset() {
        $html = $this->render( [ 'id' => 'greeting', 'type' => 'text', 'default' => 'howdy' ] );

        $this->assertStringContainsString( 'value="howdy"', $html );
    }

    public function test_missing_placeholder_renders_empty_not_a_warning() {
        $html = $this->render( [ 'id' => 'greeting', 'type' => 'text' ] );

        $this->assertStringContainsString( 'placeholder=""', $html );
    }

    public function test_typeless_field_renders_no_control() {
        $html = $this->render( [ 'id' => 'run_now', 'label' => '<a class="button">Run</a>' ] );

        $this->assertStringNotContainsString( '<input', $html );
        $this->assertStringNotContainsString( '<select', $html );
    }

    public function test_textarea_value_is_escaped() {
        update_option( 'pof_greeting', '</textarea><script>alert(1)</script>' );

        $html = $this->render( [ 'id' => 'greeting', 'type' => 'textarea' ] );

        $this->assertStringNotContainsString( '<script>', $html );
    }

    public function test_select_renders_every_option() {
        $html = $this->render(
            [
                'id'      => 'greeting',
                'type'    => 'select',
                'options' => [ 'a' => 'Apple', 'b' => 'Banana' ],
            ]
        );

        $this->assertStringContainsString( 'value="a"', $html );
        $this->assertStringContainsString( 'Banana', $html );
    }

    public function test_multi_value_field_survives_an_unsaved_option() {
        $html = $this->render(
            [
                'id'      => 'greeting',
                'type'    => 'checkbox_multi',
                'options' => [ 'a' => 'Apple' ],
            ]
        );

        $this->assertStringContainsString( 'type="checkbox"', $html );
    }

    public function test_description_renders_below_control_for_radio() {
        $html = $this->render(
            [
                'id'          => 'greeting',
                'type'        => 'radio',
                'options'     => [ 'a' => 'Apple' ],
                'description' => 'Pick one',
            ]
        );

        $this->assertStringContainsString( '<br/><span class="description">Pick one</span>', $html );
    }

    public function test_description_is_wrapped_in_a_label_for_scalar_controls() {
        $html = $this->render(
            [ 'id' => 'greeting', 'type' => 'text', 'description' => 'Your greeting' ]
        );

        $this->assertStringContainsString( '<label for="greeting">', $html );
        $this->assertStringContainsString( 'Your greeting', $html );
    }

    public function test_accepts_an_already_typed_definition() {
        $definition = \PowerOfFamilies\FieldDefinition::fromArray(
            [ 'id' => 'greeting', 'type' => 'text', 'default' => 'typed' ]
        );

        ob_start();
        $this->renderer->display_field( [ 'field' => $definition, 'prefix' => 'pof_' ] );
        $html = ob_get_clean();

        $this->assertStringContainsString( 'value="typed"', $html );
    }
}

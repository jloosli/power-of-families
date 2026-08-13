# Field Definition Type Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the untyped field-definition array with a `FieldDefinition` readonly class and a `FieldType` enum, and delete the unreachable metabox half of `FieldRenderer` that the type exposes.

**Architecture:** Raw arrays are converted to `FieldDefinition` objects at exactly one boundary — `Settings::settings_fields()`, immediately after the `{token}_settings_fields` filter runs — so third-party contributions are typed on the same path as ours. `FieldRenderer` shrinks to a single public method that consumes the typed value. Behavior encoded today as scattered `in_array` checks moves onto `FieldType` as predicates.

**Tech Stack:** PHP 8.4, PSR-4 autoloading (`PowerOfFamilies\` → `wp-content/themes/power-of-families/inc/PowerOfFamilies/`), PHPUnit 9.6 with the WordPress test library, Docker/Podman test harness.

**Spec:** [`docs/superpowers/specs/2026-08-12-field-definition-type-design.md`](../specs/2026-08-12-field-definition-type-design.md)

## Global Constraints

- **PHP 8.4.** `final readonly class` and backed enums are available; do not add polyfills.
- **Spaces, not tabs** (`AGENTS.md`). `FieldRenderer.php` currently uses tabs; convert the lines you touch. Four-space indent, matching `Settings.php`.
- **PHP is not format-gated in CI.** `npm run format:check` is Prettier, which has no PHP plugin here. Markdown _is_ gated — run `npx prettier --write` on any `.md` you touch.
- **New test files are invisible until listed.** `phpunit.xml` names test files individually in `<testsuite>`; a new file not listed there simply never runs.
- **Namespace:** `PowerOfFamilies`. New classes go in `inc/PowerOfFamilies/` and autoload with no composer regeneration (PSR-4 resolves dynamically).
- **All test commands run from the repo root**, not the theme directory.

---

### Task 0: Establish the baseline

No code changes. Every later "no behavior change" claim is measured against these numbers, so they must be observed, not assumed.

**Files:** none

- [ ] **Step 1: Rebuild the test image**

Candidate 06 (merged as `ba01519`) changed `docker/test.dockerfile` — removed the ENTRYPOINT, moved WORKDIR to the theme directory, added `docker/ci-test.sh`. A stale image silently runs the old harness.

```bash
docker compose --profile testing build test
```

- [ ] **Step 2: Populate the theme build artifact**

One test skips without `dist/*.asset.php`.

```bash
npm run build:theme
```

- [ ] **Step 3: Run the suite and record the numbers**

```bash
npm run test
```

Expected: passing, roughly 34 tests / 80 assertions. **Write the actual counts down** — the exact figures are the baseline, and Task 5 compares against them.

- [ ] **Step 4: Confirm the warnings this work removes**

Prove the defect exists before fixing it:

```bash
grep -n "placeholder\|description" wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldRenderer.php | head
```

Confirm `FieldRenderer.php:74`, `:89`, `:93`, `:97` read `$field['placeholder']` unguarded and `:175`, `:181` read `$field['description']` unguarded, while `AffiliateLinkerSettings.php:55-59` defines a field supplying neither.

---

### Task 1: FieldType enum

**Files:**

- Create: `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldType.php`
- Test: `wp-content/themes/power-of-families/tests/test-FieldType.php`
- Modify: `wp-content/themes/power-of-families/phpunit.xml`

**Interfaces:**

- Consumes: nothing.
- Produces: `PowerOfFamilies\FieldType` — backed string enum, 16 cases. Methods `isMultiValue(): bool` and `describesBelowControl(): bool`. Tasks 2 and 3 depend on these exact names.

> **Amendment to the spec:** the spec also lists `usesOptions()`, meant to replace four unguarded `$field['options']` loops. It is dropped. Once `options` defaults to `[]` in `FieldDefinition`, iterating it needs no guard, so nothing would ever call the predicate — the type removes the need rather than relocating it.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/power-of-families/tests/test-FieldType.php`:

```php
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
```

- [ ] **Step 2: Register the test file**

`phpunit.xml` lists files individually. In `wp-content/themes/power-of-families/phpunit.xml`, inside `<testsuite name="Theme Test Suite">`, after the `test-AffiliateLinker.php` line, add:

```xml
            <file>./tests/test-FieldType.php</file>
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
npm run test
```

Expected: FAIL — `Error: Class "PowerOfFamilies\FieldType" not found`.

- [ ] **Step 4: Write the enum**

Create `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldType.php`:

```php
<?php

namespace PowerOfFamilies;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The set of field controls a settings screen can render.
 *
 * `None` is the type of a field that renders no control at all -- a row
 * that exists only for its label. It is what a field with no declared
 * type resolves to.
 */
enum FieldType: string
{
    case None = 'none';
    case Text = 'text';
    case Url = 'url';
    case Email = 'email';
    case Password = 'password';
    case Number = 'number';
    case Hidden = 'hidden';
    case TextSecret = 'text_secret';
    case Textarea = 'textarea';
    case Checkbox = 'checkbox';
    case CheckboxMulti = 'checkbox_multi';
    case Radio = 'radio';
    case Select = 'select';
    case SelectMulti = 'select_multi';
    case Image = 'image';
    case Color = 'color';

    /**
     * Whether a saved value for this control is an array rather than a scalar.
     */
    public function isMultiValue(): bool
    {
        return match ($this) {
            self::CheckboxMulti, self::SelectMulti => true,
            default => false,
        };
    }

    /**
     * Whether the description renders beneath the control rather than
     * wrapped in a label alongside it.
     */
    public function describesBelowControl(): bool
    {
        return match ($this) {
            self::CheckboxMulti, self::Radio, self::SelectMulti => true,
            default => false,
        };
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
npm run test
```

Expected: PASS, with 4 more tests than the Task 0 baseline.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldType.php \
        wp-content/themes/power-of-families/tests/test-FieldType.php \
        wp-content/themes/power-of-families/phpunit.xml
git commit -m "Add FieldType enum for settings field controls"
```

---

### Task 2: FieldDefinition value object

**Files:**

- Create: `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldDefinition.php`
- Test: `wp-content/themes/power-of-families/tests/test-FieldDefinition.php`
- Modify: `wp-content/themes/power-of-families/phpunit.xml`

**Interfaces:**

- Consumes: `PowerOfFamilies\FieldType` from Task 1.
- Produces: `PowerOfFamilies\FieldDefinition` with readonly public properties `id`, `type`, `label`, `description`, `placeholder`, `options`, `default`, `min`, `max`, `callback`, and the static factory `FieldDefinition::fromArray(array $field): self`. Tasks 3 and 4 depend on these exact property names.

> **Note on `callback`:** this property is not in the spec. `Settings.php:218` reads `$field['callback']` and passes it to `register_setting()` as the validation callback. Dropping it would silently remove a live capability on the settings path — the same reasoning the spec gives for keeping `min` and `max`.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/power-of-families/tests/test-FieldDefinition.php`:

```php
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
```

- [ ] **Step 2: Register the test file**

In `wp-content/themes/power-of-families/phpunit.xml`, after the `test-FieldType.php` line:

```xml
            <file>./tests/test-FieldDefinition.php</file>
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
npm run test
```

Expected: FAIL — `Error: Class "PowerOfFamilies\FieldDefinition" not found`.

- [ ] **Step 4: Write the value object**

Create `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldDefinition.php`:

```php
<?php

namespace PowerOfFamilies;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One field on a settings screen.
 *
 * This is the interface between the modules that declare settings fields
 * and the renderer that draws them. Defaults are resolved once here, so
 * readers never guard for absent keys.
 */
final readonly class FieldDefinition
{
    public function __construct(
        public string $id,
        public FieldType $type,
        public string $label = '',
        public string $description = '',
        public string $placeholder = '',
        public array $options = [],
        public mixed $default = null,
        public ?string $min = null,
        public ?string $max = null,
        public mixed $callback = null,
    ) {
    }

    /**
     * Build a definition from the legacy array shape.
     *
     * @throws \InvalidArgumentException when `id` is absent or `type` is unrecognised.
     */
    public static function fromArray(array $field): self
    {
        $id = isset($field['id']) ? (string) $field['id'] : '';

        if ('' === $id) {
            throw new \InvalidArgumentException(
                'Field definition is missing a non-empty "id"; the id names both the stored option and the form control.'
            );
        }

        return new self(
            id: $id,
            type: self::resolveType($field, $id),
            label: isset($field['label']) ? (string) $field['label'] : '',
            description: isset($field['description']) ? (string) $field['description'] : '',
            placeholder: isset($field['placeholder']) ? (string) $field['placeholder'] : '',
            options: isset($field['options']) && is_array($field['options']) ? $field['options'] : [],
            default: $field['default'] ?? null,
            min: isset($field['min']) ? (string) $field['min'] : null,
            max: isset($field['max']) ? (string) $field['max'] : null,
            callback: $field['callback'] ?? null,
        );
    }

    /**
     * An absent type means "no control"; a misspelled one is a mistake.
     */
    private static function resolveType(array $field, string $id): FieldType
    {
        if (!isset($field['type']) || '' === $field['type']) {
            return FieldType::None;
        }

        $type = FieldType::tryFrom((string) $field['type']);

        if (null === $type) {
            throw new \InvalidArgumentException(
                sprintf('Field "%s" declares unknown type "%s".', $id, (string) $field['type'])
            );
        }

        return $type;
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
npm run test
```

Expected: PASS, 9 more tests than after Task 1.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldDefinition.php \
        wp-content/themes/power-of-families/tests/test-FieldDefinition.php \
        wp-content/themes/power-of-families/phpunit.xml
git commit -m "Add FieldDefinition value object with defaults resolved once"
```

---

### Task 3: Rewrite FieldRenderer onto the type

This is the largest task. It deletes the unreachable metabox path and rewrites `display_field` to consume a `FieldDefinition`.

**Files:**

- Modify: `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldRenderer.php` (full rewrite, 318 → ~150 lines)
- Test: `wp-content/themes/power-of-families/tests/test-FieldRenderer.php`
- Modify: `wp-content/themes/power-of-families/phpunit.xml`

**Interfaces:**

- Consumes: `FieldDefinition` and `FieldType` from Tasks 1–2.
- Produces: `FieldRenderer::display_field(array $args): void`. `$args` is WordPress's `add_settings_field` payload: `['field' => FieldDefinition|array, 'prefix' => string]`. The class no longer implements `HookRegistrar` and has no `register()` — Task 4 depends on that.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/power-of-families/tests/test-FieldRenderer.php`:

```php
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
```

- [ ] **Step 2: Register the test file**

In `wp-content/themes/power-of-families/phpunit.xml`, after the `test-FieldDefinition.php` line:

```xml
            <file>./tests/test-FieldRenderer.php</file>
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
npm run test
```

Expected: FAIL — `display_field()` currently takes `(array $data, $post, bool $echo)` and reads array keys, so these fail on the typed-definition test and on the missing-placeholder test (PHP warning converted to an exception by `convertWarningsToExceptions="true"` in `phpunit.xml`).

- [ ] **Step 4: Replace FieldRenderer wholesale**

Replace the entire contents of `wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldRenderer.php` with:

```php
<?php

namespace PowerOfFamilies;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Draws one settings-screen field.
 *
 * Registered as the add_settings_field callback by {@see Settings}. This
 * class no longer wires any hooks of its own -- the metabox path it used
 * to serve was driven by a `{post_type}_custom_fields` filter that nothing
 * in the install ever registered.
 */
class FieldRenderer
{
    /**
     * Render one field.
     *
     * @param array $args WordPress add_settings_field payload:
     *                    ['field' => FieldDefinition|array, 'prefix' => string]
     */
    public function display_field(array $args = []): void
    {
        $field = $args['field'] ?? $args;

        if (is_array($field)) {
            $field = FieldDefinition::fromArray($field);
        }

        $prefix = isset($args['prefix']) ? (string) $args['prefix'] : '';
        $option_name = $prefix . $field->id;

        echo $this->render($field, $option_name, $this->currentValue($field, $option_name));
    }

    /**
     * The saved option, falling back to the definition's default.
     */
    private function currentValue(FieldDefinition $field, string $option_name): mixed
    {
        $option = get_option($option_name);
        $value = (false === $option) ? $field->default : $option;

        if (null === $value) {
            return $field->type->isMultiValue() ? [] : '';
        }

        if ($field->type->isMultiValue() && !is_array($value)) {
            return '' === $value ? [] : [$value];
        }

        return $value;
    }

    private function render(FieldDefinition $field, string $option_name, mixed $value): string
    {
        $html = $this->control($field, $option_name, $value);

        if ($field->type->describesBelowControl()) {
            return $html . '<br/><span class="description">' . wp_kses_post($field->description) . '</span>';
        }

        $html .= '<label for="' . esc_attr($field->id) . '">' . "\n";
        $html .= '<span class="description">' . wp_kses_post($field->description) . '</span>' . "\n";
        $html .= '</label>' . "\n";

        return $html;
    }

    private function control(FieldDefinition $field, string $option_name, mixed $value): string
    {
        return match ($field->type) {
            FieldType::None => '',

            FieldType::Text, FieldType::Url, FieldType::Email => sprintf(
                '<input id="%s" type="text" name="%s" placeholder="%s" value="%s" />' . "\n",
                esc_attr($field->id),
                esc_attr($option_name),
                esc_attr($field->placeholder),
                esc_attr($value)
            ),

            FieldType::Password, FieldType::Number, FieldType::Hidden => sprintf(
                '<input id="%s" type="%s" name="%s" placeholder="%s" value="%s"%s%s/>' . "\n",
                esc_attr($field->id),
                esc_attr($field->type->value),
                esc_attr($option_name),
                esc_attr($field->placeholder),
                esc_attr($value),
                null === $field->min ? '' : ' min="' . esc_attr($field->min) . '"',
                null === $field->max ? '' : ' max="' . esc_attr($field->max) . '"'
            ),

            FieldType::TextSecret => sprintf(
                '<input id="%s" type="text" name="%s" placeholder="%s" value="" />' . "\n",
                esc_attr($field->id),
                esc_attr($option_name),
                esc_attr($field->placeholder)
            ),

            FieldType::Textarea => sprintf(
                '<textarea id="%s" rows="5" cols="50" name="%s" placeholder="%s">%s</textarea><br/>' . "\n",
                esc_attr($field->id),
                esc_attr($option_name),
                esc_attr($field->placeholder),
                esc_textarea($value)
            ),

            FieldType::Checkbox => sprintf(
                '<input id="%s" type="checkbox" name="%s" %s/>' . "\n",
                esc_attr($field->id),
                esc_attr($option_name),
                checked('on', $value, false)
            ),

            FieldType::CheckboxMulti => $this->checkboxMulti($field, $option_name, $value),
            FieldType::Radio => $this->radio($field, $option_name, $value),
            FieldType::Select => $this->select($field, $option_name, $value, false),
            FieldType::SelectMulti => $this->select($field, $option_name, $value, true),
            FieldType::Image => $this->image($option_name, $value),
            FieldType::Color => $this->color($option_name, $value),
        };
    }

    private function checkboxMulti(FieldDefinition $field, string $option_name, mixed $value): string
    {
        $html = '';

        foreach ($field->options as $key => $label) {
            $id = $field->id . '_' . $key;
            $html .= '<label for="' . esc_attr($id) . '" class="checkbox_multi">'
                . '<input type="checkbox" ' . checked(in_array($key, (array) $value, false), true, false)
                . ' name="' . esc_attr($option_name) . '[]" value="' . esc_attr($key) . '"'
                . ' id="' . esc_attr($id) . '" /> ' . esc_html($label) . '</label> ';
        }

        return $html;
    }

    private function radio(FieldDefinition $field, string $option_name, mixed $value): string
    {
        $html = '';

        foreach ($field->options as $key => $label) {
            $id = $field->id . '_' . $key;
            $html .= '<label for="' . esc_attr($id) . '">'
                . '<input type="radio" ' . checked($key, $value, false)
                . ' name="' . esc_attr($option_name) . '" value="' . esc_attr($key) . '"'
                . ' id="' . esc_attr($id) . '" /> ' . esc_html($label) . '</label> ';
        }

        return $html;
    }

    private function select(FieldDefinition $field, string $option_name, mixed $value, bool $multiple): string
    {
        $html = '<select name="' . esc_attr($option_name) . ($multiple ? '[]' : '') . '"'
            . ' id="' . esc_attr($field->id) . '"' . ($multiple ? ' multiple="multiple"' : '') . '>';

        foreach ($field->options as $key => $label) {
            $selected = $multiple ? in_array($key, (array) $value, false) : $key === $value;
            $html .= '<option ' . selected($selected, true, false)
                . ' value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }

        return $html . '</select> ';
    }

    private function image(string $option_name, mixed $value): string
    {
        $thumb = $value ? wp_get_attachment_thumb_url($value) : '';

        $html = '<img id="' . esc_attr($option_name) . '_preview" class="image_preview" src="'
            . esc_url((string) $thumb) . '" /><br/>' . "\n";
        $html .= '<input id="' . esc_attr($option_name) . '_button" type="button"'
            . ' data-uploader_title="' . esc_attr__('Upload an image', 'power-of-families-programs') . '"'
            . ' data-uploader_button_text="' . esc_attr__('Use image', 'power-of-families-programs') . '"'
            . ' class="image_upload_button button"'
            . ' value="' . esc_attr__('Upload new image', 'power-of-families-programs') . '" />' . "\n";
        $html .= '<input id="' . esc_attr($option_name) . '_delete" type="button"'
            . ' class="image_delete_button button"'
            . ' value="' . esc_attr__('Remove image', 'power-of-families-programs') . '" />' . "\n";
        $html .= '<input id="' . esc_attr($option_name) . '" class="image_data_field" type="hidden"'
            . ' name="' . esc_attr($option_name) . '" value="' . esc_attr($value) . '"/><br/>' . "\n";

        return $html;
    }

    private function color(string $option_name, mixed $value): string
    {
        return '<div class="color-picker" style="position:relative;">'
            . '<input type="text" name="' . esc_attr($option_name) . '" class="color"'
            . ' value="' . esc_attr($value) . '" />'
            . '<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>'
            . '</div>';
    }
}
```

Three behavior corrections are folded in deliberately, beyond removing the warnings:

1. The `color` case previously used inline `?> … <?php` and **echoed immediately**, so it printed before the rest of the field's HTML. It now builds into the returned string, in order.
2. `checkbox_multi` / `select_multi` previously called `in_array($k, $data)` where `$data` could be `''`, which is a `TypeError` in PHP 8. `currentValue()` now normalises an unset multi-value option to `[]`.
3. Option labels now go through `esc_html()`; they were interpolated raw.

- [ ] **Step 5: Run the test to verify it passes**

```bash
npm run test
```

Expected: PASS, 10 more tests than after Task 2. If `test_description_renders_below_control_for_radio` fails on whitespace, match the assertion to the real output rather than adding whitespace to the renderer.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/power-of-families/inc/PowerOfFamilies/FieldRenderer.php \
        wp-content/themes/power-of-families/tests/test-FieldRenderer.php \
        wp-content/themes/power-of-families/phpunit.xml
git commit -m "Rewrite FieldRenderer onto FieldDefinition and drop the dead metabox path"
```

---

### Task 4: Convert at the Settings boundary

**Files:**

- Modify: `wp-content/themes/power-of-families/inc/PowerOfFamilies/Settings.php:137-171` and `:199-241`
- Modify: `wp-content/themes/power-of-families/inc/PowerOfFamilies/Programs.php:60-65`
- Test: `wp-content/themes/power-of-families/tests/test-Settings.php` (new)
- Modify: `wp-content/themes/power-of-families/phpunit.xml`

**Interfaces:**

- Consumes: `FieldDefinition::fromArray()` from Task 2; `FieldRenderer::display_field()` from Task 3.
- Produces: `Settings::$settings` where every `['fields']` entry is a `FieldDefinition`. Nothing later depends on this.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/power-of-families/tests/test-Settings.php`:

```php
<?php

/**
 * Tests for the Settings screen's field typing.
 *
 * @package Power_Of_Families
 */
class test_Settings extends WP_UnitTestCase {

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
```

- [ ] **Step 2: Register the test file**

In `wp-content/themes/power-of-families/phpunit.xml`, after the `test-FieldRenderer.php` line:

```xml
            <file>./tests/test-Settings.php</file>
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
npm run test
```

Expected: FAIL — the fields are still raw arrays, so `assertInstanceOf` fails.

- [ ] **Step 4: Convert at the boundary**

In `Settings.php`, replace the closing lines of `settings_fields()` (currently `:168-170`):

```php
        $settings = apply_filters($this->token . '_settings_fields', $settings);

        return $settings;
```

with:

```php
        $settings = apply_filters($this->token . '_settings_fields', $settings);

        // Single conversion boundary: everything downstream -- ours and any
        // field contributed through the filter above -- is typed from here on.
        foreach ($settings as $section => $data) {
            if (!isset($data['fields']) || !is_array($data['fields'])) {
                continue;
            }

            $settings[$section]['fields'] = array_map(
                static fn($field) => $field instanceof FieldDefinition
                    ? $field
                    : FieldDefinition::fromArray($field),
                $data['fields']
            );
        }

        return $settings;
```

- [ ] **Step 5: Read the typed values in register_settings()**

In `Settings.php`, replace the body of the inner `foreach ($data['fields'] as $field)` loop (currently `:214-233`) with:

```php
                foreach ($data['fields'] as $field) {

                    // Register field
                    $option_name = $this->base . $field->id;
                    register_setting($this->token . '_settings', $option_name, $field->callback ?? '');

                    // Add field to page
                    add_settings_field($field->id, $field->label, array(
                        $this->renderer,
                        'display_field'
                    ), $this->token . '_settings', $section, array(
                        'field' => $field,
                        'prefix' => $this->base
                    ));
                }
```

- [ ] **Step 6: Stop registering the renderer's hooks**

In `Programs.php`, delete line 62:

```php
        $this->fieldRenderer?->register();
```

`register()` no longer exists on `FieldRenderer`, so leaving this in place is a fatal error on every admin request.

- [ ] **Step 7: Run the tests to verify they pass**

```bash
npm run test
```

Expected: PASS, 3 more tests than after Task 3.

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/power-of-families/inc/PowerOfFamilies/Settings.php \
        wp-content/themes/power-of-families/inc/PowerOfFamilies/Programs.php \
        wp-content/themes/power-of-families/tests/test-Settings.php \
        wp-content/themes/power-of-families/phpunit.xml
git commit -m "Type settings fields at the Settings boundary"
```

---

### Task 5: Verify and open the PR

**Files:** none modified.

- [ ] **Step 1: Full suite from clean**

```bash
docker compose --profile testing build test
npm run test
```

Expected: all green. Test count = Task 0 baseline + 26 new tests (4 + 9 + 10 + 3). Zero failures, zero errors.

- [ ] **Step 2: Confirm no reference to the deleted API survives**

```bash
grep -rn "save_meta_boxes\|display_meta_box_field\|validate_field\|meta_box_content" \
    wp-content/themes/power-of-families/ --include="*.php"
grep -rn "fieldRenderer?->register\|FieldRenderer implements" \
    wp-content/themes/power-of-families/ --include="*.php"
```

Expected: no output from either. Any hit is a missed call site.

- [ ] **Step 3: Confirm the warnings are gone**

`phpunit.xml` sets `convertWarningsToExceptions="true"`, so a surviving undefined-key read fails a test rather than printing. A green suite that exercises a typeless field (`test_typeless_field_renders_no_control`) is the evidence.

- [ ] **Step 4: Format any Markdown touched**

```bash
npx prettier --check .
```

Expected: pass. This is exactly what CI's "Format Check" job runs.

- [ ] **Step 5: Push and open the PR**

```bash
git push -u origin worktree-field-definition-type
gh pr create --base main --title "Give the field definition a type" --body-file - <<'BODY'
Candidate 02 from `docs/architecture/2026-08-12-deepening-review.md`.
Design: `docs/superpowers/specs/2026-08-12-field-definition-type-design.md`.

Adds `FieldDefinition` and `FieldType`, converts raw arrays at a single
boundary in `Settings::settings_fields()`, and deletes the unreachable
metabox half of `FieldRenderer`.

Baseline before: <N> tests / <M> assertions. After: <N+26> tests.
BODY
```

Fill in the real baseline numbers recorded in Task 0.

- [ ] **Step 6: Check Copilot's review**

Per `AGENTS.md`, both places — the inline comments API and the review body, which carries suppressed comments the inline API never returns.

```bash
gh pr view <n> --json reviews --jq '.reviews[] | "\(.author.login) [\(.state)]: \(.body)"'
gh api --paginate repos/jloosli/power-of-families/pulls/<n>/comments \
    --jq '.[] | "\(.user.login) @ \(.path):\(.line)\n\(.body)"'
```

## Notes for the executor

- **`FieldRenderer` is constructed only when `is_admin()`** (`Programs.php:53`), so `Programs::$fieldRenderer` is null on front-end requests. That is why `Settings::$renderer` is nullable. Do not "tidy" that into a non-nullable property.
- **`AffiliateLinkerSettings.php` must not need editing.** If you find yourself changing it to make a test pass, the `FieldType::None` rule is wrong — stop and raise it rather than editing the file.
- **`Settings::$settings` is a public property** read by `settings_section()` and `get_current_tab()`. Both use section-level keys (`title`, `description`), not field-level ones, so typing the fields does not affect them.

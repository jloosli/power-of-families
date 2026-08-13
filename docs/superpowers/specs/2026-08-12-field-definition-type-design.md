# Give the Field Definition a Type — Design

**Status:** Implemented on `worktree-field-definition-type`; suite green at 63 tests / 174 assertions
**Date:** 2026-08-12
**Author:** Jared Loosli (with Claude)
**Implements:** Candidate 02 of [`docs/architecture/2026-08-12-deepening-review.md`](../../architecture/2026-08-12-deepening-review.md)

## Problem

An untyped array shape is the real interface between five modules:

| File                                                                             | Role                         |
| -------------------------------------------------------------------------------- | ---------------------------- |
| `inc/PowerOfFamilies/Settings.php:137-171`                                       | builds the shape             |
| `inc/PowerOfFamilies/Programs/AffiliateLinkerSettings.php:41-64`                 | builds the shape             |
| `inc/PowerOfFamilies/FieldRenderer.php:19-194`                                   | destructures it              |
| `wp-content/plugins/pof-bloom-plugin/includes/lib/class-pom-bloom-admin-api.php` | destructures it again        |
| `{token}_settings_fields` filter (`Settings.php:168`)                            | lets third parties supply it |

Every reader re-derives the shape with scattered `isset()` guards, and several keys
are read with no guard at all.

**This is not hypothetical.** `AffiliateLinkerSettings.php:55-59` defines a field with
only `id` and `label` — no `type`, no `description`, no `placeholder`. `FieldRenderer`
reads all three unguarded (`:69`, `:174`, `:181`), so rendering the Affiliate Linker
settings tab emits PHP warnings today from committed production data.

Two further defects surfaced while reading:

- **Defaults never apply to post meta.** `FieldRenderer:61` gates the default on
  `$data === false`, but `get_post_meta()` returns `''` for a missing key, never
  `false`. Only the options path can reach the default branch.
- **The metabox half of the renderer is unreachable.** `meta_box_content` and
  `save_meta_boxes` are driven by the `{post_type}_custom_fields` filter. Nothing
  registers it — not the theme, not the Bloom plugin, and nothing in the full
  production plugin set under `wordpress/wp-content/plugins/`, checked for both literal
  and concatenated registrations. The only two references in the entire install are the
  `apply_filters` calls that ask the question.

## Scope

**Theme only.** `FieldDefinition` lives permanently in the `PowerOfFamilies` namespace.

This is settled rather than provisional: the project's direction is to fold what is
usable from `pof-bloom-plugin` into the theme, so there is one home rather than a shared
one. That also retires the open question the review left on candidate 01 — a Composer
package, a third plugin, or a declared dependency between the two deploy units are all
moot. Candidate 01 becomes "absorb the plugin's Settings Screen into the theme's."

### Non-goals

- Touching the plugin's copy of the renderer. It converges by migration later, not by
  sharing an interface now.
- Preserving the metabox code path (see Deletions).
- Candidate 01 itself. 02 defines the type; 01 collapses the duplicated screens onto it.

## Design

### `FieldType` — backed enum

Fifteen types drawn from the existing switch, plus `None`:

`text`, `url`, `email`, `password`, `number`, `hidden`, `text_secret`, `textarea`,
`checkbox`, `checkbox_multi`, `radio`, `select`, `select_multi`, `image`, `color`, and
`None` for a field that renders no control.

Behavior currently encoded as scattered conditionals becomes methods on the enum:

| Method                    | Cases                                     | Replaces                              |
| ------------------------- | ----------------------------------------- | ------------------------------------- |
| `isMultiValue()`          | `checkbox_multi`, `select_multi`          | the `in_array($k, $data)` calls       |
| `describesBelowControl()` | `checkbox_multi`, `radio`, `select_multi` | the `in_array` at `FieldRenderer:174` |

An earlier draft of this design also carried a `usesOptions()` predicate, to replace the
four unguarded `$field['options']` loops. It was dropped while planning: once `options`
defaults to `[]`, iterating it needs no guard, so nothing would ever call the predicate.
The type removes the need rather than relocating it.

### `FieldDefinition` — final readonly class

```php
final readonly class FieldDefinition {
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
    ) {}

    public static function fromArray(array $field): self { /* ... */ }
}
```

`min` and `max` have no current user but are read on a live path for `number` fields.
They are kept because dropping them is a silent capability loss, not a YAGNI win.

`callback` is kept for the same reason, and was missed in the first draft of this design:
`Settings.php:218` reads `$field['callback']` and hands it to `register_setting()` as the
validation callback. No field declares one today, but dropping the property would quietly
remove the ability for any filter-supplied field to validate itself.

### Where arrays become typed

One boundary: `Settings::init_settings()`, immediately after `settings_fields()` applies
the `{token}_settings_fields` filter — so third-party contributions are typed on the same
path as ours. `register_settings()` then passes the object inside WordPress's args array:

```php
add_settings_field( $definition->id, $definition->label, [$this->renderer, 'display_field'],
    $this->token . '_settings', $section,
    ['field' => $definition, 'prefix' => $this->base] );
```

`display_field()` accepts `FieldDefinition|array` and calls `fromArray()` on an array, so
any direct caller keeps working.

### Behavior rules

- **Missing `type`** → `FieldType::None`, rendering no control. This preserves exactly
  what the Affiliate Linker's button row looks like today, minus the warnings, and turns
  an accident of switch fall-through into a named intent.
- **Unrecognised `type`** (e.g. `'txet'`) → throws `InvalidArgumentException` naming the
  offending value and the field's `id`. Absent means "no control"; misspelled means a
  mistake, and catching that is the reason 02 exists. This is the one rule that can fail
  on input that renders quietly today.
- **Missing `id`** → throws `InvalidArgumentException`. `id` is the one key with no
  sensible default: it names both the option and the form control, so a field without one
  cannot round-trip.
- **Defaults** → `get_option()` returning `false` falls back to `$definition->default`.
  Unchanged for the options path. The broken post-meta default rule leaves with the
  metabox path rather than being fixed.

## Deletions

The type exposes how much of `FieldRenderer` is unreachable. Removed:

| Removed                                                                         | Reason                                        |
| ------------------------------------------------------------------------------- | --------------------------------------------- |
| `meta_box_content`, `display_meta_box_field`, `save_meta_boxes`, `add_meta_box` | no registrant for `{post_type}_custom_fields` |
| `validate_field`                                                                | called only from `save_meta_boxes:311`        |
| `implements HookRegistrar` and `register()`                                     | only wired `save_post` → `save_meta_boxes`    |
| `Programs.php:62` — `$this->fieldRenderer?->register()`                         | renderer registers nothing                    |
| `display_field`'s `$post` branch and `$echo` parameter                          | only the metabox path supplied them           |

With `$post` always `false`, the `if ($post)` fork collapses to the `get_option` branch
and the return-a-string mode disappears. `display_field(array $args): void` is the whole
public surface. `FieldRenderer` stops needing WordPress hooks, which is what makes it
testable in isolation.

**Risk.** `apply_filters` is a public extension point. "Unreachable" means no consumer in
this install today; external code (an mu-plugin, another site) hooking
`{post_type}_custom_fields` would break. Accepted deliberately.

## Escaping

Five unescaped interpolations sit in the switch being rewritten, and are fixed as part
of the rewrite:

| Location                                                                                                                       | Fix                           |
| ------------------------------------------------------------------------------------------------------------------------------ | ----------------------------- |
| `$data` into `<textarea>` (`:97`)                                                                                              | `esc_textarea()`              |
| `$data` into the image field's attributes (`:157-160`)                                                                         | `esc_attr()` / `esc_url()`    |
| `description` into `<span class="description">` (`:175`, `:181`)                                                               | `wp_kses_post()`              |
| Option labels (`FieldRenderer.php:137`, `:152`, `:166`)                                                                        | `esc_html($label)`            |
| The image field's `$option_name` interpolations (`id=`/`name=`, `FieldRenderer.php:176-187`) and its static translated strings | `esc_attr()` / `esc_attr__()` |

The last two rows are strict hardening of previously-raw output, not behavior fixes:
option labels were interpolated raw, and the image field's own `id=`/`name=`
interpolations and static translated strings (`__()` → `esc_attr__()`) were too. Neither
changes rendered output for a well-formed value.

`wp_kses_post` rather than `esc_html` for descriptions: labels in this codebase
deliberately carry HTML (`AffiliateLinkerSettings.php:57` is an `<a class="button">`), so
descriptions plausibly do too, and escaping them outright would be a silent visual change.

## Testing

Test-driven — tests precede implementation. The payoff of 02 is that `FieldDefinition`
and `FieldType` need no WordPress at all.

`tests/test-FieldDefinition.php`

- every optional key absent → documented default
- missing `type` → `FieldType::None`
- misspelled `type` → throws
- `options` absent → `[]`, not a warning
- `default` absent → `null`
- the three enum predicates over every case

`tests/test-FieldRenderer.php`

- `display_field()` output per type, against real WordPress helpers
- a `None` field renders no control
- saved option present → rendered; absent → default rendered
- description placement above vs. below the control

Both files must be added to `phpunit.xml`'s `<testsuite>`, which names files
individually — a new test file is invisible to the suite until it is listed.

## Files

**New:** `inc/PowerOfFamilies/FieldDefinition.php`, `inc/PowerOfFamilies/FieldType.php`,
`tests/test-FieldDefinition.php`, `tests/test-FieldRenderer.php`

**Modified:** `inc/PowerOfFamilies/FieldRenderer.php`, `inc/PowerOfFamilies/Settings.php`,
`inc/PowerOfFamilies/Programs.php`, `phpunit.xml`

**Untouched:** `inc/PowerOfFamilies/Programs/AffiliateLinkerSettings.php` — the `None`
rule means its odd field needs no edit. That it survives unchanged is the check that the
rule was chosen correctly.

## Verification

`npm run test`, after `docker compose --profile testing build test` — candidate 06
(merged as `ba01519`) changed the test image, so a stale image will not reflect the new
harness.

Baseline to establish before writing any code, not assumed: 34 tests / 83 assertions was
the count through the pre-06 container path. Post-06, `npm run test` is expected to report
the same 34 with one skip caused by a missing theme build artifact; `npm run build:theme`
clears it. Record the real numbers from a clean run first — every later claim about "no
behavior change" is measured against them.

## Domain terms

**Field Definition** and **Field Type** enter the project's vocabulary here. `CONTEXT.md`
and `docs/adr/` do not exist yet; the review named these as natural first entries.

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

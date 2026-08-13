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
            $selected = $multiple ? in_array($key, (array) $value, false) : (string) $key === (string) $value;
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

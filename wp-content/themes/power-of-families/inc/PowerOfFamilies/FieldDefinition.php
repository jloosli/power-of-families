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

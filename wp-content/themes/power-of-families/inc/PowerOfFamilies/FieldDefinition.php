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
        if (isset($field['id']) && !is_scalar($field['id'])) {
            throw new \InvalidArgumentException('Field definition declares a non-scalar "id".');
        }

        $id = isset($field['id']) ? (string) $field['id'] : '';

        if ('' === $id) {
            throw new \InvalidArgumentException(
                'Field definition is missing a non-empty "id"; the id names both the stored option and the form control.'
            );
        }

        return new self(
            id: $id,
            type: self::resolveType($field, $id),
            label: self::text($field, 'label', $id),
            description: self::text($field, 'description', $id),
            placeholder: self::text($field, 'placeholder', $id),
            options: isset($field['options']) && is_array($field['options']) ? $field['options'] : [],
            default: $field['default'] ?? null,
            min: self::textOrNull($field, 'min', $id),
            max: self::textOrNull($field, 'max', $id),
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

        $declared = self::text($field, 'type', $id);
        $type = FieldType::tryFrom($declared);

        if (null === $type) {
            throw new \InvalidArgumentException(
                sprintf('Field "%s" declares unknown type "%s".', $id, $declared)
            );
        }

        return $type;
    }

    /**
     * Read a key as a string, rejecting anything that cannot become one.
     *
     * Casting is deliberately guarded rather than unconditional: `(string)` on
     * an array emits a warning, and on an object without __toString it raises a
     * plain \Error -- which is not a \TypeError, so it escapes the containment
     * around this factory and would fatal a request. Rejecting non-scalars here
     * keeps every failure mode of this class a catchable InvalidArgumentException.
     */
    private static function text(array $field, string $key, string $id): string
    {
        if (!isset($field[$key])) {
            return '';
        }

        if (!is_scalar($field[$key])) {
            throw new \InvalidArgumentException(
                sprintf('Field "%s" declares a non-scalar "%s"; only scalar values can be rendered.', $id, $key)
            );
        }

        return (string) $field[$key];
    }

    /**
     * As {@see self::text()}, but absent means null rather than the empty string.
     */
    private static function textOrNull(array $field, string $key, string $id): ?string
    {
        return isset($field[$key]) ? self::text($field, $key, $id) : null;
    }
}

<?php

namespace PowerOfFamilies\Programs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A {@see ProgramModule}'s contribution to the POF Settings screen.
 *
 * One implementation is one tab. The array is the legacy section shape --
 * {@see \PowerOfFamilies\Settings::settings_fields()} keys it by the module
 * and converts each field to a {@see \PowerOfFamilies\FieldDefinition}, so
 * fields declared here may stay arrays.
 */
interface ProgramSettings
{
    /**
     * @return array{title: string, description?: string, fields?: array}
     */
    public function getSettings(): array;
}

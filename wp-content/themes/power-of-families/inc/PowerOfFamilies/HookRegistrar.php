<?php

namespace PowerOfFamilies;

/**
 * Marker interface for any class whose job is to wire WordPress hooks.
 *
 * Constructors should be free of side effects (composition + assignments
 * only). All add_action / add_filter / add_theme_support / add_image_size
 * etc. calls belong in register().
 *
 * This lets tests construct an instance without globally mutating
 * $wp_filter, and lets functions.php control the order in which features
 * register themselves.
 */
interface HookRegistrar
{
    public function register(): void;
}

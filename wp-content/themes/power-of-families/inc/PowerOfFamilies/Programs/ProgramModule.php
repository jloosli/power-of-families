<?php

namespace PowerOfFamilies\Programs;

use PowerOfFamilies\HookRegistrar;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One of the optional feature modules listed under "Active Programs".
 *
 * Not to be confused with {@see EnrolledProgram}, which is a program a
 * customer has bought. These are the admin checkboxes:
 * {@see \PowerOfFamilies\Settings} instantiates the checked ones, cascades
 * `register()` to them, and folds their settings screens into its own.
 *
 * The contract is declared here because Settings used to infer it. It read
 * each class's constructor with ReflectionClass to decide whether to pass
 * the token, then called `getSettingsInstance()` -- a method no type
 * declared, so a module marked as having settings but missing that method
 * fataled every admin request. Both halves are stated instead of sniffed:
 * every module is constructed with the token, and every module answers
 * `settings()`.
 */
interface ProgramModule extends HookRegistrar
{
    /**
     * @param string $token Settings page slug / option prefix root. Modules
     *                      use it to namespace script handles; one that has
     *                      no use for it still accepts it, so that
     *                      construction is uniform across the registry.
     */
    public function __construct(string $token);

    /**
     * This module's settings screen, or null when it contributes none.
     */
    public function settings(): ?ProgramSettings;
}

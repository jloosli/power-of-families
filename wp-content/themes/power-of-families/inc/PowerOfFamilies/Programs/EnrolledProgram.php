<?php

namespace PowerOfFamilies\Programs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A program a user is enrolled in.
 *
 * This is what crosses the {@see ProgramMembership} seam. Not to be confused
 * with a program *module* (`AffiliateLinker`, `MyPrograms`) -- those are units
 * of theme functionality, this is a thing a user has subscribed to.
 */
final readonly class EnrolledProgram
{
    public function __construct(
        public string $name,
        public ProgramDescription $description,
    ) {
    }
}

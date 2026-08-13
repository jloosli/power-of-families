<?php

namespace PowerOfFamilies\Programs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Which programs a user is enrolled in.
 *
 * The seam between the theme and whatever holds memberships. Production answers
 * with {@see GroupsMembership}; tests answer with a fake, which is what lets the
 * `[pof_programs]` shortcode be exercised end to end.
 */
interface ProgramMembership
{
    /**
     * @param int|null $userId defaults to the current user.
     *
     * @return EnrolledProgram[]
     */
    public function programsFor(?int $userId = null): array;
}

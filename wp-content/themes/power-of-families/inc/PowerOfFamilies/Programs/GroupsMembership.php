<?php

namespace PowerOfFamilies\Programs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Memberships as held by the Groups plugin.
 *
 * Groups hands back two different shapes and this is the one place that knows it:
 * `Groups_Group::get_groups()` returns database rows, while `Groups_User->groups`
 * returns `Groups_Group` objects wrapping a row in a public `$group` property.
 * Both reduce to an {@see EnrolledProgram} here.
 */
final class GroupsMembership implements ProgramMembership
{
    /**
     * @return EnrolledProgram[]
     */
    public function programsFor(?int $userId = null): array
    {
        // Groups is a plugin and can be deactivated; the shortcode then shows
        // the "no subscriptions" message rather than failing.
        if (!class_exists('Groups_User')) {
            return [];
        }

        // Only null means "the current user". A literal 0 is WordPress for
        // "nobody", and answering it with the current user's programs would be
        // a surprising thing for this interface to do.
        if (null === $userId) {
            $userId = get_current_user_id();
        }

        return array_map(
            static fn(object $group): EnrolledProgram => new EnrolledProgram(
                (string) ($group->name ?? ''),
                ProgramDescription::parse(isset($group->description) ? (string) $group->description : null)
            ),
            $this->groupsFor($userId)
        );
    }

    /**
     * The raw group rows for a user.
     *
     * @return object[]
     */
    private function groupsFor(int $userId): array
    {
        if ($this->treatsAdministratorsAsMembers($userId)) {
            return $this->rows(\Groups_Group::get_groups());
        }

        // `Groups_User->groups` is null, not an empty array, for a user with no
        // memberships -- and also for a user id Groups cannot resolve. Mapping
        // over that directly is a TypeError, so normalise before unwrapping.
        $memberships = (new \Groups_User($userId))->groups;

        if (!is_array($memberships)) {
            return [];
        }

        return $this->rows(array_map(static fn($membership) => $membership->group, $memberships));
    }

    /**
     * Keep only the entries that are actually group rows.
     *
     * A membership row can outlive its group, in which case `Groups_Group`
     * leaves its public `$group` false rather than throwing; `get_groups()`
     * itself answers null on a query failure.
     *
     * @return object[]
     */
    private function rows(mixed $groups): array
    {
        return is_array($groups) ? array_values(array_filter($groups, 'is_object')) : [];
    }

    /**
     * Whether this user should see every program.
     *
     * `GROUPS_ADMINISTRATOR_OVERRIDE` is a Groups-wide switch, defined by
     * {@see MyPrograms::register()}.
     *
     * The check is `user_can($userId, …)` rather than `current_user_can()` so
     * the answer is a function of the user asked about. The two agree for the
     * only production call site, which asks about the current user -- but an
     * interface that takes a user id should not answer for a different one.
     */
    private function treatsAdministratorsAsMembers(int $userId): bool
    {
        return defined('GROUPS_ADMINISTRATOR_OVERRIDE')
            && (GROUPS_ADMINISTRATOR_OVERRIDE === true)
            && user_can($userId, 'administrator');
    }
}

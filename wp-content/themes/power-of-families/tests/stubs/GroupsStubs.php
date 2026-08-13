<?php

/**
 * Groups plugin stubs for unit testing.
 *
 * Lets GroupsMembership run under PHPUnit when the Groups plugin isn't
 * installed. Each stub is guarded with class_exists() so this file is safe to
 * load even if Groups is present.
 *
 * The shapes mirror the real plugin, including its asymmetry: Groups_Group's
 * static get_groups() answers with rows, while a Groups_User's `groups`
 * property answers with Groups_Group objects wrapping a row -- and answers
 * null, not an empty array, for a user with no memberships.
 *
 * @package Power_Of_Families
 */

// Defined in production by MyPrograms::register(); the adapter's
// administrator-override branch is unreachable without it.
if ( ! defined( 'GROUPS_ADMINISTRATOR_OVERRIDE' ) ) {
    define( 'GROUPS_ADMINISTRATOR_OVERRIDE', true );
}

if ( ! class_exists( 'Groups_Group' ) ) {
    /**
     * A single group, wrapping its database row.
     */
    class Groups_Group {

        /**
         * Rows keyed by group id, as the test set them up.
         *
         * @var array<int, object>
         */
        public static array $rows = array();

        /**
         * The wrapped row, or false when the id does not resolve -- which is
         * what the real plugin leaves behind for a stale membership row.
         *
         * @var object|false
         */
        public $group;

        public function __construct( $group_id ) {
            $this->group = self::$rows[ $group_id ] ?? false;
        }

        /**
         * Every group, as rows.
         *
         * @return object[]
         */
        public static function get_groups( $args = array() ) {
            return array_values( self::$rows );
        }

        /**
         * Build a row and register it under an id.
         */
        public static function seed( int $group_id, string $name, ?string $description ): object {
            self::$rows[ $group_id ] = (object) array(
                'group_id'    => $group_id,
                'name'        => $name,
                'description' => $description,
            );

            return self::$rows[ $group_id ];
        }

        public static function reset(): void {
            self::$rows = array();
        }
    }
}

if ( ! class_exists( 'Groups_User' ) ) {
    /**
     * A user's memberships.
     */
    class Groups_User {

        /**
         * Group ids per user id, as the test set them up.
         *
         * @var array<int, int[]>
         */
        public static array $memberships = array();

        public function __construct( private $user_id ) {}

        /**
         * Mirrors the real plugin's __get(): null when the user belongs to no
         * groups, an array of Groups_Group otherwise.
         */
        public function __get( $name ) {
            if ( 'groups' !== $name ) {
                return null;
            }

            $group_ids = self::$memberships[ $this->user_id ] ?? array();

            if ( empty( $group_ids ) ) {
                return null;
            }

            return array_map(
                static function ( $group_id ) {
                    return new Groups_Group( $group_id );
                },
                $group_ids
            );
        }

        /**
         * @param int[] $group_ids
         */
        public static function enroll( int $user_id, array $group_ids ): void {
            self::$memberships[ $user_id ] = $group_ids;
        }

        public static function reset(): void {
            self::$memberships = array();
        }
    }
}

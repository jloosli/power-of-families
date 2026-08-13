<?php

/**
 * Tests for the Groups membership adapter.
 *
 * This is the one place a change in the Groups plugin's shapes can break
 * /my-account/, so it is covered against tests/stubs/GroupsStubs.php rather
 * than left implicit behind the ProgramMembership seam.
 *
 * The "Groups is not installed" branch is not covered here: the stubs define
 * Groups_User for the whole suite, and a class cannot be unloaded.
 *
 * @package Power_Of_Families
 */

use PowerOfFamilies\Programs\GroupsMembership;

class test_GroupsMembership extends WP_UnitTestCase {

    private GroupsMembership $membership;

    protected function setUp(): void {
        parent::setUp();
        Groups_Group::reset();
        Groups_User::reset();
        $this->membership = new GroupsMembership();
    }

    protected function tearDown(): void {
        Groups_Group::reset();
        Groups_User::reset();
        parent::tearDown();
    }

    public function test_user_with_no_memberships_gets_no_programs() {
        $subscriber = self::factory()->user->create();

        // Groups answers null rather than an empty array here -- mapping over
        // that directly is a TypeError, which is what this asserts is guarded.
        $this->assertSame( array(), $this->membership->programsFor( $subscriber ) );
    }

    public function test_membership_row_becomes_an_enrolled_program() {
        $subscriber = self::factory()->user->create();
        Groups_Group::seed( 7, 'Goalsetting', "image: https://example.com/i.png\nhome: https://example.com/g" );
        Groups_User::enroll( $subscriber, array( 7 ) );

        $programs = $this->membership->programsFor( $subscriber );

        $this->assertCount( 1, $programs );
        $this->assertSame( 'Goalsetting', $programs[0]->name );
        $this->assertSame( 'https://example.com/i.png', $programs[0]->description->image() );
        $this->assertSame( 'https://example.com/g', $programs[0]->description->home() );
    }

    public function test_null_description_parses_to_an_empty_description() {
        $subscriber = self::factory()->user->create();
        Groups_Group::seed( 7, 'Goalsetting', null );
        Groups_User::enroll( $subscriber, array( 7 ) );

        $programs = $this->membership->programsFor( $subscriber );

        $this->assertNull( $programs[0]->description->image() );
        $this->assertNull( $programs[0]->description->home() );
    }

    /**
     * A membership row can outlive its group; Groups_Group then wraps false.
     */
    public function test_membership_row_for_a_deleted_group_is_dropped() {
        $subscriber = self::factory()->user->create();
        Groups_Group::seed( 7, 'Goalsetting', null );
        Groups_User::enroll( $subscriber, array( 7, 99 ) );

        $programs = $this->membership->programsFor( $subscriber );

        $this->assertCount( 1, $programs );
        $this->assertSame( 'Goalsetting', $programs[0]->name );
    }

    public function test_administrator_sees_every_group() {
        $administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $administrator );
        Groups_Group::seed( 7, 'Goalsetting', null );
        Groups_Group::seed( 8, 'Assessments', null );
        // Deliberately not enrolled in either.

        $names = array_map(
            static fn( $program ) => $program->name,
            $this->membership->programsFor( $administrator )
        );

        $this->assertSame( array( 'Goalsetting', 'Assessments' ), $names );
    }

    public function test_subscriber_sees_only_their_own_groups() {
        $subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $subscriber );
        Groups_Group::seed( 7, 'Goalsetting', null );
        Groups_Group::seed( 8, 'Assessments', null );
        Groups_User::enroll( $subscriber, array( 8 ) );

        $programs = $this->membership->programsFor( $subscriber );

        $this->assertCount( 1, $programs );
        $this->assertSame( 'Assessments', $programs[0]->name );
    }

    public function test_omitted_user_id_falls_back_to_the_current_user() {
        $subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $subscriber );
        Groups_Group::seed( 7, 'Goalsetting', null );
        Groups_User::enroll( $subscriber, array( 7 ) );

        $programs = $this->membership->programsFor();

        $this->assertCount( 1, $programs );
        $this->assertSame( 'Goalsetting', $programs[0]->name );
    }
}

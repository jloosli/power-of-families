<?php

/**
 * Tests for the MyPrograms shortcode.
 *
 * Closes issue #41: the [pof_programs] surface used to be untested because
 * membership was read by instantiating Groups inside a private method. It comes
 * through the ProgramMembership seam now, so all four branches are reachable
 * with a fake -- no Groups install required.
 *
 * @package Power_Of_Families
 */

use PowerOfFamilies\Programs\EnrolledProgram;
use PowerOfFamilies\Programs\MyPrograms;
use PowerOfFamilies\Programs\ProgramDescription;
use PowerOfFamilies\Programs\ProgramMembership;

/**
 * Answers with whatever the test enrolled the user in.
 */
class Fake_Program_Membership implements ProgramMembership {

    /** @var EnrolledProgram[] */
    public array $programs = array();

    public ?int $asked_for = null;

    public function programsFor( ?int $userId = null ): array {
        $this->asked_for = $userId;

        return $this->programs;
    }
}

class test_MyPrograms extends WP_UnitTestCase {

    private Fake_Program_Membership $membership;
    private MyPrograms $my_programs;

    protected function setUp(): void {
        parent::setUp();
        $this->membership  = new Fake_Program_Membership();
        $this->my_programs = new MyPrograms( null, $this->membership );
    }

    protected function tearDown(): void {
        // $shortcode_tags is a global the WP test case does not roll back.
        remove_shortcode( 'pof_programs' );
        parent::tearDown();
    }

    /**
     * @param EnrolledProgram[] $programs
     */
    private function enroll( array $programs ): int {
        $user_id = self::factory()->user->create();
        wp_set_current_user( $user_id );
        $this->membership->programs = $programs;

        return $user_id;
    }

    private function program( string $name, ?string $description ): EnrolledProgram {
        return new EnrolledProgram( $name, ProgramDescription::parse( $description ) );
    }

    // -------------------------------------------------------------------------
    // Logged-out and empty branches
    // -------------------------------------------------------------------------

    public function test_logged_out_visitor_is_asked_to_log_in() {
        wp_set_current_user( 0 );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( 'You need to log in to view your Programs.', $output );
        $this->assertStringNotContainsString( "class='program'", $output );
    }

    public function test_logged_out_visitor_never_reaches_membership() {
        wp_set_current_user( 0 );

        $this->my_programs->show_programs( array() );

        $this->assertNull( $this->membership->asked_for );
    }

    public function test_logged_in_user_with_no_programs_is_pointed_at_the_store() {
        $this->enroll( array() );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( "You haven't subscribed to any Programs.", $output );
        // wp_kses_post() rewrites the default message's attribute quoting.
        $this->assertStringContainsString( 'href="/store"', $output );
    }

    public function test_membership_is_asked_about_the_current_user() {
        $user_id = $this->enroll( array( $this->program( 'Goalsetting', null ) ) );

        $this->my_programs->show_programs( array() );

        $this->assertSame( $user_id, $this->membership->asked_for );
    }

    // -------------------------------------------------------------------------
    // Rendering enrolled programs
    //
    // The NULL-description case is issue #39's production fatal, now covered at
    // the surface that actually fatalled rather than one private method in.
    // -------------------------------------------------------------------------

    public function test_program_with_null_description_renders_without_fatal() {
        $this->enroll( array( $this->program( 'Goalsetting', null ) ) );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( 'Goalsetting', $output );
        $this->assertStringContainsString( "<div class='program'>", $output );
        $this->assertStringNotContainsString( '<img', $output );
    }

    public function test_program_renders_its_image_and_home_link() {
        $this->enroll(
            array(
                $this->program(
                    'Goalsetting',
                    "image: https://example.com/i.png\nhome: https://example.com/goalsetting"
                ),
            )
        );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( "href='https://example.com/goalsetting'", $output );
        $this->assertStringContainsString( "src='https://example.com/i.png'", $output );
        $this->assertStringContainsString( "class='alignleft'", $output );
        // Decorative: the link already carries the program name as text.
        $this->assertStringContainsString( "alt=''", $output );
    }

    public function test_program_without_a_home_link_renders_an_empty_href() {
        $this->enroll( array( $this->program( 'Goalsetting', 'image: https://example.com/i.png' ) ) );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( "<a href=''>", $output );
    }

    public function test_every_enrolled_program_is_rendered() {
        $this->enroll(
            array(
                $this->program( 'Goalsetting', 'home: https://example.com/one' ),
                $this->program( 'Assessments', 'home: https://example.com/two' ),
            )
        );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( 'Goalsetting', $output );
        $this->assertStringContainsString( 'Assessments', $output );
        $this->assertSame( 2, substr_count( $output, "<div class='program'>" ) );
    }

    public function test_program_name_is_escaped_and_unslashed() {
        $this->enroll( array( $this->program( "Ben\\'s <script>Program</script>", null ) ) );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringNotContainsString( '<script>', $output );
        $this->assertStringContainsString( 'Ben&#039;s', $output );
    }

    public function test_javascript_home_url_is_stripped() {
        $this->enroll( array( $this->program( 'Goalsetting', 'home: javascript:alert(1)' ) ) );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringNotContainsString( 'javascript:', $output );
    }

    // -------------------------------------------------------------------------
    // Shortcode attributes
    // -------------------------------------------------------------------------

    public function test_title_is_shown_by_default() {
        wp_set_current_user( 0 );

        $output = $this->my_programs->show_programs( array() );

        $this->assertStringContainsString( '<h2>My Programs</h2>', $output );
    }

    public function test_showtitle_false_omits_the_heading() {
        wp_set_current_user( 0 );

        $output = $this->my_programs->show_programs( array( 'showtitle' => 'false' ) );

        $this->assertStringNotContainsString( '<h2>', $output );
    }

    public function test_title_attribute_overrides_the_heading() {
        wp_set_current_user( 0 );

        $output = $this->my_programs->show_programs( array( 'title' => 'Your Stuff' ) );

        $this->assertStringContainsString( '<h2>Your Stuff</h2>', $output );
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function test_register_adds_the_shortcode() {
        $this->my_programs->register();
        $this->enroll( array( $this->program( 'Goalsetting', 'home: https://example.com/one' ) ) );

        $output = do_shortcode( '[pof_programs]' );

        $this->assertStringContainsString( 'Goalsetting', $output );
    }

    /**
     * Constructed the way Settings::loadActivePrograms() constructs it -- token
     * only -- the shortcode still renders, through the real GroupsMembership.
     */
    public function test_default_membership_is_wired_up() {
        $this->enroll( array() );

        $output = ( new MyPrograms( 'power_of_families' ) )->show_programs( array() );

        $this->assertStringContainsString( "You haven't subscribed to any Programs.", $output );
    }
}

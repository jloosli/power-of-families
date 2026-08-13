<?php

namespace PowerOfFamilies\Programs;

class MyPrograms implements ProgramModule
{

    private ProgramMembership $membership;

    /**
     * The token is nullable, and $membership is a second argument the
     * contract does not mention, because both are widenings PHP allows an
     * implementation to make: tests construct this class directly, with a
     * fake membership and no token.
     */
    public function __construct(private ?string $token = null, ?ProgramMembership $membership = null)
    {
        $this->membership = $membership ?? new GroupsMembership();
    }

    /**
     * This module is a shortcode; it has nothing to configure.
     */
    public function settings() : ?ProgramSettings
    {
        return null;
    }

    public function register(): void
    {
        if (!defined('GROUPS_ADMINISTRATOR_OVERRIDE')) {
            define('GROUPS_ADMINISTRATOR_OVERRIDE', true);
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode("pof_programs", [$this, 'show_programs']);
    }

    public function enqueue_scripts() : void
    {
        // Token is optional (e.g. when instantiated by tests). Without it we
        // have no namespaced handle to enqueue, so bail rather than register a
        // bogus "-frontend" script.
        if (empty($this->token)) {
            return;
        }
        wp_enqueue_script($this->token . '-frontend');
    }

    /**
     * Create shortcode for POF Programs
     */
    public function show_programs($atts) : string
    {
        $atts = shortcode_atts( [
            'showtitle'       => 'true',
            'title'           => 'My Programs',
            'notloggedin'     => 'Sorry. You need to log in to view your Programs.',
            'nosubscriptions' => "You haven't subscribed to any Programs. Go check out some of <a href='/store'>our Programs</a> and see what may be of use to you.",
        ], $atts );
        $showtitle       = $atts['showtitle'];
        $title           = esc_html( $atts['title'] );
        $notloggedin     = wp_kses_post( $atts['notloggedin'] );
        $nosubscriptions = wp_kses_post( $atts['nosubscriptions'] );
        $title = $showtitle == "true" ? "<h2>$title</h2>" : "";
        $output = "<div id='pof_userprograms'>$title\n";
        if (is_user_logged_in()) {
            $progs = $this->membership->programsFor(get_current_user_id());
            if ($progs) {
                foreach ($progs as $prog) {
                    $output .= $this->render_program($prog);
                }
            } else {
                $output .= "<div class='message'>$nosubscriptions</div>";
            }
        } else {
            $output .= "<div class='message'>$notloggedin</div>";
        }
        $output .= "</div>";

        return $output;
    }

    /**
     * One program's tile: its image, if it declares one, linked to its home page.
     *
     * The image gets `alt=""` rather than the program name: the same link already
     * carries that name as text, so announcing it twice is noise to a screen
     * reader. Decorative is the accurate description here.
     */
    private function render_program( EnrolledProgram $program ) : string
    {
        $image = $program->description->image();
        $home = $program->description->home();

        return sprintf(
            "<div class='program'><a href='%s'>%s%s</a></div>",
            esc_url( $home ?? '' ),
            null === $image
                ? ''
                : sprintf("<img class='alignleft' src='%s' width='88' height='88' alt='' />", esc_url($image)),
            esc_html( stripslashes($program->name) )
        );
    }

}

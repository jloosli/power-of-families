<?php

namespace PowerOfFamilies\Programs;

use PowerOfFamilies\HookRegistrar;

class MyPrograms implements HookRegistrar
{

    public static mixed $settingsInstance = null;

    public function __construct(private ?string $token = null) {}

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
            $progs = $this->getCurrentUserPrograms(get_current_user_id());
            if ($progs) {
                foreach ($progs as $prog) {
                    $meta = $this->getProgramMetaFromDescription($prog->description);
                    $image = '';
                    if (!empty($meta->image)) {
                        $image = sprintf("<img class='alignleft' src='%s' width='88' height='88' />", esc_url($meta->image));
                    }
                    $output .= sprintf(
                        "<div class='program'><a href='%s'>%s%s</a></div>",
                        esc_url( !empty($meta->home) ? $meta->home : '' ),
                        $image,
                        esc_html( stripslashes($prog->name) )
                    );
                }
                $output .= "</div>";
            } else {
                $output .= "<div class='message'>$nosubscriptions</div>";
            }
        } else {
            $output .= "<div class='message'>$notloggedin</div>";
        }
        $output .= "</div>";

        return $output;
    }

    private function getProgramMetaFromDescription( ?string $description ) : \stdClass
    {
        $meta = new \stdClass();
        $lines = explode("\n", $description ?? '');
        foreach ($lines as $line) {
            $parts = array_map('trim', explode(":", $line));
            if (count($parts) >= 2) {
                $attr = strtolower(array_shift($parts));
                $val = implode(':', $parts);
                $meta->{$attr} = $val;
            }
        }
        return $meta;
    }

    private function getCurrentUserPrograms( ?int $user_id = null ) : array
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $the_programs = [];
        if (class_exists('Groups_User')) {
            if (
                defined('GROUPS_ADMINISTRATOR_OVERRIDE')
                && (GROUPS_ADMINISTRATOR_OVERRIDE === true)
                && current_user_can('administrator')
            ) {
                $the_programs = \Groups_Group::get_groups();
            } else {
                $groups_user = new \Groups_User($user_id);
                // get groups objects
                $user_groups = $groups_user->groups;
                $the_programs = array_map(function ($group) {
                    return $group->group;
                }, $user_groups);
            }
        }
        return $the_programs;
    }

}

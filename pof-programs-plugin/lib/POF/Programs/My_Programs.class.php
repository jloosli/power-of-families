<?php

namespace POF\Programs;
/**
 * Helper utilities for diplaying My Programs
 * @property \POF\Power_of_Families_Programs parent
 */
class My_Programs
{

    public static $settingsInstance;

    function __construct($parent = null)
    {
        $this->parent = $parent;

        if (!defined('GROUPS_ADMINISTRATOR_OVERRIDE')) {
            define('GROUPS_ADMINISTRATOR_OVERRIDE', true);
        }

        //Actions
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        //Filters
        //Short codes
        add_shortcode("pof_programs", array($this, "show_programs"));
        //Scripts
//        $this->add_users();

//        add_action('wp_loaded', [$this, 'add_users']);

    }

    function enqueue_scripts()
    {
        wp_enqueue_script($this->parent->token . '-frontend');

    }

    /**
     * Create shortcode for POF Programs
     */
    function show_programs($atts)
    {
        /** @var $showtitle bool
         * @var $title           string
         * @var $notloggedin     string
         * @var $nosubscriptions string
         */
        extract(shortcode_atts(array(
            'showtitle' => "true",
            'title' => 'My Programs',
            'notloggedin' => "Sorry. You need to log in to view your Programs.",
            'nosubscriptions' => "You haven't subscribed to any Programs. Go check out some of <a href='/store'>our Programs</a> and see what may be of use to you."
        ), $atts));
        $title = $showtitle == "true" ? "<h2>$title</h2>" : "";
        $output = "<div id='pof_userprograms'>$title\n";
        $output .= "<style>#pof_userprograms .program {display: block; clear: both;}</style>";
        if (is_user_logged_in()) {
            $progs = $this->getCurrentUserPrograms(get_current_user_id());
            if ($progs) {
                foreach ($progs as $prog) {
                    $meta = $this->getProgramMetaFromDescription($prog->description);
                    $image = '';
                    if (!empty($meta->image)) {
                        $image = sprintf("<img class='alignleft' src='%s' width='88' height='88' />", $meta->image);
                    }
                    $output .= sprintf("<div class='program'><a href='%s'>%s%s</a></div>", !empty($meta->home) ? $meta->home : '', $image, stripslashes($prog->name));
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

    private function getProgramMetaFromDescription($description)
    {
        $meta = new \stdClass();
        $lines = explode("\n", $description);
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

    private function getCurrentUserPrograms($user_id = null)
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

    function add_users()
    {
        if (($handle = fopen(dirname(__FILE__) . '/../../../members_20171103_164946.csv', 'r'))) {
            $line = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($line++ == 0) {
                    continue;
                }
                $username = $data[0];
                $levels = explode("\n", $data[5]);
                $user = \get_user_by('login', $username);
                if ($user) {
                    foreach ($levels as $level) {
                        $the_group = \Groups_Group::read_by_name($level);
                        \Groups_User_Group::create([
                            'user_id' => $user->ID,
                            'group_id' => $the_group->group_id
                        ]);
                    }
                }


            }
        }
    }

}


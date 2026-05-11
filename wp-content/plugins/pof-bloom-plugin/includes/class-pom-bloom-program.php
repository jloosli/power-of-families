<?php
/**
 * Created by PhpStorm.
 * User: jloosli
 * Date: 11/6/14
 * Time: 12:05 PM
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

function pom_bloom_create_goalset() {
    $bloom = POM_Bloom();
    $bloom->program->bloom_create_goalset();
}

class POM_Bloom_Program {
    /**
     * Message displayed if user isn't subscribed to the program.
     * @var string
     * @since 1.0.0
     */
    public $msg_not_subscribed;

    /**
     * Message displayed if user isn't logged in.
     * @var string
     * @since 1.0.0
     */
    public $msg_not_logged_in;

    /**
     * Array of available routes
     * @var array
     */
    public $routes;

    /**
     * Partial directory
     * @var string
     */
    public $partial_directory;

    /**
     * Partial variables used in partials
     * @var mixed
     */
    protected $pvars;

    /**
     * Number of weeks to show in overview
     * @var int
     */
    protected $weeks_to_show;

    public function __construct( $parent ) {
        $this->parent = $parent;
        $this->setup();

        add_shortcode( 'bloom-program', array( $this, 'bloom_shortcode_func' ) );
        add_action( 'wp_ajax_pom_bloom', array( $this, 'ajax_callback' ) );
        add_action( 'init', array( $this, 'setup_cron' ) );

    }

    function setup_cron() {
        $event = 'bloom_create_goalset_event';
        $bloom_cron = 'pom_bloom_create_goalset';
        if(wp_next_scheduled($event)) {
        }
        if ( !( wp_next_scheduled( $event ) ) ) {
            wp_schedule_event( time(), 'daily', $event );
        }
        add_action( $event, $bloom_cron );
    }

    function deactive_cron() {
        wp_clear_scheduled_hook( 'bloom_create_goalset_event' );
    }


    function bloom_create_goalset() {
        $dow = 6; // Saturday
        if ( (int) date( 'w' ) === $dow ) {
            $this->addGoalset( date( 'Y-m-d', strtotime( 'next Monday' ) ) );
        }
    }


    public function bloom_shortcode_func() {
        $this->enqueue_stuff();

        if ( !is_user_logged_in() ) {
            return sprintf( $this->msg_not_logged_in );
        }
        if ( !$this->check_access() ) {
            return sprintf( $this->msg_not_subscribed, get_page_link( get_option( $this->parent->settings->base . 'sales_page' ) ) );
        }

        $route = $this->getRoute();
        $html  = $this->page( $route );

        return $html;
    }

    public function ajax_callback() {
        check_ajax_referer( 'pom_bloom_nonce', 'nonce' );
        $result = array( 'success' => false );
        switch ( $_POST['route'] ) {
            case 'preferences':
                update_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', sanitize_text_field( $_POST['preference'] ) );
                $result = array( 'success' => true );
                break;
            case 'assessments':
                $user               = get_current_user_id();
                $average            = [ 'sum' => 0, 'count' => 0 ];
                $assessment_results = array_map( function ( $a ) use ( &$average ) {
                    if ( (int) $a['value'] > 0 ) {
                        $average['sum'] += (int) $a['value'];
                        $average['count'] ++;
                    }

                    return [
                        'q'      => (int) str_replace( "q_", "", $a['name'] ),
                        'rating' => (int) $a['value']
                    ];
                }, $_POST['assessment'] );

                $result = [
                    'assessment_date'    => date( "Y-m-d H:i:s" ),
                    'average'            => $average['count'] > 0 ? round( $average['sum'] / $average['count'], 1 ) : 0,
                    'assessment_results' => $assessment_results
                ];
                add_user_meta( $user, $this->parent->_token . '_assessment', $result );
                $result['success'] = true;
                break;
            case 'goal_suggestions':
                $goals   = get_posts( [
                    'posts_per_page' => - 1,
                    'post_type'      => 'bloom_suggested',
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'tax_query'      => array(
                        array(
                            'taxonomy'         => 'bloom-categories',
                            'field'            => 'id',
                            'terms'            => absint( $_POST['category_id'] ),
                            'include_children' => false
                        )
                    )
                ] );
                $cleaned = array_map( function ( $goal ) {
                    return [
                        'id'         => $goal->ID,
                        'suggestion' => $goal->post_title,
                        'per_week'   => (int) get_post_meta( $goal->ID, 'bloom_per_week', true )
                    ];
                }, $goals );
                $result  = [
                    'goals'   => $cleaned,
                    'success' => true
                ];
                break;
            case 'add_goals':
                $current_user = get_current_user_id();
                $data         = array();
                parse_str( wp_unslash( $_POST['data'] ?? '' ), $data );
                $goalCount = isset( $data['goals'] ) ? count( $data['goals'] ) : 0;
                $goals     = [ ];
                for ( $i = 0; $i < $goalCount; $i ++ ) {
                    $goals[] = [
                        'suggestion_id' => $data['suggestions'][ $i ],
                        'category_id'   => $data['cat'][ $i ],
                        'goal'          => $data['goals'][ $i ],
                        'per_week'      => $data['per_week'][ $i ]
                    ];
                }
                array_map( function ( $goal ) use ( $current_user, $data ) {
                    $post    = [
                        'post_title'  => sanitize_text_field( $goal['goal'] ),
                        'post_author' => $current_user,
                        'tax_input'   => array(
                            'bloom-categories' => array( absint( $goal['category_id'] ) ),
                            'bloom-goalsets'   => array( absint( $data['goalset'] ) )
                        ),
                        'post_status' => 'publish',
                        'post_type'   => 'bloom-user-goals'
                    ];
                    $goal_id = wp_insert_post( $post, true );
                    add_post_meta( $goal_id, 'per_week', absint( $goal['per_week'] ) );
                    add_post_meta( $goal_id, 'suggested_id', absint( $goal['suggestion_id'] ) );
                }, $goals );
                // Serendipity
                $post        = [
                    'post_title'  => '',
                    'post_author' => $current_user,
                    'post_status' => 'publish',
                    'post_type'   => 'bloom-user-goals',
                    'tax_input'   => array(
                        'bloom-goalsets' => array( absint( $data['goalset'] ) )
                    ),

                ];
                $is_advanced = get_user_meta( $current_user, $this->parent->_token . 'preference_level', true ) === 'advanced';
                for ( $i = $is_advanced ? 2 : 1; $i > 0; $i -- ) {
                    $goal_id = wp_insert_post( $post, true );
                }
                $result['success'] = true;
                break;
            case 'update_goals':
                $goal_id = absint( $_POST['goal'] ?? 0 );
                if ( ! $this->user_owns_bloom_goal( $goal_id ) ) {
                    $result = array( 'success' => false, 'error' => 'forbidden' );
                    break;
                }
                $completed = get_post_meta( $goal_id, 'completed', true );
                if ( empty( $completed ) ) {
                    $completed = [ ];
                }
                $set = isset( $_POST['set'] ) && $_POST['set'] === 'true';
                $completed[ sanitize_key( $_POST['day'] ?? '' ) ] = $set;
                update_post_meta( $goal_id, 'completed', $completed );
                $result['success'] = true;
                $result['set']     = $set;
                break;
            case 'update_serendipity':
                $goal_id = absint( $_POST['id'] ?? 0 );
                if ( ! $this->user_owns_bloom_goal( $goal_id ) ) {
                    $result = array( 'success' => false, 'error' => 'forbidden' );
                    break;
                }
                $args              = [
                    'ID'         => $goal_id,
                    'post_title' => sanitize_text_field( wp_unslash( $_POST['serendipity'] ?? '' ) )
                ];
                $update            = wp_update_post( $args );
                $result['success'] = true;
                break;
        }
        die( json_encode( $result ) );
    }

    /**
     * Authorize a write against a bloom-user-goals post: the post must
     * exist, be the expected post type, and either be authored by the
     * current user or be editable via standard WP capabilities.
     */
    protected function user_owns_bloom_goal( int $goal_id ) : bool {
        if ( ! $goal_id ) {
            return false;
        }
        $post = get_post( $goal_id );
        if ( ! $post || $post->post_type !== 'bloom-user-goals' ) {
            return false;
        }
        if ( (int) $post->post_author === get_current_user_id() ) {
            return true;
        }
        return current_user_can( 'edit_post', $goal_id );
    }

    protected function getRoute() {
        if ( empty( $_GET ) || empty( $_GET['page'] ) ) {
            $route = array_filter( $this->routes, function ( $route ) {
                return isset( $route['default'] ) && $route['default'] === true;
            } );
        } else {
            $route = array_filter( $this->routes, function ( $route ) {
                return $route['page'] === strtolower( $_GET['page'] );
            } );
            if ( !$route ) {
                $route = [ [ 'page' => 'bad', 'template' => 'bad' ] ];
            }
        }

        return end( $route );

    }

    protected function page( $route ) {
        $vars = [ ];
        if ( $route['vars'] && is_object( $route['vars'] ) && $route['vars'] instanceof Closure ) {
            $vars = $route['vars']();
        }
        $template = $route['template'] ? $route['template'] : $route['page'];
        $html     = "<div id='bloom'>\n";
        $html .= $this->get_partial( 'nav', [ 'active' => $route['page'] ] );
        $html .= $this->get_partial( $template, $vars );
        $html .= $this->get_partial( 'footer', [ ] );
        $html .= "</div>";

        return $html;
    }

    protected function enqueue_stuff() {
        // Enqueue scripts and css here.
        wp_enqueue_script( 'jquery-dotdotdot' );
        wp_enqueue_script( 'underscore' );
        wp_enqueue_script( $this->parent->_token . '-frontend' );
        wp_localize_script(
            $this->parent->_token . '-frontend',
            'POM_BLOOM',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'current_user' => get_current_user_id(),
                'nonce'        => wp_create_nonce( 'pom_bloom_nonce' )
            )
        );
    }

    protected function check_access() {
        return current_user_can( "manage_options" ) ||
               ( function_exists( 'wlmapi_is_user_a_member' ) &&
                 wlmapi_is_user_a_member(
                     get_option( $this->parent->settings->base . 'membership_level' ),
                     get_current_user_id()
                 )
               );
    }

    /**
     * Get template partial
     *
     * @param $partial
     *
     * @return string
     */
    protected function get_partial( $partial, $vars = [ ] ) {
        $html       = '';
        $thePartial = $this->partial_directory . $partial . ".php";
        if ( file_exists( $thePartial ) ) {
            extract( $vars );
            ob_start();
            include $thePartial;
            $html = ob_get_clean();

        }

        return $html;
    }

    protected function generate_categories() {
        $terms = get_terms( 'bloom-categories',
            array(
                'hide_empty' => false,
                'orderby'    => 'slug',
                'order'      => 'ASC'
            )
        );

        return $terms;
    }

    protected function generate_hierarchy( $terms, $parent = 0 ) {
        $h = [ ];
        foreach ( $terms as $term ) {
            if ( (int) $term->parent === (int) $parent ) {
                $questions = $this->get_category_questions( $term->term_id );
                $node = [
                    'name'      => $term->name,
                    'questions' => $questions,
                    'sections'  => $this->generate_hierarchy( $terms, $term->term_id )
                ];

                // Only add node if there are questions and/or sections
                if(count($node['questions']) || count($node['sections'])) {
                    $h[] = $node;
                }
            }
        }

        return $h;
    }

    protected function format_questionaire_hierarchy( $hierarchy, $level = 0 ) {
        $html = '';
        foreach ( $hierarchy as $sect ) {
            $html .= "<fieldset class='level level_$level'>\n";
            $html .= "<legend>{$sect['name']}</legend>\n";
            foreach ( $sect['questions'] as $q ) {
                $quest = $q->post_title;
                $qid   = $q->ID;
                $html .= "<div id='q_{$qid}_group' class='qgroup'>\n";
                $html .= "<strong>$quest</strong>\n";
//                    $html .= "<input type='hidden' name='q_{$qid}' value='x' />\n";
                $html .= "<table class='scale'>\n";
                $html .= "<tr>\n";
                $html .= "<th title='Help!'><label for='q_{$qid}_1'>1</label></th>\n";
                $html .= "<th title='Not so great'><label for='q_{$qid}_2'>2</label></th>\n";
                $html .= "<th title='Okay'><label for='q_{$qid}_3'>3</label></th>";
                $html .= "<th title='Pretty good'><label for='q_{$qid}_4'>4</label></th>\n";
                $html .= "<th title='Wonderful'><label for='q_{$qid}_5'>5</label></th>\n";
                $html .= "<th class='empty' rowspan='2'>&nbsp;</th>\n";
                $html .= "<th title='Not Applicable'><label for='q_{$qid}_0'>N/A</label></th>\n";
                $html .= "</tr>\n";
                $html .= "<tr>\n";
                $html .= "<th title='Help!'><input type='radio' name='q_{$qid}' value='1' id='q_{$qid}_1'/></th>\n";
                $html .= "<th title='Not so great'><input type='radio' name='q_{$qid}' value='2' id='q_{$qid}_2'/></th>\n";
                $html .= "<th title='Okay'><input type='radio' name='q_{$qid}' value='3' id='q_{$qid}_3'/></th>\n";
                $html .= "<th title='Pretty good'><input type='radio' name='q_{$qid}' value='4' id='q_{$qid}_4'/></th>\n";
                $html .= "<th title='Wonderful'><input type='radio' name='q_{$qid}' value='5' id='q_{$qid}_5'/></th>\n";
                $html .= "<th title='Not Applicable'><input type='radio' name='q_{$qid}' value='0' id='q_{$qid}_0'/></th>\n";
                $html .= "</tr>\n";
                $html .= "</table>\n";
                $html .= "</div>\n";
            }
            if ( $sect['sections'] ) {
                $html .= $this->format_questionaire_hierarchy( $sect['sections'], $level + 1 );
            }
            $html .= "</fieldset>";
        }

        return $html;
    }

    protected function get_category_questions( $cat_id ) {
        $args = [
            'post_type' => 'bloom-assessments',
            'orderby'   => 'title',
            'tax_query' => [
                [
                    'taxonomy'         => 'bloom-categories',
                    'field'            => 'term_id',
                    'terms'            => $cat_id,
                    'include_children' => false
                ]
            ]
        ];

        $posts = get_posts( $args );

        return $posts;
    }

    protected function getAssessmentResponses( $question, $assessments ) {
        return array_map( function ( $assessment ) use ( $question ) {
            return array_filter( $assessment['assessment_results'], function ( $result ) use ( $question ) {
                return $result['q'] === $question->ID;
            } );
        }, $assessments );
    }

    protected function getSubCategories( $id, $level = 0 ) {
        $theTerms = [ ];
        $terms    = get_terms( 'bloom-categories', array(
            'hide_empty' => false,
            'parent'     => $id
        ) );
        foreach ( $terms as $term ) {
            $theTerms[] = [
                'id'   => $term->term_id,
                'name' => str_repeat( '-', $level * 2 ) . $term->name
            ];

            $subs     = $this->getSubCategories( $term->term_id, $level + 1 );
            $theTerms = array_merge( $theTerms, $subs );
        }

        return $theTerms;
    }

    protected function getCategoryAverages( $user_id, $category_id, $last = 4 ) {
        $assessments    = $this->getAssessments( $user_id );
        $category_terms = get_terms( 'bloom-categories', [
            'hide_empty' => false,
            'parent'     => $category_id
        ] );
//        $categories = array_merge([$category_id],array_map(function($term) {
//            return $term->term_id;
//        }, $category_terms));
        $args = [
            'post_type' => 'bloom-assessments',
            'tax_query' => [
                [
                    'taxonomy'         => 'bloom-categories',
                    'field'            => 'term_id',
                    'terms'            => $category_id,
                    'include_children' => true
                ]
            ]
        ];

        $questions_raw = get_posts( $args );
        $questions     = array_map( function ( $q ) {
            return $q->ID;
        }, $questions_raw );
        $averages      = [ ];
        foreach ( $assessments as $a ) {
            $avg = [ 0, 0 ];
            foreach ( $a['assessment_results'] as $r ) {
                if ( in_array( $r['q'], $questions ) and $r['rating'] > 0 ) {
                    $avg[0] += $r['rating'];
                    $avg[1] ++;
                }
            }
            $averages[ $a['assessment_date'] ] = $avg[1] > 0 ? round( $avg[0] / $avg[1], 1 ) : 0;
        }

        return $averages;
    }

    protected function getAssessments( $user_id, $number = 4 ) {
        $assessments = get_user_meta( $user_id, $this->parent->_token . '_assessment' );
        if ( $number ) {
            $assessments = array_slice( $assessments, - $number );
        }
        $assessments = array_reverse( $assessments );

        return $assessments;
    }

    public function addGoalset( $goalset = null ) {

        if ( empty( $goalset ) ) {
            $goalset = date( "Y-m-d" );
        }
        wp_insert_term( $goalset, 'bloom-goalsets' );
    }

    protected function getLatestGoalset() {
        $latest = get_terms( 'bloom-goalsets', array(
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'DESC',
                'number'     => 1
            )
        );

        return $latest[0];
    }


    protected function canUserAddGoals( $user_id ) {
        $latest = $this->getLatestGoalset();
        $posts  = get_posts( [
            'author'    => $user_id,
            'post_type' => 'bloom-user-goals',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bloom-goalsets',
                    'field'    => 'id',
                    'terms'    => array( $latest->term_id )
                )
            )
        ] );

        return count( $posts ) === 0;
    }

    protected function setup() {
        $this->msg_not_subscribed = <<<MESSAGE
Sorry. You're not currently subscribed to this program. Please go to <a href='%s'>Bloom Program Page</a> for more information.
MESSAGE;
        $this->msg_not_logged_in  = <<<MESSAGE
Looks like you're not logged in. Please try logging in above for this program to display.
MESSAGE;
        $this->partial_directory  = __DIR__ . '/../assets/partials/';
        $this->weeks_to_show      = 4;

        $this->routes = [
            [
                'page'     => 'overview',
                'template' => 'overview',
                'default'  => true,
                'vars'     => function () {
                    $goalsets = array_slice( get_terms( 'bloom-goalsets', array(
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'DESC'
                        ) ),
                        0,
                        $this->weeks_to_show
                    );
                    $terms    = array_map( function ( $set ) {
                        return $set->term_id;
                    }, $goalsets );
                    $goals    = get_posts( [
                        'post_type'      => 'bloom-user-goals',
                        'author'         => get_current_user_id(),
                        'posts_per_page' => - 1,
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'bloom-goalsets',
                                'field'    => 'id',
                                'terms'    => $terms
                            )
                        )

                    ] );

                    $grouped = [ ];
                    array_map( function ( $goal ) use ( &$grouped ) {
                        $goal->category     = wp_get_post_terms( $goal->ID, 'bloom-categories', [ 'fields' => 'all' ] )[0];
                        $goal->goalset      = wp_get_post_terms( $goal->ID, 'bloom-goalsets', [ 'fields' => 'all' ] )[0];
                        $goal->completed    = get_post_meta( $goal->ID, 'completed', true );
                        $goal->per_week     = get_post_meta( $goal->ID, 'per_week', true );
                        $goal->is_completed = is_null( $goal->category ) ? // Serendipity if null
                            strlen( $goal->post_title ) > 0
                            : array_sum( (array) $goal->completed ) >= $goal->per_week;

                        $grouped[ $goal->goalset->name ][ $goal->category->name ? $goal->category->name : 'serendipity' ][] = $goal;
                    }, $goals );

                    return [
                        'current_user' => wp_get_current_user(),
                        'goalsets'     => $goalsets,
                        'goals'        => $grouped,
                        'categories'   => get_terms( 'bloom-categories', array(
                            'hide_empty' => false,
                            'parent'     => 0,
                            'orderby'    => 'slug'
                        ) )
                    ];
                }
            ],
            [
                'page'     => 'preferences',
                'template' => 'preferences',
                'vars'     => function () {
                    return [
                        'current_user'     => wp_get_current_user(),
                        'preference_level' =>
                            get_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', true )
                    ];
                }
            ],
            [
                'page'     => 'instructions',
                'template' => 'instructions',
                'vars'     => function () {
                    return array();
                }
            ],
            [
                'page'     => 'serendipity-examples',
                'template' => 'serendipity-examples',
                'vars'     => function () {
                    return array();
                }
            ],
            [
                'page'     => 'goals.set',
                'template' => 'goals.set',
                'vars'     => function () {
                    wp_enqueue_script( 'google-jsapi', 'https://www.google.com/jsapi' );
                    $goalCategories = [ ];
                    $categories     = get_terms( 'bloom-categories', array(
                            'hide_empty' => false,
                            'parent'     => 0,
                            'orderby'    => 'slug',
                            'order'      => 'ascending'
                        )
                    );
                    $level          = get_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', true );
                    foreach ( $categories as $cat ) {
                        $goalCategories[] = [
                            'id'       => $cat->term_id,
                            'name'     => $cat->name,
                            'goal_num' => 1
                        ];
                        if ( $level === 'advanced' && $cat !== end( $categories ) ) {
                            $copy = end( $goalCategories );
                            $copy['goal_num'] ++;
                            $goalCategories[] = $copy;
                        }
                    }

                    return [
                        'current_user'    => wp_get_current_user(),
                        'current_goalset' => '2014-01-01',
                        'categories'      => $goalCategories
                    ];
                }
            ],
            [
                'page'     => 'assessments.create',
                'template' => 'assessments.create',
                'vars'     => function () {
                    $categories = $this->generate_categories();
                    $hierarchy  = $this->generate_hierarchy( $categories );
                    $formatted  = $this->format_questionaire_hierarchy( $hierarchy );

                    return [
                        'current_user'        => wp_get_current_user(),
                        'generated_questions' => $formatted

                    ];
                }
            ],
            [
                'page'     => 'assessments',
                'template' => 'assessments',
                'vars'     => function () {
                    $categories = $this->generate_categories();
                    $hierarchy  = $this->generate_hierarchy( $categories );

                    $assessments = $this->getAssessments( get_current_user_id() );

                    return [
                        'meta'        => get_user_meta( get_current_user_id(), $this->parent->_token . '_assessment' ),
                        'assessments' => $assessments,
                        'hierarchy'   => $hierarchy
                    ];
                }
            ],
            [
                'page'     => 'goals.update',
                'template' => 'goals.update',
                'vars'     => function () {

                    $goalset  = $_GET['goalset'];
                    $goals    = get_posts( [
                        'posts_per_page' => - 1,
                        'post_type'      => 'bloom-user-goals',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'bloom-goalsets',
                                'field'    => 'slug',
                                'terms'    => $goalset
                            )
                        ),
                        'author'         => get_current_user_id()
                    ] );
                    $modified = array_map( function ( $goal ) {
                        $goal->category  = wp_get_post_terms( $goal->ID, 'bloom-categories', [ 'fields' => 'all' ] )[0];
                        $goal->goalset   = wp_get_post_terms( $goal->ID, 'bloom-goalsets', [ 'fields' => 'all' ] )[0];
                        $goal->completed = get_post_meta( $goal->ID, 'completed', true );
                        $goal->per_week  = get_post_meta( $goal->ID, 'per_week', true );

                        return $goal;
                    }, $goals );

                    $dow = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

                    return [
                        'goals' => $modified,
                        'dow'   => $dow
                    ];
                }
            ],
            [
                'page' => 'conversion'
            ]
        ];

    }

}
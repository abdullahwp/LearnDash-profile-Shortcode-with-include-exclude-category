<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Builds the `[ld_profile]` shortcode output with category filtering.
 */
function learndash_profile_with_categories( $atts = array(), $content = '', $shortcode_slug = 'ld_profile' ) {
    global $learndash_shortcode_used;

    // Ensure user is logged in.
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $defaults = array(
        'user_id'            => get_current_user_id(),
        'per_page'           => false,
        'order'              => 'DESC',
        'orderby'            => 'ID',
        'course_points_user' => 'yes',
        'expand_all'         => false,
        'profile_link'       => 'yes',
        'show_header'        => 'yes',
        'show_quizzes'       => 'yes',
        'show_search'        => 'yes',
        'search'             => '',
        'quiz_num'           => false,
        'include_category'   => '', // New attribute for included categories
        'exclude_category'   => '', // New attribute for excluded categories
    );
    
    $atts = wp_parse_args( $atts, $defaults );

    // Additional filters for attributes
    $atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

    // Ensure the user ID is valid
    if ( ( (int) get_current_user_id() !== (int) $atts['user_id'] ) && ( ! learndash_is_admin_user( get_current_user_id() ) ) ) {
        if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
            if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $atts['user_id'] ) ) {
                $atts['user_id'] = get_current_user_id();
            }
        } else {
            $atts['user_id'] = get_current_user_id();
        }
    }

    // Simplified boolean checks
    $enabled_values = array( 'yes', 'true', 'on', '1' );
    $atts['expand_all'] = in_array( strtolower( $atts['expand_all'] ), $enabled_values, true );
    $atts['show_header'] = in_array( strtolower( $atts['show_header'] ), $enabled_values, true ) ? 'yes' : false;
    $atts['show_search'] = in_array( strtolower( $atts['show_search'] ), $enabled_values, true ) ? 'yes' : false;
    $atts['course_points_user'] = in_array( strtolower( $atts['course_points_user'] ), $enabled_values, true ) ? 'yes' : false;
    $atts['profile_link'] = in_array( strtolower( $atts['profile_link'] ), $enabled_values, true );
    $atts['show_quizzes'] = in_array( strtolower( $atts['show_quizzes'] ), $enabled_values, true );

    // Handle per_page and quiz_num settings
    $atts['per_page'] = $atts['per_page'] ? intval( $atts['per_page'] ) : LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
    $atts['quiz_num'] = $atts['quiz_num'] ? intval( $atts['quiz_num'] ) : LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );

    // Set pagination
    if ( $atts['per_page'] > 0 ) {
        $atts['paged'] = 1;
    } else {
        unset( $atts['paged'] );
        $atts['nopaging'] = true;
    }

    // Handle search functionality
    if ( 'yes' === $atts['show_search'] && !empty( $_GET['ld-profile-search'] ) ) {
        $atts['s'] = esc_attr( $_GET['ld-profile-search'] );
    } else {
        $atts['s'] = '';
    }

    if ( empty( $atts['user_id'] ) ) {
        return '';
    }

    $current_user = get_user_by( 'id', $atts['user_id'] );

    // Fetch user courses
    $user_courses = ld_get_mycourses( $atts['user_id'], $atts );

    // Filtering courses by include and exclude categories
    if ( !empty( $atts['include_category']) || !empty( $atts['exclude_category']) ) {
        $filtered_courses = array();
        
        // Get the include and exclude categories as arrays
        $include_categories = !empty( $atts['include_category']) ? explode(',', $atts['include_category']) : [];
        $exclude_categories = !empty( $atts['exclude_category']) ? explode(',', $atts['exclude_category']) : [];

        foreach ( $user_courses as $course ) {
            // Get course categories
            $course_categories = wp_get_post_terms( $course, 'ld_course_category', array( 'fields' => 'ids' ) );

            // Check if the course should be included
            $include = empty($include_categories) || array_intersect($course_categories, $include_categories);
            // Check if the course should be excluded
            $exclude = !empty($exclude_categories) && array_intersect($course_categories, $exclude_categories);

            // Add course if it matches include and does not match exclude
            if ($include && !$exclude) {
                $filtered_courses[] = $course;
            }
        }

        $user_courses = $filtered_courses; // Replace with filtered courses
    }

    $quiz_attempts = learndash_get_user_profile_quiz_attempts( $current_user->ID );

    $profile_pager = array();
    if ( isset( $atts['per_page'] ) && intval( $atts['per_page'] ) > 0 ) {
        $profile_pager['paged'] = isset( $_GET['ld-profile-page'] ) ? intval( $_GET['ld-profile-page'] ) : 1;

        // Pagination for quizzes
        $quiz_attempts['quizzes-paged'] = isset( $_GET['profile-quizzes'] ) ? intval( $_GET['profile-quizzes'] ) : 1;

        $profile_pager['total_items'] = count( $user_courses );
        $profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );

        // Slice user courses for pagination
        $user_courses = array_slice( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
    }

    $learndash_shortcode_used = true;

    return SFWD_LMS::get_template(
        'profile',
        array(
            'user_id'        => $atts['user_id'],
            'quiz_attempts'  => $quiz_attempts,
            'current_user'   => $current_user,
            'user_courses'   => $user_courses,
            'shortcode_atts' => $atts,
            'profile_pager'  => $profile_pager,
        )
    );
}
add_shortcode( 'ld_profile_with_categories', 'learndash_profile_with_categories', 10, 3 );

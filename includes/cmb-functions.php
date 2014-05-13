<?php
/**
 * Include and setup custom metaboxes and fields.
 *
 * @category YourThemeOrPlugin
 * @package  Metaboxes
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/webdevstudios/Custom-Metaboxes-and-Fields-for-WordPress
 */


function be_sample_metaboxes( $meta_boxes ) {
    $prefix = '_sd_'; // Prefix for all fields
    $meta_boxes['sd_metafields'] = array(
        'id' => 'sd_metafields',
        'title' => 'Broadcast Info',
        'pages' => array('sdprogram'), // post type
        'context' => 'normal',
        'priority' => 'high',
        'show_names' => true, // Show field names on the left
        'fields' => array(
            array(
                    'name' => __( 'Series Name'),
                    'desc' => __( ''),
                    'id'   => $prefix . 'seriesname_text',
                    'type' => 'text',
            ),
            array(
                    'name' => __( 'Episode Name'),
                    'desc' => __( ''),
                    'id'   => $prefix . 'episodename_text',
                    'type' => 'text',
            ),
            array(
                    'name' => __( 'Running Time'),
                    'desc' => __( ''),
                    'id'   => $prefix . 'runningtime_textsmall',
                    'type' => 'text_small',
            ),  
            array(
                    'name' => __( 'Program Number '),
                    'desc' => __( ''),
                    'id'   => $prefix . 'programnumber_textsmall',
                    'type' => 'text_small',
            ), 
            array(
                    'name' => __( 'Airing Date'),
                    'desc' => __( 'Airing date of repeat'),
                    'id'   => $prefix . 'airdate_textdate_timestamp',
                    'type' => 'text_datetime_timestamp',
            ),
            array(
                    'name' => __( 'Original Broadcast Date'),
                    'desc' => __( 'First airing date'),
                    'id'   => $prefix . 'orgdate_textsmall',
                    'type' => 'text_small',
                    // 'timezone_meta_key' => $prefix . 'timezone', // Optionally make this field honor the timezone selected in the select_timezone specified above
            ),
            array(
                    'name' => __( 'Weekday'),
                    'desc' => __( ''),
                    'id'   => $prefix . 'weekday_textsmall',
                    'type' => 'text_small',
            ),  
        ),
    );

    return $meta_boxes;
}
add_filter( 'cmb_meta_boxes', 'be_sample_metaboxes' );

// Initialize the metabox class
add_action( 'init', 'be_initialize_cmb_meta_boxes', 9999 );
function be_initialize_cmb_meta_boxes() {
    if ( !class_exists( 'cmb_Meta_Box' ) ) {
        require_once( 'Custom-Metaboxes-and-Fields-for-WordPress-master/init.php' );
    }
}
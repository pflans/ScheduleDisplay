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
    $prefix = '_cmb_'; // Prefix for all fields
    $meta_boxes['sd_metafields'] = array(
        'id' => '',
        'title' => 'Broadcast Info',
        'pages' => array('sdprogram'), // post type
        'context' => 'normal',
        'priority' => 'high',
        'show_names' => true, // Show field names on the left
        'fields' => array(
            array(
                    'name' => __( 'Series Name', 'cmb' ),
                    'desc' => __( '', 'cmb' ),
                    'id'   => $prefix . 'seriesname_text',
                    'type' => 'text',
            ),
            array(
                    'name' => __( 'Episode Name', 'cmb' ),
                    'desc' => __( '', 'cmb' ),
                    'id'   => $prefix . 'episodename_text',
                    'type' => 'text',
            ),
            array(
                    'name' => __( 'Running Time', 'cmb' ),
                    'desc' => __( '', 'cmb' ),
                    'id'   => $prefix . 'runningtime_textsmall',
                    'type' => 'text_small',
            ),  
            array(
                    'name' => __( 'Program Number ', 'cmb' ),
                    'desc' => __( '', 'cmb' ),
                    'id'   => $prefix . 'programnumber_textsmall',
                    'type' => 'text_small',
            ), 
            array(
                    'name' => __( 'Airing Date', 'cmb' ),
                    'desc' => __( 'Airing date of repeat', 'cmb' ),
                    'id'   => $prefix . 'airdate_datetime_timestamp',
                    'type' => 'text_datetime_timestamp',
            ),
            array(
                    'name' => __( 'Original Broadcast Date', 'cmb' ),
                    'desc' => __( 'First airing date', 'cmb' ),
                    'id'   => $prefix . 'orgdate_textdate_timestamp',
                    'type' => 'text_date_timestamp',
                    // 'timezone_meta_key' => $prefix . 'timezone', // Optionally make this field honor the timezone selected in the select_timezone specified above
            ),
            array(
                    'name' => __( 'Weekday', 'cmb' ),
                    'desc' => __( '', 'cmb' ),
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
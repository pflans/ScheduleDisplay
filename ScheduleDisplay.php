<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   ScheduleDisplay
 * @author    Patrick Murray <patfmurray@gmail.com>
 * @license   GPL-2.0+
 * @link      http://github.com/comrh/
 * @copyright 2014 PFM
 *
 * @wordpress-plugin
 * Plugin Name:       ScheduleDisplay
 * Plugin URI:        http://github.com/comrh/ScheduleDisplay
 * Description:       Used to upload and display CSV to the frontend as a schedule
 * Version:           1.0.0
 * Author:            Patrick Murray
 * Author URI:        http://github.com/comrh/
 * Text Domain:       ScheduleDisplay-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/comrh/ScheduleDisplay
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-ScheduleDisplay.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/cmb-functions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/index.php' );

// Update from Github
include_once( plugin_dir_path( __FILE__ ) . 'admin/includes/WordPress-GitHub-Plugin-Updater/updater.php' );
        
register_activation_hook( __FILE__, array( 'ScheduleDisplay', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ScheduleDisplay', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'ScheduleDisplay', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-ScheduleDisplay-admin.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/csv_importer.php' );

	add_action( 'plugins_loaded', array( 'ScheduleDisplay', 'get_instance' ) );
        

}


if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
    $config = array(
        'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
        'proper_folder_name' => 'ScheduleDisplay', // this is the name of the folder your plugin lives in
        'api_url' => 'https://api.github.com/repos/comrh/ScheduleDisplay', // the github API url of your github repo
        'raw_url' => 'https://raw.github.com/comrh/ScheduleDisplay/master', // the github raw url of your github repo
        'github_url' => 'https://github.com/comrh/ScheduleDisplay', // the github url of your github repo
        'zip_url' => 'https://github.com/comrh/ScheduleDisplay/zipball/master', // the zip url of the github repo
        'sslverify' => false, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
        'requires' => '3.0', // which version of WordPress does your plugin require?
        'tested' => '3.3', // which version of WordPress is your plugin tested up to?
        'readme' => 'README.txt', // which file to use as the readme for the version number
        'access_token' => '', // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
    );
    new WP_GitHub_Updater($config);
}

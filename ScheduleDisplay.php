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
 * Description:       @TODO
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
	add_action( 'plugins_loaded', array( 'ScheduleDisplay', 'get_instance' ) );

}

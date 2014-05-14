<?php
/**
 * ScheduleDisplay
 *
 * @package   ScheduleDisplay
 * @author    Patrick Murray <patfmurray@gmail.com>
 * @license   GPL-2.0+
 * @link      http://github.com/comrh
 * @copyright 2014 PFM
 */


class ScheduleDisplay {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * @TODO - Rename "plugin-name" to the name of your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'ScheduleDisplay';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'init', array( $this, 'register_cpt_sdprogram' ) );
                add_filter( 'manage_edit-sdprogram_columns', array( $this, 'set_custom_edit_sdprogram_columns' ));
                add_action( 'manage_sdprogram_posts_custom_column' , array( $this, 'custom_sdprogram_column'), 10, 2 );
 
        
        }

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
                wp_enqueue_style( 'themename-style', get_stylesheet_uri(), array( 'dashicons' ), '1.0' );
        }

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
         public function register_cpt_sdprogram() {

                $labels = array( 
                    'name' => _x( 'Broadcasts', 'sdprogram' ),
                    'singular_name' => _x( 'Broadcasts', 'sdprogram' ),
                    'add_new' => _x( 'Add New', 'sdprogram' ),
                    'add_new_item' => _x( 'Add New Broadcasts', 'sdprogram' ),
                    'edit_item' => _x( 'Edit Broadcasts', 'sdprogram' ),
                    'new_item' => _x( 'New Broadcasts', 'sdprogram' ),
                    'view_item' => _x( 'View Broadcasts', 'sdprogram' ),
                    'search_items' => _x( 'Search Broadcasts', 'sdprogram' ),
                    'not_found' => _x( 'No programs found', 'sdprogram' ),
                    'not_found_in_trash' => _x( 'No programs found in Trash', 'sdprogram' ),
                    'parent_item_colon' => _x( 'Parent Broadcasts:', 'sdprogram' ),
                    'menu_name' => _x( 'Broadcasts', 'sdprogram' ),
                );

                $args = array( 
                    'labels' => $labels,
                    'hierarchical' => false,

                    'supports' => array( 'title' ),
                    'taxonomies' => array( 'category', 'post_tag' ),
                    'public' => true,
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'menu_icon' => 'dashicons-format-audio',
                    'show_in_nav_menus' => true,
                    'publicly_queryable' => true,
                    'exclude_from_search' => false,
                    'has_archive' => true,
                    'query_var' => true,
                    'can_export' => true,
                    'rewrite' => true,
                    'capability_type' => 'post'
                );

                register_post_type( 'sdprogram', $args );

         }

         
          

        function set_custom_edit_sdprogram_columns($columns) {
            unset( $columns['categories'] );
            unset( $columns['tags'] );
            unset( $columns['date'] );
            $columns['_sd_weekday_textsmall'] = __( 'Weekday', '' );;
            $columns['_sd_airdate_textdate_timestamp'] = __( 'Date', '' );;
            $columns['_sd_seriesname_text'] = __( 'Series Name', '' );;
            $columns['_sd_episodename_text'] = __( 'Episode Name', '' );;
            $columns['_sd_runningtime_textsmall'] = __( 'Running Time', '' );;
            $columns['_sd_programnumber_textsmall'] = __( 'Broadcasts', '' );;
            $columns['_sd_orgdate_textsmall'] = __( 'Original Broadcast Date', '' );;  
            return $columns;
        }

        function custom_sdprogram_column( $column, $post_id ) {
            switch ( $column ) {
                case '_sd_airdate_textdate_timestamp':
                    $value = get_post_meta( $post_id , '_sd_airdate_textdate_timestamp' , true );
                    if( ! empty( $value ) ) {
                        echo gmdate("F j, Y, H:i:s", $value);
                    } 
                    break;
                case '_sd_seriesname_text':
                    $value = get_post_meta( $post_id , '_sd_seriesname_text' , true );
                    if( ! empty( $value ) ) {
                        echo $value;
                    } 
                    break;
                case '_sd_episodename_text':
                    $value = get_post_meta( $post_id , '_sd_episodename_text' , true );
                    if( ! empty( $value ) ) {
                        echo $value;
                    }                     
                    break;
                case '_sd_runningtime_textsmall':
                    $value = get_post_meta( $post_id , '_sd_runningtime_textsmall' , true );
                    if( ! empty( $value ) ) {
                        echo $value;
                    }                     
                    break;
                case '_sd_programnumber_textsmall':
                    $value = get_post_meta( $post_id , '_sd_programnumber_textsmall' , true );
                    if( ! empty( $value ) ) {
                        echo $value;
                    }                     
                    break;
                case '_sd_orgdate_textsmall':
                    $value = get_post_meta( $post_id , '_sd_orgdate_textsmall' , true );  
                    if( ! empty( $value ) ) {
                        echo $value;
                    }                     
                    break;
                case '_sd_weekday_textsmall':
                    $value = get_post_meta( $post_id , '_sd_weekday_textsmall' , true );  
                    if( ! empty( $value ) ) {
                        echo $value;
                    }                     
                    break;
            }
        }
         
        
}

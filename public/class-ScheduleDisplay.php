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
	protected $plugin_slug = 'plugin-name';

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
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

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
		
            /* Get the administrator role. */
		$role =& get_role( 'administrator' );

		/* If the administrator role exists, add required capabilities for the plugin. */
		if ( !empty( $role ) ) {

			$role->add_cap( 'manageSD_programs' );
			$role->add_cap( 'createSD_programs' );
			$role->add_cap( 'editSD_programs' );
		}
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
         function register_post_types() {

                /* Set up the arguments for the post type. */
                $args = array(

                        /**
                         * A short description of what your post type is. As far as I know, this isn't used anywhere 
                         * in core WordPress.  However, themes may choose to display this on post type archives. 
                         */
                        'description'         => __( 'ScheduleDisplay', 'example-textdomain' ), // string

                        /** 
                         * Whether the post type should be used publicly via the admin or by front-end users.  This 
                         * argument is sort of a catchall for many of the following arguments.  I would focus more 
                         * on adjusting them to your liking than this argument.
                         */
                        'public'              => true, // bool (default is FALSE)

                        /**
                         * Whether queries can be performed on the front end as part of parse_request(). 
                         */
                        'publicly_queryable'  => true, // bool (defaults to 'public').

                        /**
                         * Whether to exclude posts with this post type from front end search results.
                         */
                        'exclude_from_search' => false, // bool (defaults to 'public')

                        /**
                         * Whether individual post type items are available for selection in navigation menus. 
                         */
                        'show_in_nav_menus'   => false, // bool (defaults to 'public')

                        /**
                         * Whether to generate a default UI for managing this post type in the admin. You'll have 
                         * more control over what's shown in the admin with the other arguments.  To build your 
                         * own UI, set this to FALSE.
                         */
                        'show_ui'             => true, // bool (defaults to 'public')

                        /**
                         * Whether to show post type in the admin menu. 'show_ui' must be true for this to work. 
                         */
                        'show_in_menu'        => true, // bool (defaults to 'show_ui')

                        /**
                         * Whether to make this post type available in the WordPress admin bar. The admin bar adds 
                         * a link to add a new post type item.
                         */
                        'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')

                        /**
                         * The position in the menu order the post type should appear. 'show_in_menu' must be true 
                         * for this to work.
                         */
                        'menu_position'       => 25, // int (defaults to 25 - below comments)

                        /**
                         * The URI to the icon to use for the admin menu item. There is no header icon argument, so 
                         * you'll need to use CSS to add one.
                         */
                        'menu_icon'           => null, // string (defaults to use the post icon)

                        /**
                         * Whether the posts of this post type can be exported via the WordPress import/export plugin 
                         * or a similar plugin. 
                         */
                        'can_export'          => true, // bool (defaults to TRUE)

                        /**
                         * Whether to delete posts of this type when deleting a user who has written posts. 
                         */
                        'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')

                        /**
                         * Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts. 
                         */
                        'hierarchical'        => false, // bool (defaults to FALSE)

                        /** 
                         * Whether the post type has an index/archive/root page like the "page for posts" for regular 
                         * posts. If set to TRUE, the post type name will be used for the archive slug.  You can also 
                         * set this to a string to control the exact name of the archive slug.
                         */
                        'has_archive'         => false, // bool|string (defaults to FALSE)

                        /**
                         * Sets the query_var key for this post type. If set to TRUE, the post type name will be used. 
                         * You can also set this to a custom string to control the exact key.
                         */
                        'query_var'           => 'example', // bool|string (defaults to TRUE - post type name)

                        /**
                         * A string used to build the edit, delete, and read capabilities for posts of this type. You 
                         * can use a string or an array (for singular and plural forms).  The array is useful if the 
                         * plural form can't be made by simply adding an 's' to the end of the word.  For example, 
                         * array( 'box', 'boxes' ).
                         */
                        'capability_type'     => 'post', // string|array (defaults to 'post')

                        /**
                         * Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for 
                         * you.  If set to FALSE, you'll need to roll your own handling of this by filtering the 
                         * 'map_meta_cap' hook.
                         */
                        'map_meta_cap'        => true, // bool (defaults to FALSE)

                        /**
                         * Provides more precise control over the capabilities than the defaults.  By default, WordPress 
                         * will use the 'capability_type' argument to build these capabilities.  More often than not, 
                         * this results in many extra capabilities that you probably don't need.  The following is how 
                         * I set up capabilities for many post types, which only uses three basic capabilities you need 
                         * to assign to roles: 'manageSD_programs', 'editSD_programs', 'createSD_programs'.  Each post type 
                         * is unique though, so you'll want to adjust it to fit your needs.
                         */
                        'capabilities' => array(

                                // meta caps (don't assign these to roles)
                                'edit_post'              => 'editSD_program',
                                'read_post'              => 'readSD_program',
                                'delete_post'            => 'deleteSD_program',

                                // primitive/meta caps
                                'create_posts'           => 'create_SD_programs',

                                // primitive caps used outside of map_meta_cap()
                                'edit_posts'             => 'editSD_programs',
                                'edit_others_posts'      => 'manageSD_programs',
                                'publish_posts'          => 'manageSD_programs',
                                'read_private_posts'     => 'read',

                                // primitive caps used inside of map_meta_cap()
                                'read'                   => 'read',
                                'delete_posts'           => 'manageSD_programs',
                                'delete_private_posts'   => 'manageSD_programs',
                                'delete_published_posts' => 'manageSD_programs',
                                'delete_others_posts'    => 'manageSD_programs',
                                'edit_private_posts'     => 'editSD_programs',
                                'edit_published_posts'   => 'editSD_programs'
                        ),

                        /** 
                         * How the URL structure should be handled with this post type.  You can set this to an 
                         * array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite 
                         * rules from being created.
                         */
                        'rewrite' => array(

                                /* The slug to use for individual posts of this type. */
                                'slug'       => 'SD_program', // string (defaults to the post type name)

                                /* Whether to show the $wp_rewrite->front slug in the permalink. */
                                'with_front' => false, // bool (defaults to TRUE)

                                /* Whether to allow single post pagination via the <!--nextpage--> quicktag. */
                                'pages'      => true, // bool (defaults to TRUE)

                                /* Whether to create feeds for this post type. */
                                'feeds'      => true, // bool (defaults to the 'has_archive' argument)

                                /* Assign an endpoint mask to this permalink. */
                                'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)
                        ),

                        /**
                         * What WordPress features the post type supports.  Many arguments are strictly useful on 
                         * the edit post screen in the admin.  However, this will help other themes and plugins 
                         * decide what to do in certain situations.  You can pass an array of specific features or 
                         * set it to FALSE to prevent any features from being added.  You can use 
                         * add_post_type_support() to add features or remove_post_type_support() to remove features 
                         * later.  The default features are 'title' and 'editor'.
                         */
                        'supports' => array(

                                /* Post titles ($post->post_title). */
                                'title',

                                /* Post content ($post->post_content). */
                                'editor',

                                /* Post author ($post->post_author). */
                                'author',

                                /* Displays the Custom Fields meta box. Post meta is supported regardless. */
                                'custom-fields',

                                /* Displays the Revisions meta box. If set, stores post revisions in the database. */
                                'revisions',

                                /* Displays the Attributes meta box with a parent selector and menu_order input box. */
                                'page-attributes',

                        ),

                        /**
                         * Labels used when displaying the posts in the admin and sometimes on the front end.  These 
                         * labels do not cover post updated, error, and related messages.  You'll need to filter the 
                         * 'post_updated_messages' hook to customize those.
                         */
                        'labels' => array(
                                'name'               => __( 'Programs',                   'example-textdomain' ),
                                'singular_name'      => __( 'Program',                    'example-textdomain' ),
                                'menu_name'          => __( 'Programs',                   'example-textdomain' ),
                                'name_admin_bar'     => __( 'Programs',                   'example-textdomain' ),
                                'add_new'            => __( 'Add New',                    'example-textdomain' ),
                                'add_new_item'       => __( 'Add New Program',            'example-textdomain' ),
                                'edit_item'          => __( 'Edit Program',               'example-textdomain' ),
                                'new_item'           => __( 'New Program',                'example-textdomain' ),
                                'view_item'          => __( 'View Program',               'example-textdomain' ),
                                'search_items'       => __( 'Search Programs',            'example-textdomain' ),
                                'not_found'          => __( 'No examples found',          'example-textdomain' ),
                                'not_found_in_trash' => __( 'No examples found in trash', 'example-textdomain' ),
                                'all_items'          => __( 'All Programs',               'example-textdomain' ),

                                /* Labels for hierarchical post types only. */
                                'parent_item'        => __( 'Parent Program',             'example-textdomain' ),
                                'parent_item_colon'  => __( 'Parent Program:',            'example-textdomain' ),

                                /* Custom archive label.  Must filter 'post_type_archive_title' to use. */
                                'archive_title'      => __( 'Programs',                   'example-textdomain' ),
                        )
                );

                /* Register the post type. */
                register_post_type(
                        'SD_program', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
                        $args      // Arguments for post type.
                );
        }

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

}

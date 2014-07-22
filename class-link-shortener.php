<?php
/**
 * Link Shortener
 *
 * @package   Link_Shortener
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

/**
 * Plugin class
 *
 * @package Link_Shortener
 * @author  Steve Taylor
 */
class Link_Shortener {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1
	 *
	 * @var     string
	 */
	protected $version = '0.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'link-shortener';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * The plugin's settings.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $settings = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	private function __construct() {

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the settings page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'process_plugin_admin_settings' ) );

		// Load admin style sheet and JavaScript.
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Other hooks
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1
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
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		// Flush rewrite rules for endpoint
		flush_rewrite_rules();

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		// Flush rewrite rules for endpoint
		flush_rewrite_rules();

	}

	/**
	 * Initialize
	 *
	 * @since    0.1
	 */
	public function init() {

		// Set the settings
		//$this->settings = $this->get_settings();

		// Load plugin text domain
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Add the endpoint to the site root
		add_rewrite_endpoint( LS_ENDPOINT_NAME, EP_ROOT );

	}

	/**
	 * Initialize admin
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function admin_init() {

		// Catch call to refresh link statuses
		if ( isset( $_GET['ls_nonce'] ) ) {

			if ( wp_verify_nonce( $_GET['ls_nonce'], 'refresh_link_statuses' ) ) {

				// Do the refresh
				$this->refresh_link_statuses();

				// Redirect back to page for success message
				wp_redirect( admin_url( 'edit.php?post_type=ls_link&page=ls-refresh&done=1' ) );
				exit();

			} else if ( wp_verify_nonce( $_GET['ls_nonce'], 'refresh_link_status' ) && isset( $_GET['link_id'] ) && ctype_digit( $_GET['link_id'] ) ) {

				// Do the refresh
				$this->refresh_link_status( $_GET['link_id'] );

				// Redirect back to admin list for success message
				wp_redirect( admin_url( 'edit.php?post_type=ls_link&refreshed=1' ) );
				exit();

			}

		}

		// Admin-only hooks
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'manage_posts_custom_column' , array( $this, 'manage_posts_custom_column' ), 10, 2 );
		add_action( 'manage_ls_link_posts_columns' , array( $this, 'manage_ls_link_posts_columns' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );

		// Extra views links for shortened links
		add_filter( 'views_edit-ls_link', array( $this, 'views_ls_link_edit' ) );
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts_ls_link_edit' ) );


	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		$screen = get_current_screen();

		//if ( in_array( $screen->id, $this->settings['footnotes_post_types'] ) ) {
		//	wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array( 'dashicons' ), $this->version );
		//}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if ( $screen->id == 'edit-ls_link' ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1
	 */
	public function enqueue_styles() {
		//wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1
	 */
	public function enqueue_scripts() {
		//wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1
	 */
	public function add_plugin_admin_menu() {

		// Settings menu
		/*
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Link shortener', $this->plugin_slug ),
			__( 'Link shortener', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
		*/

		// Extra items for the main post type menu
		add_submenu_page(
			'edit.php?post_type=ls_link',
			__( 'Refresh link statuses', $this->plugin_slug ),
			__( 'Refresh link statuses', $this->plugin_slug ),
			'manage_options',
			'ls-refresh',
			array( $this, 'display_refresh_link_statuses_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Render the refresh link statuses page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_refresh_link_statuses_page() {
		include_once( 'views/refresh_link_statuses.php' );
	}

	/**
	 * Add custom columns to link admin list
	 *
	 * @since    0.1
	 */
	public function manage_ls_link_posts_columns( $columns ) {

		unset( $columns['date'] );
		$columns['short_link'] = __( 'Short link', $this->plugin_slug );
		$columns['link_status'] = __( 'Status', $this->plugin_slug );

		return $columns;
	}

	/**
	 * Output custom column values admin lists
	 *
	 * @since    0.1
	 */
	public function manage_posts_custom_column( $column, $post_id ) {

		switch ( $column ) {
			case 'short_link' :
				$short_link = $this->short_link( $post_id );
				echo '<input type="text" style="width:80%" class="ls-short-link" readonly="readonly" value="' . $short_link . '"> &nbsp;';
				echo '<a class="button" href="' . $short_link . '" target="_blank">' . __( 'Test', $this->plugin_slug ) . '</a>';
				break;
			case 'link_status' :
				if ( $status = get_post_meta( $post_id, '_ls_link_status', true ) ) {
					echo $status;
				} else {
					echo __( 'Not checked yet', $this->plugin_slug );
				}
				break;
		}

	}

	/**
	 * Filters for links listing
	 *
	 * @since	0.1
	 */
	public function views_ls_link_edit( $views ) {
		global $wpdb;

		// Get count for 4xx status codes
		$count = $wpdb->get_var("
			SELECT	COUNT(*)
			FROM	$wpdb->postmeta
			WHERE	meta_key 	= '_ls_link_status'
			AND		meta_value	LIKE '4__'
		");

		// Add view
		$view = '<a href="' . esc_url( add_query_arg( array( 'status_code' => '4xx' ) ) ) . '"';
		if ( isset( $_GET['status_code'] ) && $_GET['status_code'] == '4xx' ) {
			$view .= ' class="current"';
		}
		$view .= '>' . __( '4xx statuses', $this->plugin_slug ) . '</a> (' . $count . ')';
		$views['4xx'] = $view;

		return $views;
	}

	/**
	 * Filter links admin list
	 *
	 * @since    0.1
	 */
	public function pre_get_posts_ls_link_edit( $query ) {
		global $pagenow;

		if ( $pagenow == 'edit.php' && $query->is_admin && isset( $_REQUEST['status_code'] ) && $_REQUEST['status_code'] ) {

			// Replace any 'x' placeholders
			$status_code = str_replace( 'x', '_', $_REQUEST['status_code'] );

			// Set status code filter
			$query->meta_query = new WP_Meta_Query( array(
				array(
					'key'		=> '_ls_link_status',
					'value'		=> $status_code,
					'compare'	=> 'LIKE'
				)
			));
			echo '<pre>'; print_r( $query ); echo '</pre>'; exit;

		}

		return $query;
	}

	/**
	 * Post actions for links
	 *
	 * @since    0.1
	 */
	public function post_row_actions( $actions, $post ) {

		if ( get_post_type( $post ) == 'ls_link' ) {
			$actions['refresh_status'] = '<a href="' . add_query_arg( 'link_id', $post->ID, wp_nonce_url( admin_url(), 'refresh_link_status', 'ls_nonce' ) ) . '">' . __( 'Refresh status', $this->plugin_slug ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Get the plugin's settings
	 *
	 * @since    0.1
	 */
	public function get_settings() {

		$settings = get_option( $this->plugin_slug . '_settings' );

		if ( ! $settings ) {

			// Defaults
			$settings = array(
			);

		}

		return $settings;
	}

	/**
	 * Set the plugin's settings
	 *
	 * @since    0.1
	 */
	public function set_settings( $settings ) {
		return update_option( $this->plugin_slug . '_settings', $settings );
	}

	/**
	 * Process the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function process_plugin_admin_settings() {

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_settings_admin_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_settings', $this->plugin_slug . '_settings_admin_nonce' ) ) {

			// Gather into array
			$settings = array(
			);

			// Save as option
			$this->set_settings( $settings );

			// Redirect
			wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin_slug . '&done=1' ) );

		}

	}

	/**
	 * Register custom post types
	 *
	 * @since	0.1
	 */
	public function register_custom_post_types() {

		// Sources
		register_post_type(
			'ls_link', apply_filters( 'ls_link_post_type_args', array(
				'labels'				=> array(
					'name'					=> __( 'Shortened links', $this->plugin_slug ),
					'singular_name'			=> __( 'Shortened link', $this->plugin_slug ),
					'add_new'				=> __( 'Add New', $this->plugin_slug ),
					'add_new_item'			=> __( 'Add New Shortened link', $this->plugin_slug ),
					'edit'					=> __( 'Edit', $this->plugin_slug ),
					'edit_item'				=> __( 'Edit Shortened link', $this->plugin_slug ),
					'new_item'				=> __( 'New Shortened link', $this->plugin_slug ),
					'view'					=> __( 'View Shortened link', $this->plugin_slug ),
					'view_item'				=> __( 'View Shortened link', $this->plugin_slug ),
					'search_items'			=> __( 'Search Shortened links', $this->plugin_slug ),
					'not_found'				=> __( 'No Shortened links found', $this->plugin_slug ),
					'not_found_in_trash'	=> __( 'No Shortened links found in Trash', $this->plugin_slug )
				),
				'public'			=> false,
				'show_ui'			=> true,
				'menu_position'		=> 15,
				'menu_icon'			=> 'dashicons-admin-links',
				'supports'			=> array( 'title' ),
				'rewrite'			=> false
			) )
		);

	}

	/**
	 * Add meta boxes
	 *
	 * @since	0.1
	 * @param	string	$post_type
	 * @param	object	$post
	 * @return	void
	 */
	public function add_meta_boxes( $post_type, $post ) {

		// The shortened link meta box
		add_meta_box(
			'ls-shortened-link',
			__( 'The shortened link', $this->plugin_slug ),
			array( $this, 'shortened_link_meta_box' )
		);

	}

	/**
	 * The shortened link meta box
	 *
	 * @since	0.1
	 * @param	object	$post
	 * @return	void
	 */
	public function shortened_link_meta_box( $post ) {

		$shortened_link = $this->short_link( $post->ID );
		echo '<a href="' . $shortened_link . '" target="_blank">' . $shortened_link . '</a>';

	}

	/**
	 * The shortened link
	 *
	 * @since	0.1
	 * @param	int		$post_id
	 * @return	string
	 */
	public function short_link( $post_id ) {

		return esc_url( trailingslashit( site_url() ) . LS_ENDPOINT_NAME . '/' . $post_id );

	}


	/**
	 * "Enter title here" text
	 *
	 * @since	0.1
	 * @param	int		$post_id
	 * @param	int		$post_id
	 * @return	string
	 */
	public function enter_title_here( $placeholder, $post ) {

		if ( get_post_type( $post ) == 'ls_link' ) {
			$placeholder = __( 'Enter URL here', $this->plugin_slug );
		}

		return $placeholder;
	}

	/**
	 * Template redirect to catch endpoint
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function template_redirect() {
		global $wp_query;

		// Is a valid endpoint set?
		if ( isset( $wp_query->query_vars[ LS_ENDPOINT_NAME ] ) && ctype_digit( $wp_query->query_vars[ LS_ENDPOINT_NAME ] ) ) {

			// Try to get the link
			if ( $link = get_the_title( $wp_query->query_vars[ LS_ENDPOINT_NAME ] ) ) {

				// Redirect
				wp_redirect( $link, 301 );
				exit;

			}

		}

		return;
	}

	/**
	 * Refresh a link's cached status code when it's saved
	 *
	 * @since	0.1
	 */
	public function save_post( $post_id ) {
		global $ls_link_status_deleted;

		if ( get_post_type( $post_id ) == 'ls_link' && ( ! isset( $ls_link_status_deleted ) || ! $ls_link_status_deleted ) ) {
			$this->refresh_link_status( $post_id );
		}

	}

	/**
	 * When a link is trashed, ditch its status code metadata
	 *
	 * @since	0.1
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		global $ls_link_status_deleted;

		if ( get_post_type( $post ) == 'ls_link' && $new_status == 'trash' ) {
			delete_post_meta( $post->ID, '_ls_link_status' );
			$ls_link_status_deleted = true;
		}

	}

	/**
	 * Refresh all link statuses
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function refresh_link_statuses() {

		// Get all links
		$links = get_posts( array(
			'posts_per_page'	=> -1,
			'post_type'			=> 'ls_link',
		));

		// Go through them all
		foreach ( $links as $link ) {

			// Do the refresh
			$this->refresh_link_status( $link->ID, $link->post_title );

		}

	}

	/**
	 * Refresh a particular link status
	 *
	 * @since	0.1
	 * @param	int			$link_id
	 * @param	string		$link_url	Optional
	 * @return	void
	 */
	public function refresh_link_status( $link_id, $link_url = null ) {

		// Get the URL?
		if ( ! $link_url ) {
			$link_url = get_the_title( $link_id );
		}

		// Get just the headers
		$headers = wp_remote_head( $link_url );

		// Error?
		if ( is_wp_error( $headers ) ) {
			$headers = array( 'response' => array( 'code' => '4xx' ) );
		}

		// Store the status
		update_post_meta( $link_id, '_ls_link_status', $headers['response']['code'] );

	}

}
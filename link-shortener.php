<?php

/**
 * Link Shortener
 *
 * @package   Link_Shortener
 * @author    Steve Taylor
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:			Link Shortener
 * Description:			A WordPress plugin for managing short redirect links.
 * Version:				0.1
 * Author:				Steve Taylor
 * Text Domain:			link-shortener-locale
 * License:				GPL-2.0+
 * License URI:			http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:			/lang
 * GitHub Plugin URI:	https://github.com/gyrus/link-shortener
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-link-shortener.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Link_Shortener', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Link_Shortener', 'deactivate' ) );

Link_Shortener::get_instance();

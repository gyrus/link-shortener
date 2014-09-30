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

// Initialize constants
if ( ! defined( 'LS_ENDPOINT_NAME' ) ) {
	define( 'LS_ENDPOINT_NAME', 'link' );
}

require_once( plugin_dir_path( __FILE__ ) . 'class-link-shortener.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Link_Shortener', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Link_Shortener', 'deactivate' ) );

Link_Shortener::get_instance();


/*
 * Any functions to be easily accessed by themes
 */

/**
 * Output a form for easily visiting a shortlink
 *
 * @since	0.1
 * @param	string	$label			Default: 'Visit a shortlink'
 * @param	mixed	$input_class	Default: ''; can be string or array of strings
 * @param	mixed	$button_class	Default: ''; can be string or array of strings
 * @param	string	$button_text	Default: 'Go »'
 * @return	void
 */
function ls_visit_shortlink_form( $label = null, $input_class = '', $button_class = '', $button_text = null ) {
	$LS = Link_Shortener::get_instance();
	$LS->visit_shortlink_form( $label, $input_class, $button_class, $button_text );
}
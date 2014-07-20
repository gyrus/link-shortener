/**
 * Admin JavaScript
 */

/* Trigger when DOM has loaded */
jQuery( document ).ready( function( $ ) {

	// Select short links easily
	$( '.wp-list-table' ).on( 'focus', 'input.ls-short-link', function( e ) {
		e.preventDefault();
		this.select();
	});

});


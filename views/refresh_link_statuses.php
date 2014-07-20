<?php

/**
 * Refresh statuses page
 *
 * @package   Link_Shortener
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php if ( isset( $_GET['done'] ) ) { ?>

		<div class="updated"><p><strong><?php _e( 'All link statuses refreshed successfully.' ); ?></strong></p></div>

	<?php } ?>

	<p><a href="<?php echo wp_nonce_url( admin_url(), 'refresh_link_statuses', 'ls_nonce' ); ?>" class="button button-primary"><?php _e( 'Refresh' ); ?></a></p>

</div>

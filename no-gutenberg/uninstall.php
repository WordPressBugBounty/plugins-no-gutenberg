<?php
/**
 * Uninstall No Gutenberg.
 *
 * Removes the plugin settings, the dismissed state of its notices, and the
 * transients used for the activation and theme switch warnings.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

// Exit if not called by WordPress during uninstall.
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'No script kiddies please!' );

// Plugin settings.
delete_option( 'ayudawp_no_gutenberg_options' );
delete_option( 'ayudawp_no_gutenberg_version' );
delete_option( 'ayudawp_no_gutenberg_classic_editor' );

// Mark left on the network by a network wide activation. WordPress runs this
// file once, so the options above are those of the site it runs on, but this
// one belongs to the whole network and would survive the uninstall.
if ( is_multisite() ) {
	delete_site_option( 'ayudawp_no_gutenberg_network_install' );
}

// Dismissed FSE warning, for every user.
delete_metadata( 'user', 0, 'ayudawp_no_gutenberg_fse_warning_dismissed', '', true );

// Editor chosen entry by entry, for every entry. The Classic Editor meta this
// plugin can read is not touched: it belongs to that plugin.
delete_metadata( 'post', 0, '_ayudawp_no_gutenberg_editor', '', true );

// Notice and count transients.
delete_transient( 'ayudawp_no_gutenberg_activated' );
delete_transient( 'ayudawp_fse_theme_activated_warning' );
delete_transient( 'ayudawp_no_gutenberg_block_content' );
delete_transient( 'ayudawp_no_gutenberg_block_counts' );

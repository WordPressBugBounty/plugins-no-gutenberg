<?php
/**
 * Plugin Name: No Gutenberg - Choose Where to Use the Block Editor or the Classic Editor
 * Plugin URI: https://servicios.ayudawp.com/
 * Description: Complete elimination of Gutenberg Block Editor, FSE Global Styles, Block Widgets, Patterns, and WooCommerce blocks. Get back to the reliable Classic Editor with zero block-related overhead.
 * Version: 2.3.0
 * Author: Fernando Tellado
 * Author URI: https://ayudawp.com/
 *
 * @package No Gutenberg
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: no-gutenberg
 * Domain Path: /languages
 * Requires at least: 6.1
 * Requires PHP: 7.4
 *
 * No Gutenberg plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * No Gutenberg plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with No Gutenberg. If not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Plugin paths.
 */
define( 'AYUDAWP_NO_GUTENBERG_FILE', __FILE__ );
define( 'AYUDAWP_NO_GUTENBERG_DIR', plugin_dir_path( __FILE__ ) );
define( 'AYUDAWP_NO_GUTENBERG_URL', plugin_dir_url( __FILE__ ) );

require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-options.php';
require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-core.php';
require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-notices.php';
require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-editor-switch.php';

if ( is_admin() ) {
	require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-status.php';
	require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-promo-banner.php';
	require_once AYUDAWP_NO_GUTENBERG_DIR . 'includes/class-no-gutenberg-settings.php';
}

// Initialize the plugin.
AyudaWP_No_Gutenberg::init();
AyudaWP_No_Gutenberg_Notices::init();
AyudaWP_No_Gutenberg_Editor_Switch::init();

if ( is_admin() ) {
	AyudaWP_No_Gutenberg_Settings::init();

	// Carry an older configuration over after an automatic update, which never
	// fires the activation hook below.
	add_action( 'admin_init', array( 'AyudaWP_No_Gutenberg_Options', 'maybe_upgrade' ), 1 );
}

/**
 * Plugin activation hook - display notice about successful activation
 */
register_activation_hook( __FILE__, 'ayudawp_no_gutenberg_activation' );

/**
 * Set a transient to show the activation notice
 */
function ayudawp_no_gutenberg_activation() {
	set_transient( 'ayudawp_no_gutenberg_activated', true, 60 );

	AyudaWP_No_Gutenberg_Options::install();

	// WP-CLI activates with is_admin() false, so the status class may not be
	// loaded here.
	if ( class_exists( 'AyudaWP_No_Gutenberg_Status' ) ) {
		AyudaWP_No_Gutenberg_Status::flush_content_cache();
	}
}

/**
 * Add action links to plugin page
 *
 * @param array $links Plugin action links.
 * @return array
 */
function ayudawp_no_gutenberg_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . AyudaWP_No_Gutenberg_Options::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'no-gutenberg' ) . '</a>';
	$support_link  = '<a href="https://servicios.ayudawp.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'no-gutenberg' ) . '</a>';

	array_unshift( $links, $settings_link, $support_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ayudawp_no_gutenberg_action_links' );

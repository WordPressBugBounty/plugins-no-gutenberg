<?php
/**
 * Core class: removes the block editor and every block related feature.
 *
 * Each group of hooks is registered only when the settings ask for it. With the
 * default options Gutenberg is disabled everywhere, which reproduces the
 * behavior of the versions before 2.2.0.
 *
 * @package No Gutenberg
 * @since 2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Main plugin class
 */
class AyudaWP_No_Gutenberg {

	/**
	 * Plugin version
	 */
	const VERSION = '2.2.0';

	/**
	 * Initialize the plugin
	 */
	public static function init() {
		// Disable the block editor wherever the configured rules say so. The
		// filters are also needed with no rules at all when switching is
		// allowed, because a per entry choice has to be honored anyway.
		if ( AyudaWP_No_Gutenberg_Options::editor_has_rules() || AyudaWP_No_Gutenberg_Options::switching_allowed() ) {
			add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'ayudawp_filter_block_editor_for_post_type' ), 100, 2 );
			add_filter( 'use_block_editor_for_post', array( __CLASS__, 'ayudawp_filter_block_editor_for_post' ), 100, 2 );

			// Remove block editor assets from the admin area.
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_gutenberg_admin_assets' ) );
		}

		// Remove the welcome panel, which promotes the block editor. It belongs
		// to the whole Dashboard, so it only goes away on a complete disable.
		if ( AyudaWP_No_Gutenberg_Options::is_complete() ) {
			add_action( 'wp_dashboard_setup', array( __CLASS__, 'ayudawp_remove_dashboard_widgets' ) );
		}

		// Remove block-based widgets.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'widgets' ) ) {
			add_action( 'init', array( __CLASS__, 'ayudawp_disable_block_widgets' ) );
		}

		// Remove block patterns and block directory.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'patterns' ) ) {
			self::ayudawp_disable_block_patterns();
		}

		// Remove block assets from the frontend.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'frontend_css' ) ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_gutenberg_assets' ) );

			// Blocks rendered inside the content enqueue their styles after
			// wp_enqueue_scripts has run, and those are printed in the footer,
			// so the same cleanup has to happen right before that.
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'ayudawp_remove_gutenberg_assets' ), 1 );

			// Global Styles print their CSS inline, so dequeuing the handle is
			// not enough: the callbacks that build it have to go.
			remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
			remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

			// Without this, every rendered core block prints its own inline
			// stylesheet instead of the block library one.
			add_filter( 'should_load_separate_core_block_assets', '__return_false' );
		}

		// Disable FSE features and theme.json processing. Both run late so they
		// take effect after the active theme has declared its own supports.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'theme_json' ) ) {
			add_action( 'after_setup_theme', array( __CLASS__, 'ayudawp_disable_fse_features' ), 999 );
			add_action( 'after_setup_theme', array( __CLASS__, 'ayudawp_remove_theme_json_support' ), 999 );
		}

		// Remove Site Editor menus and block direct access to its screens.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'site_editor' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'ayudawp_remove_site_editor_menus' ), 999 );
			add_action( 'current_screen', array( __CLASS__, 'ayudawp_block_site_editor_access' ) );
		}

		// WooCommerce is not a single switch: its product editor follows the
		// block editor rules, like any other post type, and its block assets
		// belong to the frontend assets item.
		add_action( 'init', array( __CLASS__, 'ayudawp_setup_woocommerce' ) );
	}

	/**
	 * Disable the block editor for a post type.
	 *
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        Post type name.
	 * @return bool
	 */
	public static function ayudawp_filter_block_editor_for_post_type( $use_block_editor, $post_type ) {
		if ( AyudaWP_No_Gutenberg_Options::editor_disabled_for_post_type( $post_type ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Disable the block editor for a single post.
	 *
	 * Adds the rules that need the post itself: page template and post ID.
	 *
	 * @param bool    $use_block_editor Whether the post can be edited with blocks.
	 * @param WP_Post $post             Post being edited.
	 * @return bool
	 */
	public static function ayudawp_filter_block_editor_for_post( $use_block_editor, $post ) {
		// An explicit per entry choice wins over every rule.
		$preferred = AyudaWP_No_Gutenberg_Options::preferred_editor( $post );

		if ( 'block' === $preferred ) {
			return true;
		}

		if ( 'classic' === $preferred ) {
			return false;
		}

		if ( AyudaWP_No_Gutenberg_Options::editor_disabled_for_post( $post ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Remove Gutenberg assets from frontend
	 */
	public static function ayudawp_remove_gutenberg_assets() {
		// Remove block library CSS.
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );

		// Remove global styles (FSE).
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );

		// Remove block editor related scripts.
		wp_dequeue_script( 'wp-block-library' );
		wp_dequeue_script( 'wp-blocks' );
		wp_dequeue_script( 'wp-edit-post' );
		wp_dequeue_script( 'wp-block-editor' );
	}

	/**
	 * Remove Gutenberg assets from admin area
	 */
	public static function ayudawp_remove_gutenberg_admin_assets() {
		// Never strip the assets of a screen that is legitimately using the
		// block editor because no rule disables it there.
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( $screen && $screen->is_block_editor() ) {
				return;
			}
		}

		// Remove block editor CSS and JS from admin.
		wp_dequeue_style( 'wp-block-editor' );
		wp_dequeue_style( 'wp-edit-blocks' );
		wp_dequeue_script( 'wp-block-editor' );
		wp_dequeue_script( 'wp-edit-post' );
		wp_dequeue_script( 'wp-blocks' );
	}

	/**
	 * Disable Full Site Editing features
	 */
	public static function ayudawp_disable_fse_features() {
		remove_theme_support( 'editor-color-palette' );
		remove_theme_support( 'editor-gradient-presets' );
		remove_theme_support( 'editor-font-sizes' );
		remove_theme_support( 'editor-styles' );
		remove_theme_support( 'wp-block-styles' );
		remove_theme_support( 'align-wide' );
		remove_theme_support( 'custom-line-height' );
		remove_theme_support( 'custom-spacing' );
		remove_theme_support( 'custom-units' );
		remove_theme_support( 'link-color' );
		remove_theme_support( 'border' );
	}

	/**
	 * Disable block-based widgets
	 */
	public static function ayudawp_disable_block_widgets() {
		// Force classic widgets.
		add_filter( 'use_widgets_block_editor', '__return_false' );

		// Remove block widgets.
		add_action( 'widgets_init', array( __CLASS__, 'ayudawp_remove_block_widgets' ) );
	}

	/**
	 * Remove block widgets
	 */
	public static function ayudawp_remove_block_widgets() {
		global $wp_widget_factory;

		if ( isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			unregister_widget( 'WP_Widget_Block' );
		}
	}

	/**
	 * Disable block patterns and block directory
	 *
	 * Theme patterns are registered on init from the WordPress bootstrap, which
	 * runs before plugins are loaded, so the hook has to be removed while this
	 * plugin file loads and not from another init callback, which would arrive
	 * too late.
	 */
	public static function ayudawp_disable_block_patterns() {
		remove_action( 'init', '_register_theme_block_patterns' );

		// Core patterns are registered on init too, and they check the theme
		// support, so dropping it has to happen before init runs.
		add_action( 'after_setup_theme', array( __CLASS__, 'ayudawp_remove_core_block_patterns_support' ), 999 );

		// Disable remote block patterns.
		add_filter( 'should_load_remote_block_patterns', '__return_false' );

		// Remove block directory.
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}

	/**
	 * Drop the core block patterns theme support
	 */
	public static function ayudawp_remove_core_block_patterns_support() {
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Remove Gutenberg-related dashboard widgets
	 */
	public static function ayudawp_remove_dashboard_widgets() {
		// Remove the "Welcome" panel, which promotes the block editor.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}

	/**
	 * Remove Site Editor, Patterns and Fonts admin menus
	 *
	 * The Patterns submenu slug changed in WordPress 6.8 and the Fonts submenu
	 * arrived in WordPress 7.0, so every known slug is removed.
	 */
	public static function ayudawp_remove_site_editor_menus() {
		// Remove Site Editor menu (for block themes).
		remove_submenu_page( 'themes.php', 'site-editor.php' );

		// Remove Patterns submenu (WP 6.5 to 6.7 slug).
		remove_submenu_page( 'themes.php', 'site-editor.php?path=/patterns' );

		// Remove Patterns submenu (WP 6.8+ slug).
		remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );

		// Remove Fonts submenu (WP 7.0+).
		remove_submenu_page( 'themes.php', 'font-library.php' );

		// Also remove the wp_block post type edit link if present.
		remove_submenu_page( 'themes.php', 'edit.php?post_type=wp_block' );
	}

	/**
	 * Block direct access to Site Editor and Font Library screens
	 *
	 * @param WP_Screen $screen Current screen object.
	 */
	public static function ayudawp_block_site_editor_access( $screen ) {
		if ( in_array( $screen->id, array( 'site-editor', 'font-library' ), true ) && ! headers_sent() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Wire WooCommerce, if it is installed
	 *
	 * Runs on init because WooCommerce loads after this plugin.
	 */
	public static function ayudawp_setup_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// The product editor is the block editor for the product post type, so
		// it obeys the same rules instead of a switch of its own.
		add_filter( 'woocommerce_enable_gutenberg_product_editor', array( __CLASS__, 'ayudawp_filter_wc_product_editor' ) );
		add_filter( 'woocommerce_feature_product_block_editor_enabled', array( __CLASS__, 'ayudawp_filter_wc_product_editor' ) );
		add_filter( 'woocommerce_admin_features', array( __CLASS__, 'ayudawp_disable_wc_admin_features' ) );

		// Its block styles are frontend block assets like any other. They are
		// enqueued while the blocks render, so they only reach the queue after
		// wp_enqueue_scripts and end up among the footer styles.
		if ( AyudaWP_No_Gutenberg_Options::item_enabled( 'frontend_css' ) ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_woocommerce_block_assets' ), 100 );
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'ayudawp_remove_woocommerce_block_assets' ), 1 );
		}
	}

	/**
	 * Disable the WooCommerce product block editor when the rules say so
	 *
	 * @param bool $enabled Whether the block based product editor is enabled.
	 * @return bool
	 */
	public static function ayudawp_filter_wc_product_editor( $enabled ) {
		if ( AyudaWP_No_Gutenberg_Options::editor_disabled_for_post_type( 'product' ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Remove WooCommerce block assets
	 */
	public static function ayudawp_remove_woocommerce_block_assets() {
		// WooCommerce block styles and scripts.
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-blocks-vendors-style' );
		wp_dequeue_script( 'wc-blocks' );
		wp_dequeue_script( 'wc-blocks-vendors' );
		wp_dequeue_script( 'wc-blocks-checkout' );
		wp_dequeue_script( 'wc-blocks-cart' );

		// Additional WooCommerce block assets.
		wp_dequeue_style( 'wc-blocks-packages-style' );
		wp_dequeue_script( 'wc-blocks-packages' );
	}

	/**
	 * Disable WooCommerce admin block features
	 *
	 * @param array $features WooCommerce admin features.
	 * @return array
	 */
	public static function ayudawp_disable_wc_admin_features( $features ) {
		if ( ! AyudaWP_No_Gutenberg_Options::editor_disabled_for_post_type( 'product' ) ) {
			return $features;
		}

		return array_diff(
			$features,
			array(
				'product-block-editor',
				'new-product-management-experience',
			)
		);
	}

	/**
	 * Remove theme.json support completely
	 */
	public static function ayudawp_remove_theme_json_support() {
		// Remove theme.json processing safely.
		add_filter( 'wp_theme_json_data_default', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );
		add_filter( 'wp_theme_json_data_user', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );

		// Remove duotone support.
		remove_filter( 'render_block', 'wp_render_duotone_support' );

		// Remove layout support.
		remove_filter( 'render_block', 'wp_render_layout_support_flag' );

		// Remove spacing support.
		remove_filter( 'render_block', 'wp_render_spacing_support_flag' );
	}

	/**
	 * Return empty theme JSON data properly
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
	 * @return WP_Theme_JSON_Data|WP_Theme_JSON
	 */
	public static function ayudawp_return_empty_theme_json( $theme_json ) {
		if ( class_exists( 'WP_Theme_JSON' ) ) {
			return new WP_Theme_JSON( array(), 'default' );
		}

		return $theme_json;
	}
}

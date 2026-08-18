<?php
/**
 * Admin notices: activation feedback and FSE theme warnings.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Admin notices handling.
 */
class AyudaWP_No_Gutenberg_Notices {

	/**
	 * User meta storing the dismissed state of the FSE warning.
	 */
	const DISMISSED_META = 'ayudawp_no_gutenberg_fse_warning_dismissed';

	/**
	 * Screens where the persistent FSE warning is shown.
	 *
	 * @var array
	 */
	private static $warning_screens = array( 'dashboard', 'themes' );

	/**
	 * Register the hooks
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'activation_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'fse_theme_warning' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_dismiss_script' ) );
		add_action( 'wp_ajax_ayudawp_dismiss_fse_warning', array( __CLASS__, 'handle_dismiss_fse_warning' ) );

		add_action( 'after_switch_theme', array( __CLASS__, 'store_fse_theme_warning' ) );
		add_action( 'admin_notices', array( __CLASS__, 'fse_theme_activation_notice' ) );
	}

	/**
	 * Check if current theme is a block (FSE) theme
	 *
	 * @return bool
	 */
	public static function is_fse_theme() {
		return wp_is_block_theme();
	}

	/**
	 * Show the activation success notice
	 */
	public static function activation_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! get_transient( 'ayudawp_no_gutenberg_activated' ) ) {
			return;
		}

		$current_theme = wp_get_theme();

		echo '<div class="notice notice-success is-dismissible">';
		echo '<p><strong>' . esc_html__( 'No Gutenberg? activated successfully!', 'no-gutenberg' ) . '</strong> ';
		echo esc_html__( 'Gutenberg Block Editor, FSE features, and all block-related assets have been completely disabled. Welcome back to the Classic Editor!', 'no-gutenberg' );
		echo '</p>';

		echo '<p>';
		printf(
			/* translators: %s: link to the plugin settings page, with "Settings" as its text. */
			esc_html__( 'Everything is disabled out of the box. If you need to keep the block editor for some post types or user roles, review the %s.', 'no-gutenberg' ),
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . AyudaWP_No_Gutenberg_Options::PAGE_SLUG ) ) . '">' . esc_html__( 'settings page', 'no-gutenberg' ) . '</a>'
		);
		echo '</p>';

		self::block_content_paragraph();

		if ( self::is_fse_theme() ) {
			echo '<p><strong style="color: #d63638;">' . esc_html__( 'Warning:', 'no-gutenberg' ) . '</strong> ';
			printf(
				/* translators: %s is the name of the current active theme */
				esc_html__( 'Your current theme "%s" is a Full Site Editing (FSE) theme and may not work properly with this plugin. Consider switching to a classic theme that uses the WordPress Customizer for the best experience.', 'no-gutenberg' ),
				esc_html( $current_theme->get( 'Name' ) )
			);
			echo '</p>';
		}

		echo '</div>';

		delete_transient( 'ayudawp_no_gutenberg_activated' );
	}

	/**
	 * Tell the site what is going to happen to the block content it already has
	 *
	 * Landing on a site with block content is the case this plugin gets wrong
	 * most easily, so the activation notice says the number out loud instead of
	 * only announcing that everything is disabled.
	 */
	private static function block_content_paragraph() {
		if ( ! class_exists( 'AyudaWP_No_Gutenberg_Status' ) ) {
			return;
		}

		$stats = AyudaWP_No_Gutenberg_Status::block_content_stats();

		if ( $stats['with_blocks'] < 1 ) {
			return;
		}

		echo '<p>';

		if ( AyudaWP_No_Gutenberg_Options::guard_enabled() ) {
			printf(
				/* translators: 1: number of entries built with blocks, 2: opening link tag to the filtered entries list, 3: closing link tag. */
				esc_html__( 'This site has %1$d entries built with blocks. They keep the block editor so nothing breaks their markup, and everything else opens in the Classic Editor. %2$sSee them%3$s.', 'no-gutenberg' ),
				(int) $stats['with_blocks'],
				'<a href="' . esc_url( AyudaWP_No_Gutenberg_Editor_Switch::list_url() ) . '">',
				'</a>'
			);
		} else {
			printf(
				/* translators: 1: number of entries built with blocks, 2: opening link tag to the filtered entries list, 3: closing link tag. */
				esc_html__( 'Careful: this site has %1$d entries built with blocks and they are opening in the Classic Editor, which can break their markup the first time they are saved. %2$sSee them%3$s.', 'no-gutenberg' ),
				(int) $stats['with_blocks'],
				'<a href="' . esc_url( AyudaWP_No_Gutenberg_Editor_Switch::list_url() ) . '">',
				'</a>'
			);
		}

		echo '</p>';
	}

	/**
	 * Whether the persistent FSE warning has to be shown on the current screen
	 *
	 * @return bool
	 */
	private static function should_show_fse_warning() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return false;
		}

		if ( ! AyudaWP_No_Gutenberg_Options::item_enabled( 'fse_notices' ) ) {
			return false;
		}

		if ( ! self::is_fse_theme() ) {
			return false;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISSED_META, true ) ) {
			return false;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && in_array( $screen->id, self::$warning_screens, true );
	}

	/**
	 * Show the persistent FSE theme warning
	 */
	public static function fse_theme_warning() {
		if ( ! self::should_show_fse_warning() ) {
			return;
		}

		$current_theme = wp_get_theme();

		echo '<div class="notice notice-warning is-dismissible" data-dismissible="ayudawp_no_gutenberg_fse_warning">';
		echo '<p><strong>' . esc_html__( 'No Gutenberg - FSE Theme Warning', 'no-gutenberg' ) . '</strong></p>';
		echo '<p>';
		printf(
			/* translators: %s is the name of the current active theme */
			esc_html__( 'You are currently using "%s", which is a Full Site Editing (FSE) theme. This plugin disables all FSE functionality, so your theme may not work as expected. The Site Editor will be broken and many theme features will be unavailable.', 'no-gutenberg' ),
			esc_html( $current_theme->get( 'Name' ) )
		);
		echo '</p>';
		echo '<p><strong>' . esc_html__( 'Recommendation:', 'no-gutenberg' ) . '</strong> ' . esc_html__( 'Switch to a classic theme that uses the WordPress Customizer for full compatibility.', 'no-gutenberg' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Enqueue the script that stores the dismissed state of the FSE warning
	 */
	public static function enqueue_dismiss_script() {
		if ( ! self::should_show_fse_warning() ) {
			return;
		}

		$script = sprintf(
			'( function() {
				document.addEventListener( "click", function( event ) {
					if ( ! event.target.classList.contains( "notice-dismiss" ) ) {
						return;
					}
					if ( ! event.target.closest( ".notice[data-dismissible=\'ayudawp_no_gutenberg_fse_warning\']" ) ) {
						return;
					}
					var data = new FormData();
					data.append( "action", "ayudawp_dismiss_fse_warning" );
					data.append( "nonce", %1$s );
					window.fetch( %2$s, { method: "POST", credentials: "same-origin", body: data } );
				} );
			} )();',
			wp_json_encode( wp_create_nonce( 'ayudawp_dismiss_fse_warning' ) ),
			wp_json_encode( admin_url( 'admin-ajax.php' ) )
		);

		wp_add_inline_script( 'common', $script );
	}

	/**
	 * Handle the AJAX request that dismisses the FSE warning
	 */
	public static function handle_dismiss_fse_warning() {
		// Check if nonce exists and is valid.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ayudawp_dismiss_fse_warning' ) ) {
			wp_die( 'Security check failed' );
		}

		// Only the users who can see the notice may dismiss it.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		update_user_meta( get_current_user_id(), self::DISMISSED_META, true );
		wp_die();
	}

	/**
	 * Store a warning when an FSE theme is activated
	 */
	public static function store_fse_theme_warning() {
		if ( ! AyudaWP_No_Gutenberg_Options::item_enabled( 'fse_notices' ) ) {
			return;
		}

		if ( self::is_fse_theme() ) {
			$current_theme = wp_get_theme();
			set_transient( 'ayudawp_fse_theme_activated_warning', $current_theme->get( 'Name' ), 60 );
		}
	}

	/**
	 * Display the FSE theme activation warning
	 */
	public static function fse_theme_activation_notice() {
		$theme_name = get_transient( 'ayudawp_fse_theme_activated_warning' );

		if ( ! $theme_name ) {
			return;
		}

		if ( ! AyudaWP_No_Gutenberg_Options::item_enabled( 'fse_notices' ) ) {
			delete_transient( 'ayudawp_fse_theme_activated_warning' );
			return;
		}

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>' . esc_html__( 'No Gutenberg? - FSE Theme Conflict!', 'no-gutenberg' ) . '</strong></p>';
		echo '<p>';
		printf(
			/* translators: %s is the name of the FSE theme that was just activated */
			esc_html__( 'You just activated "%s", which is a Full Site Editing (FSE) theme. The No Gutenberg? plugin is currently active and disables all FSE functionality.', 'no-gutenberg' ),
			esc_html( $theme_name )
		);
		echo '</p>';
		echo '<p><strong>' . esc_html__( 'Your options:', 'no-gutenberg' ) . '</strong></p>';
		echo '<ul style="list-style: disc; margin-left: 20px;">';
		echo '<li>' . esc_html__( 'Deactivate the No Gutenberg plugin to use FSE features', 'no-gutenberg' ) . '</li>';
		echo '<li>' . esc_html__( 'Switch to a classic theme for full compatibility with No Gutenberg?', 'no-gutenberg' ) . '</li>';
		echo '<li>' . esc_html__( 'Turn off the Site Editor blocking module on the plugin settings page', 'no-gutenberg' ) . '</li>';
		echo '</ul>';
		echo '<p><em>' . esc_html__( 'Note: The Site Editor and many theme features will not work properly while No Gutenberg? is active.', 'no-gutenberg' ) . '</em></p>';
		echo '</div>';

		delete_transient( 'ayudawp_fse_theme_activated_warning' );
	}
}

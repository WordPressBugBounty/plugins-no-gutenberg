<?php
/**
 * Per entry editor switching.
 *
 * When it is allowed, every entry can be opened with the other editor and the
 * choice is remembered, so a single page can stay on the Classic Editor while
 * the rest of its post type keeps the block editor, or the other way around.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Editor switching.
 */
class AyudaWP_No_Gutenberg_Editor_Switch {

	/**
	 * Query argument carrying the chosen editor.
	 */
	const QUERY_ARG = 'ayudawp-editor';

	/**
	 * Nonce action prefix.
	 */
	const NONCE_ACTION = 'ayudawp_switch_editor_';

	/**
	 * Register the hooks
	 */
	public static function init() {
		if ( ! AyudaWP_No_Gutenberg_Options::switching_allowed() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'handle_switch' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'classic_editor_link' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_script' ) );
	}

	/**
	 * Whether an entry can be edited with both editors
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	private static function is_switchable( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( ! AyudaWP_No_Gutenberg_Options::switching_allowed( $post->post_type ) ) {
			return false;
		}

		if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * URL that opens an entry with a given editor
	 *
	 * @param int    $post_id Post ID.
	 * @param string $editor  Either 'block' or 'classic'.
	 * @return string
	 */
	public static function switch_url( $post_id, $editor ) {
		$url = add_query_arg(
			array(
				'post'          => (int) $post_id,
				'action'        => 'edit',
				self::QUERY_ARG => $editor,
			),
			admin_url( 'post.php' )
		);

		return wp_nonce_url( $url, self::NONCE_ACTION . $post_id );
	}

	/**
	 * Store the chosen editor and reload the edit screen without the arguments
	 */
	public static function handle_switch() {
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) || ! isset( $_GET['post'] ) ) {
			return;
		}

		$post_id = absint( $_GET['post'] );

		if ( ! $post_id ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_ACTION . $post_id )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! self::is_switchable( get_post( $post_id ) ) ) {
			return;
		}

		$editor = sanitize_key( wp_unslash( $_GET[ self::QUERY_ARG ] ) );

		if ( ! in_array( $editor, array( 'block', 'classic' ), true ) ) {
			return;
		}

		update_post_meta( $post_id, AyudaWP_No_Gutenberg_Options::EDITOR_META, $editor );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => $post_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	/**
	 * Add the switch links to the entries list
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( ! self::is_switchable( $post ) ) {
			return $actions;
		}

		if ( use_block_editor_for_post( $post ) ) {
			$actions['ayudawp_classic'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( self::switch_url( $post->ID, 'classic' ) ),
				esc_html__( 'Edit (Classic)', 'no-gutenberg' )
			);
		} else {
			$actions['ayudawp_block'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( self::switch_url( $post->ID, 'block' ) ),
				esc_html__( 'Edit (Blocks)', 'no-gutenberg' )
			);
		}

		return $actions;
	}

	/**
	 * Add the switch link to the Classic Editor publish box
	 *
	 * @param WP_Post $post Post being edited.
	 */
	public static function classic_editor_link( $post ) {
		if ( ! self::is_switchable( $post ) ) {
			return;
		}
		?>
		<div class="misc-pub-section misc-pub-ayudawp-editor">
			<span class="dashicons dashicons-edit"></span>
			<a href="<?php echo esc_url( self::switch_url( $post->ID, 'block' ) ); ?>">
				<?php echo esc_html__( 'Switch to the block editor', 'no-gutenberg' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Enqueue the script that adds the switch to the block editor menu
	 */
	public static function enqueue_block_editor_script() {
		$post = get_post();

		if ( ! self::is_switchable( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'no-gutenberg-editor-switch',
			AYUDAWP_NO_GUTENBERG_URL . 'assets/js/editor-switch.js',
			array( 'wp-plugins', 'wp-element', 'wp-editor' ),
			AyudaWP_No_Gutenberg::VERSION,
			true
		);

		wp_localize_script(
			'no-gutenberg-editor-switch',
			'ayudawpNoGutenbergSwitch',
			array(
				'url'   => self::switch_url( $post->ID, 'classic' ),
				'label' => __( 'Switch to the Classic Editor', 'no-gutenberg' ),
			)
		);
	}
}

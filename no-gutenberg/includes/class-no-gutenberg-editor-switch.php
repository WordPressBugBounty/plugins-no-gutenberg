<?php
/**
 * Per entry editor switching, and telling block content apart in the lists.
 *
 * When switching is allowed, every entry can be opened with the other editor
 * and the choice is remembered, so a single page can stay on the Classic Editor
 * while the rest of its post type keeps the block editor, or the other way
 * around. The entries list also says which editor each entry opens with, and
 * can be narrowed down to the content built with blocks, which is the content
 * that needs deciding about.
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
	 * Query argument that filters the entries list down to block content.
	 */
	const LIST_ARG = 'ayudawp-has-blocks';

	/**
	 * Nonce action of the entries list filter.
	 */
	const LIST_NONCE = 'ayudawp_no_gutenberg_block_list';

	/**
	 * Post type of the entries list being rendered.
	 *
	 * Taken from the screen when the view is registered instead of asked for
	 * again while rendering it, which is what keeps the count and the link
	 * pointing at the same post type.
	 *
	 * @var string
	 */
	private static $list_post_type = '';

	/**
	 * Register the hooks
	 */
	public static function init() {
		// The entries list has to tell block content apart even where nothing
		// can be switched, because that is where somebody is about to open an
		// entry built with blocks in an editor that will break it.
		if ( is_admin() && AyudaWP_No_Gutenberg_Options::editor_has_rules() ) {
			add_filter( 'display_post_states', array( __CLASS__, 'post_state' ), 10, 2 );
			add_action( 'pre_get_posts', array( __CLASS__, 'filter_entries_with_blocks' ) );
			add_action( 'edit_form_top', array( __CLASS__, 'block_content_warning' ) );
			add_action( 'current_screen', array( __CLASS__, 'add_list_view' ) );
		}

		if ( ! self::is_active() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'handle_switch' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'classic_editor_link' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_script' ) );
	}

	/**
	 * Whether any entry on this site can be moved between editors
	 *
	 * @return bool
	 */
	private static function is_active() {
		if ( AyudaWP_No_Gutenberg_Options::switching_allowed() ) {
			return true;
		}

		// With the content protection on, entries built with blocks stay on the
		// block editor while the rest of their post type is on the Classic
		// Editor, so they need a way out in both directions.
		return AyudaWP_No_Gutenberg_Options::guard_enabled()
			&& AyudaWP_No_Gutenberg_Options::editor_has_rules();
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

		if ( ! AyudaWP_No_Gutenberg_Options::post_type_can_use_block_editor( $post->post_type ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}

		if ( AyudaWP_No_Gutenberg_Options::switching_allowed( $post->post_type ) ) {
			return true;
		}

		// Outside the post types that opted in, the only entries with a real
		// choice to make are the ones the content protection is holding on the
		// block editor, and the ones somebody already decided about.
		return AyudaWP_No_Gutenberg_Options::block_content_protected( $post )
			|| '' !== AyudaWP_No_Gutenberg_Options::preferred_editor( $post );
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

	/**
	 * URL of the entries list narrowed down to content built with blocks
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	public static function list_url( $post_type = 'post' ) {
		$url = add_query_arg(
			array(
				'post_type'    => $post_type,
				self::LIST_ARG => 1,
			),
			admin_url( 'edit.php' )
		);

		return wp_nonce_url( $url, self::LIST_NONCE );
	}

	/**
	 * Say which editor an entry opens with, next to its title
	 *
	 * Two different facts, and the second one is the one that matters: an entry
	 * built with blocks that opens in the Classic Editor is an entry whose
	 * markup the next save can break.
	 *
	 * @param array   $states Post states.
	 * @param WP_Post $post   Post object.
	 * @return array
	 */
	public static function post_state( $states, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return $states;
		}

		if ( ! AyudaWP_No_Gutenberg_Options::post_type_can_use_block_editor( $post->post_type ) ) {
			return $states;
		}

		$states = (array) $states;

		// Core prints post states as they come, so they are escaped here.
		if ( use_block_editor_for_post( $post ) ) {
			$states['ayudawp_no_gutenberg'] = esc_html__( 'Block editor', 'no-gutenberg' );
		} elseif ( AyudaWP_No_Gutenberg_Options::post_has_blocks( $post ) ) {
			$states['ayudawp_no_gutenberg'] = esc_html__( 'Block content, Classic Editor', 'no-gutenberg' );
		}

		return $states;
	}

	/**
	 * Whether the entries list is being narrowed down to block content
	 *
	 * @return bool
	 */
	private static function is_filtered() {
		// The links that turn this filter on are the ones this plugin builds,
		// and they all carry the nonce. A stale bookmark simply shows the
		// unfiltered list instead of narrowing it.
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::LIST_NONCE ) ) {
			return false;
		}

		$requested = isset( $_GET[ self::LIST_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ self::LIST_ARG ] ) ) : '';

		return '1' === $requested;
	}

	/**
	 * Narrow the entries list down to the content built with blocks
	 *
	 * @param WP_Query $query Query about to run.
	 */
	public static function filter_entries_with_blocks( $query ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) || ! self::is_filtered() ) {
			return;
		}

		add_filter( 'posts_where', array( __CLASS__, 'where_has_blocks' ) );
	}

	/**
	 * Add the block content link to the views of an entries list
	 *
	 * @param WP_Screen $screen Current screen.
	 */
	public static function add_list_view( $screen ) {
		if ( ! $screen instanceof WP_Screen || 'edit' !== $screen->base ) {
			return;
		}

		if ( AyudaWP_No_Gutenberg_Options::is_internal_post_type( $screen->post_type ) ) {
			return;
		}

		if ( ! AyudaWP_No_Gutenberg_Options::post_type_can_use_block_editor( $screen->post_type ) ) {
			return;
		}

		self::$list_post_type = $screen->post_type;

		add_filter( 'views_' . $screen->id, array( __CLASS__, 'list_view' ) );
	}

	/**
	 * The block content link itself, next to All, Published and Drafts
	 *
	 * @param array $views List views.
	 * @return array
	 */
	public static function list_view( $views ) {
		$post_type = self::$list_post_type;

		if ( ! $post_type ) {
			return $views;
		}

		$count = AyudaWP_No_Gutenberg_Status::block_content_count( $post_type );

		if ( $count < 1 ) {
			return $views;
		}

		$views = (array) $views;

		$views['ayudawp_blocks'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
			esc_url( self::list_url( $post_type ) ),
			self::is_filtered() ? ' class="current" aria-current="page"' : '',
			esc_html__( 'Built with blocks', 'no-gutenberg' ),
			esc_html( number_format_i18n( $count ) )
		);

		return $views;
	}

	/**
	 * Add the block markup condition to the entries query
	 *
	 * @param string $where WHERE clause.
	 * @return string
	 */
	public static function where_has_blocks( $where ) {
		global $wpdb;

		// Only ever applies to the one query it was added for.
		remove_filter( 'posts_where', array( __CLASS__, 'where_has_blocks' ) );

		return $where . $wpdb->prepare( " AND {$wpdb->posts}.post_content LIKE %s ", '%' . $wpdb->esc_like( '<!-- wp:' ) . '%' );
	}

	/**
	 * Warn before an entry built with blocks is saved from the Classic Editor
	 *
	 * This hook only runs on the Classic Editor screen, so getting here with
	 * block content means the markup is one save away from being reflowed.
	 *
	 * @param WP_Post $post Post being edited.
	 */
	public static function block_content_warning( $post ) {
		if ( ! $post instanceof WP_Post || ! AyudaWP_No_Gutenberg_Options::post_has_blocks( $post ) ) {
			return;
		}
		?>
		<div class="notice notice-warning nogb-block-content-warning">
			<p>
				<strong><?php echo esc_html__( 'This entry is built with blocks.', 'no-gutenberg' ); ?></strong>
				<?php echo esc_html__( 'The Classic Editor reflows its markup, so saving it here can break the blocks it contains. Its content is untouched until you save.', 'no-gutenberg' ); ?>
			</p>
			<?php if ( self::is_switchable( $post ) ) : ?>
			<p>
				<a href="<?php echo esc_url( self::switch_url( $post->ID, 'block' ) ); ?>">
					<?php echo esc_html__( 'Open it in the block editor instead', 'no-gutenberg' ); ?>
				</a>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}
}

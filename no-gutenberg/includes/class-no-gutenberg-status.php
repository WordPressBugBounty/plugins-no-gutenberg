<?php
/**
 * Site status: what the plugin is actually doing on this particular site.
 *
 * This is what turns the settings screen into a diagnosis instead of a list of
 * checkboxes: it reports the real state of the site, including how much of the
 * existing content is built with blocks, which is what should drive the
 * decision to strip the frontend styles or not.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Site status report.
 */
class AyudaWP_No_Gutenberg_Status {

	/**
	 * Transient caching the block content count.
	 */
	const CONTENT_TRANSIENT = 'ayudawp_no_gutenberg_block_content';

	/**
	 * How long the block content count is cached.
	 */
	const CONTENT_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Rows of the status panel.
	 *
	 * Each row is an array with label, value and tone ('on' when the plugin is
	 * removing something, 'off' when it is not, 'info' for plain data).
	 *
	 * @return array
	 */
	public static function get_rows() {
		$theme = wp_get_theme();
		$rows  = array();

		$rows[] = array(
			'label' => __( 'Block editor', 'no-gutenberg' ),
			'value' => self::editor_summary(),
			'tone'  => AyudaWP_No_Gutenberg_Options::editor_has_rules() ? 'on' : 'off',
		);

		$patterns = count( WP_Block_Patterns_Registry::get_instance()->get_all_registered() );

		if ( ! AyudaWP_No_Gutenberg_Options::item_enabled( 'patterns' ) ) {
			$patterns_value = sprintf(
				/* translators: %d: number of block patterns registered on the site. */
				_n( '%d pattern registered', '%d patterns registered', $patterns, 'no-gutenberg' ),
				$patterns
			);
		} elseif ( $patterns > 0 ) {
			// Other plugins can register their own patterns directly, and those
			// are theirs to manage: WooCommerce, for instance, builds screens
			// out of them.
			$patterns_value = sprintf(
				/* translators: %d: number of block patterns other plugins keep registered. */
				_n(
					'Core and theme patterns removed, %d still added by other plugins',
					'Core and theme patterns removed, %d still added by other plugins',
					$patterns,
					'no-gutenberg'
				),
				$patterns
			);
		} else {
			$patterns_value = __( 'Removed, none registered', 'no-gutenberg' );
		}

		$rows[] = array(
			'label' => __( 'Block patterns', 'no-gutenberg' ),
			'value' => $patterns_value,
			'tone'  => AyudaWP_No_Gutenberg_Options::item_enabled( 'patterns' ) ? 'on' : 'off',
		);

		$weight = self::block_css_weight();

		$rows[] = array(
			'label' => __( 'Block assets on the frontend', 'no-gutenberg' ),
			'value' => AyudaWP_No_Gutenberg_Options::item_enabled( 'frontend_css' )
				? sprintf(
					/* translators: %s: approximate size of the block library stylesheet, already formatted. */
					__( 'Removed, around %s saved per page', 'no-gutenberg' ),
					size_format( $weight )
				)
				: __( 'Loaded on every page', 'no-gutenberg' ),
			'tone'  => AyudaWP_No_Gutenberg_Options::item_enabled( 'frontend_css' ) ? 'on' : 'off',
		);

		$rows[] = array(
			'label' => __( 'theme.json and Global Styles', 'no-gutenberg' ),
			'value' => AyudaWP_No_Gutenberg_Options::item_enabled( 'theme_json' )
				? __( 'Neutralized', 'no-gutenberg' )
				: __( 'Processed as usual', 'no-gutenberg' ),
			'tone'  => AyudaWP_No_Gutenberg_Options::item_enabled( 'theme_json' ) ? 'on' : 'off',
		);

		$rows[] = array(
			'label' => __( 'Widgets', 'no-gutenberg' ),
			'value' => AyudaWP_No_Gutenberg_Options::item_enabled( 'widgets' )
				? __( 'Classic widgets', 'no-gutenberg' )
				: __( 'Block widgets', 'no-gutenberg' ),
			'tone'  => AyudaWP_No_Gutenberg_Options::item_enabled( 'widgets' ) ? 'on' : 'off',
		);

		$rows[] = array(
			'label' => __( 'Site Editor', 'no-gutenberg' ),
			'value' => AyudaWP_No_Gutenberg_Options::item_enabled( 'site_editor' )
				? __( 'Menus removed and access blocked', 'no-gutenberg' )
				: __( 'Available', 'no-gutenberg' ),
			'tone'  => AyudaWP_No_Gutenberg_Options::item_enabled( 'site_editor' ) ? 'on' : 'off',
		);

		$rows[] = array(
			'label' => __( 'Active theme', 'no-gutenberg' ),
			'value' => wp_is_block_theme()
				? sprintf(
					/* translators: %s: name of the active theme. */
					__( '%s, a block theme', 'no-gutenberg' ),
					$theme->get( 'Name' )
				)
				: sprintf(
					/* translators: %s: name of the active theme. */
					__( '%s, a classic theme', 'no-gutenberg' ),
					$theme->get( 'Name' )
				),
			'tone'  => wp_is_block_theme() ? 'off' : 'info',
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$classic_products = AyudaWP_No_Gutenberg_Options::editor_disabled_for_post_type( 'product' );

			$rows[] = array(
				'label' => __( 'WooCommerce products', 'no-gutenberg' ),
				'value' => $classic_products
					? __( 'Classic product editor', 'no-gutenberg' )
					: __( 'Block product editor', 'no-gutenberg' ),
				'tone'  => $classic_products ? 'on' : 'off',
			);
		}

		if ( AyudaWP_No_Gutenberg_Options::switching_allowed() ) {
			$options = AyudaWP_No_Gutenberg_Options::get();
			$labels  = array();

			foreach ( $options['switch_post_types'] as $post_type ) {
				$object   = get_post_type_object( $post_type );
				$labels[] = $object ? $object->labels->name : $post_type;
			}

			$rows[] = array(
				'label' => __( 'Editor switching', 'no-gutenberg' ),
				'value' => sprintf(
					/* translators: %s: comma separated list of post type names. */
					__( 'Allowed entry by entry in %s', 'no-gutenberg' ),
					implode( ', ', $labels )
				),
				'tone'  => 'info',
			);
		}

		$content = self::block_content_stats();

		if ( $content['total'] > 0 ) {
			$rows[] = array(
				'label' => __( 'Your content', 'no-gutenberg' ),
				'value' => sprintf(
					/* translators: 1: number of entries built with blocks, 2: total number of published entries. */
					__( '%1$d of %2$d published entries are built with blocks', 'no-gutenberg' ),
					$content['with_blocks'],
					$content['total']
				),
				'tone'  => 'info',
			);
		}

		return $rows;
	}

	/**
	 * One line description of where the block editor is disabled.
	 *
	 * @return string
	 */
	public static function editor_summary() {
		if ( AyudaWP_No_Gutenberg_Options::is_complete() ) {
			return __( 'Disabled everywhere', 'no-gutenberg' );
		}

		$options = AyudaWP_No_Gutenberg_Options::get();
		$parts   = array();

		if ( ! empty( $options['disable_post_types'] ) ) {
			$labels = array();

			foreach ( $options['disable_post_types'] as $post_type ) {
				$object   = get_post_type_object( $post_type );
				$labels[] = $object ? $object->labels->name : $post_type;
			}

			/* translators: %s: comma separated list of post type names. */
			$parts[] = sprintf( __( 'post types: %s', 'no-gutenberg' ), implode( ', ', $labels ) );
		}

		if ( ! empty( $options['disable_roles'] ) ) {
			$names  = wp_roles()->get_names();
			$labels = array();

			foreach ( $options['disable_roles'] as $role ) {
				$labels[] = isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : $role;
			}

			/* translators: %s: comma separated list of user role names. */
			$parts[] = sprintf( __( 'roles: %s', 'no-gutenberg' ), implode( ', ', $labels ) );
		}

		if ( ! empty( $options['disable_templates'] ) ) {
			/* translators: %d: number of page templates. */
			$parts[] = sprintf( _n( '%d template', '%d templates', count( $options['disable_templates'] ), 'no-gutenberg' ), count( $options['disable_templates'] ) );
		}

		if ( ! empty( $options['disable_ids'] ) ) {
			/* translators: %d: number of individual entries. */
			$parts[] = sprintf( _n( '%d entry', '%d entries', count( $options['disable_ids'] ), 'no-gutenberg' ), count( $options['disable_ids'] ) );
		}

		if ( empty( $parts ) ) {
			return __( 'Not disabled anywhere', 'no-gutenberg' );
		}

		/* translators: %s: list of the rules that disable the block editor. */
		return sprintf( __( 'Disabled for %s', 'no-gutenberg' ), implode( '; ', $parts ) );
	}

	/**
	 * Approximate weight of the block library stylesheet.
	 *
	 * @return int Size in bytes, 0 when the file cannot be read.
	 */
	public static function block_css_weight() {
		$file = ABSPATH . WPINC . '/css/dist/block-library/style.min.css';

		if ( ! file_exists( $file ) ) {
			return 0;
		}

		$size = filesize( $file );

		return $size ? (int) $size : 0;
	}

	/**
	 * How much published content is built with blocks.
	 *
	 * @return array With total and with_blocks keys.
	 */
	public static function block_content_stats() {
		$cached = get_transient( self::CONTENT_TRANSIENT );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$needle   = '%' . $wpdb->esc_like( '<!-- wp:' ) . '%';
		$internal = $wpdb->esc_like( 'wp_' ) . '%';

		// There is no WordPress API to count how many entries contain blocks,
		// so this needs a direct query. It is cached in a transient for 12
		// hours and only runs on the plugin settings screen.
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No core API for this count; result cached in a transient right below.
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, SUM( CASE WHEN post_content LIKE %s THEN 1 ELSE 0 END ) AS with_blocks
				FROM {$wpdb->posts}
				WHERE post_status = %s AND post_type NOT LIKE %s",
				$needle,
				'publish',
				$internal
			),
			ARRAY_A
		);

		$stats = array(
			'total'       => isset( $row['total'] ) ? (int) $row['total'] : 0,
			'with_blocks' => isset( $row['with_blocks'] ) ? (int) $row['with_blocks'] : 0,
		);

		set_transient( self::CONTENT_TRANSIENT, $stats, self::CONTENT_TTL );

		return $stats;
	}

	/**
	 * Whether the site has content that would lose its styles.
	 *
	 * @return bool
	 */
	public static function has_block_content() {
		$stats = self::block_content_stats();

		return $stats['with_blocks'] > 0;
	}

	/**
	 * Discard the cached content count.
	 */
	public static function flush_content_cache() {
		delete_transient( self::CONTENT_TRANSIENT );
	}
}

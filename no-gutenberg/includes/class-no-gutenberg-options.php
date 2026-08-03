<?php
/**
 * Plugin options: schema, defaults, reading and sanitization.
 *
 * Everything here follows one single polarity: a checked box always means
 * "disable this". The master switch disables Gutenberg everywhere, and the
 * granular lists say where to disable the block editor when the master switch
 * is off. There is no "keep it here" setting anywhere.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Options handling.
 */
class AyudaWP_No_Gutenberg_Options {

	/**
	 * Option name in the options table.
	 */
	const OPTION_NAME = 'ayudawp_no_gutenberg_options';

	/**
	 * Option storing the version that last ran, used to migrate defaults.
	 */
	const VERSION_OPTION = 'ayudawp_no_gutenberg_version';

	/**
	 * Settings group used by the Settings API.
	 */
	const OPTION_GROUP = 'ayudawp_no_gutenberg';

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'no-gutenberg';

	/**
	 * Runtime cache of the merged options.
	 *
	 * @var array|null
	 */
	private static $options = null;

	/**
	 * Default options: Gutenberg disabled everywhere, no configuration needed.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Master switch. On its own it disables everything, everywhere.
			'complete'           => true,

			// Entries that already contain blocks keep the block editor, so no
			// rule can quietly send existing block content to a editor that
			// would break its markup on the first save. Checking this box gives
			// up that protection and applies the rules to everything.
			'force_classic_on_blocks' => false,

			// Where to disable the block editor when the master switch is off.
			// An empty list means "no rule of this kind", not "everywhere".
			'disable_post_types' => array(),
			'disable_roles'      => array(),
			'disable_templates'  => array(),
			'disable_ids'        => array(),

			// Post types whose entries can be opened with the other editor.
			'switch_post_types'  => array(),

			// Site wide items. They are not tied to any post type.
			'widgets'            => true,
			'patterns'           => true,
			'frontend_css'       => true,
			'theme_json'         => true,
			'site_editor'        => true,
			'fse_notices'        => true,
		);
	}

	/**
	 * Post meta storing the editor chosen for a single entry.
	 */
	const EDITOR_META = '_ayudawp_no_gutenberg_editor';

	/**
	 * Post meta the Classic Editor plugin uses for the same purpose.
	 *
	 * Coming from Classic Editor is the most common way to land on this plugin,
	 * and that plugin has been recording the editor of every entry it opened.
	 * Reading it, never writing it, means a site that switches over keeps the
	 * choices it had instead of starting from zero.
	 */
	const CLASSIC_EDITOR_META = 'classic-editor-remember';

	/**
	 * Whether existing block content is protected from the editor rules.
	 *
	 * @return bool
	 */
	public static function guard_enabled() {
		$options = self::get();

		return empty( $options['force_classic_on_blocks'] );
	}

	/**
	 * Whether an entry is built with blocks.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return bool
	 */
	public static function post_has_blocks( $post ) {
		$post = get_post( $post );

		return $post instanceof WP_Post && has_blocks( $post );
	}

	/**
	 * Whether a post type can run the block editor at all.
	 *
	 * These are the checks core makes before its own filter, and they have to
	 * be repeated here because the plugin can answer true to the per post
	 * filter after core already answered false: forcing the block editor on a
	 * post type that is not in the REST API would only produce a broken screen.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function post_type_can_use_block_editor( $post_type ) {
		if ( ! $post_type || ! post_type_exists( $post_type ) ) {
			return false;
		}

		if ( ! post_type_supports( $post_type, 'editor' ) ) {
			return false;
		}

		$object = get_post_type_object( $post_type );

		return ! $object || ! empty( $object->show_in_rest );
	}

	/**
	 * Whether a post type is one of the internal ones the block editor uses.
	 *
	 * Reusable blocks, templates, navigation menus and global styles are stored
	 * as entries and are full of block markup by definition, so they have no
	 * place in a report about the content of the site.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function is_internal_post_type( $post_type ) {
		return 0 === strpos( (string) $post_type, 'wp_' );
	}

	/**
	 * Whether an entry keeps the block editor because of its own content.
	 *
	 * This is the protection that makes the plugin non destructive on a site
	 * that already has block content: the rules decide where the block editor
	 * goes away, but they never send an entry built with blocks to an editor
	 * that would mangle its markup the first time somebody saves it.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return bool
	 */
	public static function block_content_protected( $post ) {
		if ( ! self::guard_enabled() ) {
			return false;
		}

		$post = get_post( $post );

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return self::post_has_blocks( $post ) && self::post_type_can_use_block_editor( $post->post_type );
	}

	/**
	 * Whether editors can be switched entry by entry.
	 *
	 * Switching is decided per post type, because it only makes sense where the
	 * editor rules already are: a news site may want it for pages and never for
	 * posts. A complete disable leaves no room for it.
	 *
	 * @param string $post_type Post type to check. Empty checks whether any
	 *                          post type allows switching at all.
	 * @return bool
	 */
	public static function switching_allowed( $post_type = '' ) {
		if ( self::is_complete() ) {
			return false;
		}

		$options = self::get();

		if ( '' === $post_type ) {
			return ! empty( $options['switch_post_types'] );
		}

		return in_array( $post_type, $options['switch_post_types'], true );
	}

	/**
	 * Editor explicitly chosen for an entry, if any.
	 *
	 * A stored choice wins over every rule, in both directions: it is what
	 * makes "this one page in the Classic Editor" possible while the rest of
	 * its post type keeps the block editor, and the other way around. It is
	 * read whatever the configuration is, master switch included, because it
	 * is the only setting a person made about that one entry, and it is also
	 * how an entry leaves the content protection once it has been migrated.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return string 'block', 'classic' or an empty string.
	 */
	public static function preferred_editor( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$preferred = get_post_meta( $post->ID, self::EDITOR_META, true );

		if ( in_array( $preferred, array( 'block', 'classic' ), true ) ) {
			return $preferred;
		}

		return self::inherited_editor( $post );
	}

	/**
	 * Editor the Classic Editor plugin recorded for an entry, if any.
	 *
	 * @param WP_Post $post Post object.
	 * @return string 'block', 'classic' or an empty string.
	 */
	private static function inherited_editor( $post ) {
		$inherited = get_post_meta( $post->ID, self::CLASSIC_EDITOR_META, true );

		if ( 'block-editor' === $inherited ) {
			return 'block';
		}

		if ( 'classic-editor' === $inherited ) {
			return 'classic';
		}

		return '';
	}

	/**
	 * Site wide item keys, in the order they are shown on the settings page.
	 *
	 * @return array
	 */
	public static function site_items() {
		return array(
			'widgets',
			'patterns',
			'frontend_css',
			'theme_json',
			'site_editor',
			'fse_notices',
		);
	}

	/**
	 * Site wide items that break block content when the block editor is only
	 * partially disabled, because the content built with blocks loses its CSS.
	 *
	 * @return array
	 */
	public static function content_dependent_items() {
		return array( 'frontend_css', 'theme_json' );
	}

	/**
	 * Get every option merged with its default.
	 *
	 * Values forced from wp-config.php through the NO_GUTENBERG_OPTIONS
	 * constant win over whatever is stored in the database.
	 *
	 * @return array
	 */
	public static function get() {
		if ( null === self::$options ) {
			$stored = get_option( self::OPTION_NAME, array() );

			if ( ! is_array( $stored ) ) {
				$stored = array();
			}

			$options = wp_parse_args( $stored, self::defaults() );

			if ( self::has_forced_options() ) {
				$options = wp_parse_args( self::sanitize( NO_GUTENBERG_OPTIONS, false ), $options );
			}

			self::$options = self::normalize( $options );
		}

		return self::$options;
	}

	/**
	 * Force every value into the type the rest of the plugin expects.
	 *
	 * The settings form always stores sanitized values, but the option can also
	 * come from wp-config.php, from a migration or from another plugin writing
	 * it directly, and a list given as a plain string is an easy mistake to
	 * make. Everything downstream assumes booleans and arrays, so this is where
	 * that assumption is guaranteed.
	 *
	 * @param array $options Raw options.
	 * @return array
	 */
	private static function normalize( $options ) {
		foreach ( self::defaults() as $key => $default ) {
			if ( is_bool( $default ) ) {
				$options[ $key ] = ! empty( $options[ $key ] ) && ! is_array( $options[ $key ] );
				continue;
			}

			$value = isset( $options[ $key ] ) ? $options[ $key ] : array();

			if ( ! is_array( $value ) ) {
				$value = is_scalar( $value ) && '' !== $value ? preg_split( '/[\s,]+/', (string) $value ) : array();
			}

			// Objects and nested arrays are not valid list items.
			$value = array_filter( $value, 'is_scalar' );

			if ( 'disable_ids' === $key ) {
				$options[ $key ] = self::sanitize_ids( $value );
				continue;
			}

			$options[ $key ] = array_values( array_unique( array_filter( array_map( 'strval', $value ) ) ) );
		}

		return $options;
	}

	/**
	 * Whether wp-config.php forces part of the configuration.
	 *
	 * @return bool
	 */
	public static function has_forced_options() {
		return defined( 'NO_GUTENBERG_OPTIONS' ) && is_array( NO_GUTENBERG_OPTIONS );
	}

	/**
	 * Whether the settings are read only because wp-config.php locks them.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		return self::has_forced_options()
			|| ( defined( 'NO_GUTENBERG_LOCK_SETTINGS' ) && NO_GUTENBERG_LOCK_SETTINGS );
	}

	/**
	 * Whether the settings page has to stay hidden.
	 *
	 * @return bool
	 */
	public static function is_hidden() {
		return defined( 'NO_GUTENBERG_HIDE_SETTINGS' ) && NO_GUTENBERG_HIDE_SETTINGS;
	}

	/**
	 * Whether Gutenberg is disabled everywhere.
	 *
	 * @return bool
	 */
	public static function is_complete() {
		$options = self::get();

		return ! empty( $options['complete'] );
	}

	/**
	 * Whether a site wide item is disabled.
	 *
	 * The master switch turns every item on, so partial configurations can only
	 * exist once it is off.
	 *
	 * @param string $item Item key.
	 * @return bool
	 */
	public static function item_enabled( $item ) {
		if ( self::is_complete() ) {
			return true;
		}

		$options = self::get();

		return ! empty( $options[ $item ] );
	}

	/**
	 * Whether the block editor is disabled for at least one thing.
	 *
	 * @return bool
	 */
	public static function editor_has_rules() {
		if ( self::is_complete() ) {
			return true;
		}

		$options = self::get();

		return ! empty( $options['disable_post_types'] )
			|| ! empty( $options['disable_roles'] )
			|| ! empty( $options['disable_templates'] )
			|| ! empty( $options['disable_ids'] );
	}

	/**
	 * Whether the current user matches one of the role rules.
	 *
	 * @return bool
	 */
	private static function role_rule_matches() {
		$options = self::get();

		if ( empty( $options['disable_roles'] ) || ! is_user_logged_in() ) {
			return false;
		}

		$user = wp_get_current_user();

		return ! empty( $user->roles ) && (bool) array_intersect( (array) $user->roles, $options['disable_roles'] );
	}

	/**
	 * Whether the block editor has to be disabled for a post type.
	 *
	 * Rules are independent: any rule that matches disables the block editor.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function editor_disabled_for_post_type( $post_type ) {
		if ( self::is_complete() ) {
			return true;
		}

		$options = self::get();

		if ( in_array( $post_type, $options['disable_post_types'], true ) ) {
			return true;
		}

		return self::role_rule_matches();
	}

	/**
	 * Whether the block editor has to be disabled for a single post.
	 *
	 * Adds the two rules that need the post itself: template and post ID.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return bool
	 */
	public static function editor_disabled_for_post( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::is_complete();
		}

		if ( self::editor_disabled_for_post_type( $post->post_type ) ) {
			return true;
		}

		$options = self::get();

		if ( in_array( (int) $post->ID, $options['disable_ids'], true ) ) {
			return true;
		}

		if ( ! empty( $options['disable_templates'] ) ) {
			$template = get_page_template_slug( $post );

			if ( $template && in_array( $template, $options['disable_templates'], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Discard the runtime cache.
	 */
	public static function flush_cache() {
		self::$options = null;
	}

	/**
	 * Record the version on a brand new install, or migrate an older one.
	 */
	public static function install() {
		$fresh = false === get_option( self::VERSION_OPTION, false )
			&& false === get_option( self::OPTION_NAME, false );

		if ( $fresh ) {
			update_option( self::VERSION_OPTION, AyudaWP_No_Gutenberg::VERSION );
			return;
		}

		self::maybe_upgrade();
	}

	/**
	 * Carry an older configuration over to the current one.
	 *
	 * Before 2.3.0 the plugin had no content protection and sent every entry to
	 * the Classic Editor, block content included. A site running that way is
	 * already living with it, so the new default is only for sites installing
	 * the plugin from now on: an update never changes which editor opens.
	 */
	public static function maybe_upgrade() {
		$stored = get_option( self::VERSION_OPTION, '' );

		if ( AyudaWP_No_Gutenberg::VERSION === $stored ) {
			return;
		}

		if ( '' === $stored || false === $stored ) {
			$options = get_option( self::OPTION_NAME, array() );

			if ( ! is_array( $options ) ) {
				$options = array();
			}

			if ( ! array_key_exists( 'force_classic_on_blocks', $options ) ) {
				$options['force_classic_on_blocks'] = true;
				update_option( self::OPTION_NAME, $options );
			}
		}

		update_option( self::VERSION_OPTION, AyudaWP_No_Gutenberg::VERSION );
		self::flush_cache();
	}

	/**
	 * Sanitize the settings form.
	 *
	 * @param mixed $input     Raw settings input.
	 * @param bool  $with_defaults Whether to return every key merged with the
	 *                             defaults. False returns only the given keys,
	 *                             which is what forced configurations need.
	 * @return array
	 */
	public static function sanitize( $input, $with_defaults = true ) {
		$clean = $with_defaults ? self::defaults() : array();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		if ( $with_defaults || array_key_exists( 'complete', $input ) ) {
			$clean['complete'] = ! empty( $input['complete'] );
		}

		// Deliberately outside the site wide items below: a complete disable
		// must not switch the content protection off on its own. Giving up the
		// protection is always an explicit decision.
		if ( $with_defaults || array_key_exists( 'force_classic_on_blocks', $input ) ) {
			$clean['force_classic_on_blocks'] = ! empty( $input['force_classic_on_blocks'] );
		}

		foreach ( self::site_items() as $item ) {
			if ( $with_defaults || array_key_exists( $item, $input ) ) {
				$clean[ $item ] = ! empty( $input[ $item ] );
			}
		}

		// Slugs of post types, roles and templates. Values are not checked
		// against the ones registered right now, so a rule survives while its
		// plugin or theme is momentarily inactive.
		foreach ( array( 'disable_post_types', 'disable_roles', 'switch_post_types' ) as $list ) {
			if ( ! $with_defaults && ! array_key_exists( $list, $input ) ) {
				continue;
			}

			$values = isset( $input[ $list ] ) ? (array) $input[ $list ] : array();
			$values = array_map( 'sanitize_key', array_filter( $values, 'is_scalar' ) );

			$clean[ $list ] = array_values( array_unique( array_filter( $values ) ) );
		}

		if ( $with_defaults || array_key_exists( 'disable_templates', $input ) ) {
			$templates = isset( $input['disable_templates'] ) ? (array) $input['disable_templates'] : array();
			$templates = array_map( 'sanitize_text_field', array_filter( $templates, 'is_scalar' ) );

			$clean['disable_templates'] = array_values( array_unique( array_filter( $templates ) ) );
		}

		if ( $with_defaults || array_key_exists( 'disable_ids', $input ) ) {
			$clean['disable_ids'] = self::sanitize_ids( isset( $input['disable_ids'] ) ? $input['disable_ids'] : array() );
		}

		// A complete disable already covers every site wide item. Storing them
		// as enabled keeps the state coherent if the master switch is turned
		// off later, so nothing silently comes back unchecked.
		if ( $with_defaults && ! empty( $clean['complete'] ) ) {
			foreach ( self::site_items() as $item ) {
				$clean[ $item ] = true;
			}
		}

		return $clean;
	}

	/**
	 * Sanitize a list of post IDs, given either as an array or as free text.
	 *
	 * @param mixed $ids Raw IDs.
	 * @return array List of positive integers.
	 */
	public static function sanitize_ids( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = is_scalar( $ids ) ? preg_split( '/[\s,]+/', (string) $ids ) : array();
		}

		// intval, not absint: a negative ID is a typo, not a request for its
		// positive counterpart.
		$ids = array_map( 'intval', array_filter( (array) $ids, 'is_scalar' ) );
		$ids = array_filter(
			$ids,
			function ( $id ) {
				return $id > 0;
			}
		);

		sort( $ids );

		return array_values( array_unique( $ids ) );
	}
}

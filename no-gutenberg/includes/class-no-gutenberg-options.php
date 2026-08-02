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
	 * its post type keeps the block editor, and the other way around.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return string 'block', 'classic' or an empty string.
	 */
	public static function preferred_editor( $post ) {
		$post = get_post( $post );

		if ( ! $post || ! self::switching_allowed( $post->post_type ) ) {
			return '';
		}

		$preferred = get_post_meta( $post->ID, self::EDITOR_META, true );

		return in_array( $preferred, array( 'block', 'classic' ), true ) ? $preferred : '';
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

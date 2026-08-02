<?php
/**
 * Settings page: Settings > No Gutenberg.
 *
 * The screen is built around scope, not around a list of modules: one master
 * switch that disables Gutenberg everywhere, and, once it is off, the rules
 * that say where to disable the block editor plus the site wide items. Every
 * checkbox has the same meaning, checked means disabled, so there is never a
 * setting that undoes another one.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Settings page handling.
 */
class AyudaWP_No_Gutenberg_Settings {

	/**
	 * Hook suffix of the settings page.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	/**
	 * Post types that never make sense as a rule.
	 *
	 * @var array
	 */
	private static $excluded_post_types = array(
		'attachment',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
	);

	/**
	 * Register the hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'update_option_' . AyudaWP_No_Gutenberg_Options::OPTION_NAME, array( 'AyudaWP_No_Gutenberg_Status', 'flush_content_cache' ) );
	}

	/**
	 * Add the settings page under the Settings menu
	 */
	public static function add_menu() {
		if ( AyudaWP_No_Gutenberg_Options::is_hidden() ) {
			return;
		}

		self::$hook_suffix = add_options_page(
			__( 'No Gutenberg', 'no-gutenberg' ),
			__( 'No Gutenberg', 'no-gutenberg' ),
			'manage_options',
			AyudaWP_No_Gutenberg_Options::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue the settings page styles and script
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( ! self::$hook_suffix || $hook_suffix !== self::$hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'no-gutenberg-admin',
			AYUDAWP_NO_GUTENBERG_URL . 'assets/css/admin.css',
			array(),
			AyudaWP_No_Gutenberg::VERSION
		);

		if ( AyudaWP_No_Gutenberg_Options::is_locked() ) {
			return;
		}

		// Progressive enhancement: without JavaScript every section stays
		// visible and the explanatory notes still describe the behavior.
		$script = sprintf(
			'( function() {
				var master = document.getElementById( "nogb-complete" );
				var scope = document.getElementById( "nogb-scope" );
				if ( ! master || ! scope ) {
					return;
				}
				var risky = %1$s;
				var hasBlockContent = %2$s;
				var warned = false;
				function sync() {
					scope.hidden = master.checked;
					if ( master.checked || warned || ! hasBlockContent ) {
						return;
					}
					warned = true;
					risky.forEach( function( id ) {
						var box = document.getElementById( id );
						if ( box ) {
							box.checked = false;
						}
					} );
					var note = document.getElementById( "nogb-risky-note" );
					if ( note ) {
						note.hidden = false;
					}
				}
				master.addEventListener( "change", sync );
				sync();
			} )();',
			wp_json_encode( array( 'nogb-frontend_css', 'nogb-theme_json' ) ),
			wp_json_encode( AyudaWP_No_Gutenberg_Status::has_block_content() )
		);

		wp_add_inline_script( 'common', $script );
	}

	/**
	 * Register the setting
	 */
	public static function register_settings() {
		register_setting(
			AyudaWP_No_Gutenberg_Options::OPTION_GROUP,
			AyudaWP_No_Gutenberg_Options::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'AyudaWP_No_Gutenberg_Options', 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Labels and descriptions of every site wide item
	 *
	 * @return array
	 */
	private static function get_item_labels() {
		return array(
			'widgets'      => array(
				'label'       => __( 'Restore the classic Widgets screen', 'no-gutenberg' ),
				'description' => __( 'Disables the block-based widget editor.', 'no-gutenberg' ),
			),
			'patterns'     => array(
				'label'       => __( 'Remove block patterns and the Block Directory', 'no-gutenberg' ),
				'description' => __( 'Core, theme and remote patterns are never registered.', 'no-gutenberg' ),
			),
			'frontend_css' => array(
				'label'       => __( 'Remove block styles and scripts from the frontend', 'no-gutenberg' ),
				'description' => __( 'Takes out the block library CSS, the Global Styles inline CSS and the block scripts on every page.', 'no-gutenberg' ),
			),
			'theme_json'   => array(
				'label'       => __( 'Neutralize theme.json and Global Styles', 'no-gutenberg' ),
				'description' => __( 'Empties the theme.json data and drops the FSE theme supports, such as wide alignment or custom spacing.', 'no-gutenberg' ),
			),
			'site_editor'  => array(
				'label'       => __( 'Block the Site Editor', 'no-gutenberg' ),
				'description' => __( 'Removes the Editor, Patterns and Fonts items under Appearance and blocks direct access to those screens.', 'no-gutenberg' ),
			),
			'fse_notices'  => array(
				'label'       => __( 'Warn me when a block theme is active', 'no-gutenberg' ),
				'description' => __( 'Shows a dismissible notice on the Dashboard and Appearance screens.', 'no-gutenberg' ),
			),
		);
	}

	/**
	 * Render the settings page
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = AyudaWP_No_Gutenberg_Options::get();
		$locked  = AyudaWP_No_Gutenberg_Options::is_locked();
		?>
		<div class="wrap nogb-settings">
			<h1><?php echo esc_html__( 'No Gutenberg', 'no-gutenberg' ); ?></h1>

			<?php
			self::render_status_panel();

			if ( $locked ) {
				self::render_lock_notice();
			}
			?>

			<div class="nogb-layout">
			<div class="nogb-main">

			<form action="options.php" method="post">
				<?php settings_fields( AyudaWP_No_Gutenberg_Options::OPTION_GROUP ); ?>

				<div class="nogb-master">
					<label for="nogb-complete">
						<input type="checkbox" id="nogb-complete" name="<?php echo esc_attr( AyudaWP_No_Gutenberg_Options::OPTION_NAME ); ?>[complete]" value="1" <?php checked( ! empty( $options['complete'] ) ); ?> <?php disabled( $locked ); ?> />
						<strong><?php echo esc_html__( 'Disable Gutenberg completely', 'no-gutenberg' ); ?></strong>
					</label>
					<p class="description">
						<?php echo esc_html__( 'Everything, everywhere: the block editor for every post type and user, block widgets, patterns, frontend block assets, theme.json and the Site Editor. This is how the plugin has always worked and needs no further setup. Uncheck it to choose exactly what to disable.', 'no-gutenberg' ); ?>
					</p>

				</div>

				<div id="nogb-scope" class="nogb-scope">
					<h2><?php echo esc_html__( 'Disable the block editor for...', 'no-gutenberg' ); ?></h2>
					<p class="description">
						<?php echo esc_html__( 'Rules are independent: anything that matches gets the Classic Editor. Leave every rule empty and the block editor stays available.', 'no-gutenberg' ); ?>
					</p>

					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<p class="description">
						<?php echo esc_html__( 'WooCommerce products play by these same rules: check Products below to force the classic product editor. Its block styles are removed with the rest of the frontend block assets.', 'no-gutenberg' ); ?>
					</p>
					<?php endif; ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php echo esc_html__( 'Post types', 'no-gutenberg' ); ?></th>
								<td><?php self::render_post_types_field( $options, $locked ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php echo esc_html__( 'User roles', 'no-gutenberg' ); ?></th>
								<td><?php self::render_roles_field( $options, $locked ); ?></td>
							</tr>
							<?php if ( self::get_page_templates() ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html__( 'Page templates', 'no-gutenberg' ); ?></th>
								<td><?php self::render_templates_field( $options, $locked ); ?></td>
							</tr>
							<?php endif; ?>
							<tr>
								<th scope="row"><?php echo esc_html__( 'Individual entries', 'no-gutenberg' ); ?></th>
								<td><?php self::render_ids_field( $options, $locked ); ?></td>
							</tr>
						</tbody>
					</table>

					<h2><?php echo esc_html__( 'Site wide', 'no-gutenberg' ); ?></h2>
					<p class="description">
						<?php echo esc_html__( 'These belong to the whole site, not to a post type, so they are not affected by the rules above.', 'no-gutenberg' ); ?>
					</p>

					<p id="nogb-risky-note" class="nogb-inline-notice" hidden>
						<?php echo esc_html__( 'Two options were unchecked for you: with the block editor still available somewhere, removing the block styles would leave that content unstyled on the frontend. Check them again if you know what you are doing.', 'no-gutenberg' ); ?>
					</p>

					<?php self::render_site_items( $options, $locked ); ?>
				</div>

				<?php
				if ( ! $locked ) {
					submit_button();
				}
				?>
			</form>

			</div><!-- .nogb-main -->

			<aside class="nogb-sidebar">
				<?php
				$promo_banner = new AyudaWP_No_Gutenberg_Promo_Banner( 'nogb' );
				$promo_banner->render();
				?>
			</aside>

			</div><!-- .nogb-layout -->
		</div>
		<?php
	}

	/**
	 * Render the status panel
	 */
	private static function render_status_panel() {
		$rows = AyudaWP_No_Gutenberg_Status::get_rows();
		?>
		<div class="nogb-status">
			<h2><?php echo esc_html__( 'What is happening on this site', 'no-gutenberg' ); ?></h2>
			<table class="nogb-status-table">
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td class="<?php echo esc_attr( 'nogb-tone-' . $row['tone'] ); ?>"><?php echo esc_html( $row['value'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the notice shown when wp-config.php locks the settings
	 */
	private static function render_lock_notice() {
		?>
		<div class="notice notice-info nogb-lock-notice">
			<p>
				<strong><?php echo esc_html__( 'Settings locked from wp-config.php', 'no-gutenberg' ); ?></strong>
			</p>
			<p>
				<?php echo esc_html__( 'This configuration is fixed in code, so it cannot be changed from here. Remove the No Gutenberg constants from wp-config.php to edit it again.', 'no-gutenberg' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the post types checklist
	 *
	 * @param array $options Current options.
	 * @param bool  $locked  Whether the settings are read only.
	 */
	private static function render_post_types_field( $options, $locked ) {
		$disable_name = AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[disable_post_types][]';
		$switch_name  = AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[switch_post_types][]';
		$post_types   = get_post_types( array( 'show_ui' => true ), 'objects' );
		?>
		<table class="nogb-matrix">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Post type', 'no-gutenberg' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Classic Editor', 'no-gutenberg' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Let each entry switch', 'no-gutenberg' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $post_types as $post_type ) {
					if ( in_array( $post_type->name, self::$excluded_post_types, true ) ) {
						continue;
					}

					if ( ! post_type_supports( $post_type->name, 'editor' ) ) {
						continue;
					}

					$disable_id = 'nogb-pt-' . $post_type->name;
					$switch_id  = 'nogb-sw-' . $post_type->name;
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $disable_id ); ?>">
								<?php echo esc_html( $post_type->labels->name ); ?>
								<code><?php echo esc_html( $post_type->name ); ?></code>
							</label>
						</th>
						<td>
							<input type="checkbox" id="<?php echo esc_attr( $disable_id ); ?>" name="<?php echo esc_attr( $disable_name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['disable_post_types'], true ) ); ?> <?php disabled( $locked ); ?> />
						</td>
						<td>
							<input type="checkbox" id="<?php echo esc_attr( $switch_id ); ?>" name="<?php echo esc_attr( $switch_name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['switch_post_types'], true ) ); ?> <?php disabled( $locked ); ?> />
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<p class="description">
			<?php echo esc_html__( 'The second column adds "Edit (Classic)" and "Edit (Blocks)" links to the entries of that post type, plus a switch inside both editors. Each entry remembers the editor chosen for it, and that choice beats the rules in both directions.', 'no-gutenberg' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the user roles checklist
	 *
	 * @param array $options Current options.
	 * @param bool  $locked  Whether the settings are read only.
	 */
	private static function render_roles_field( $options, $locked ) {
		$name = AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[disable_roles][]';

		echo '<fieldset class="nogb-checklist">';

		foreach ( wp_roles()->get_names() as $role => $role_name ) {
			?>
			<label for="<?php echo esc_attr( 'nogb-role-' . $role ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( 'nogb-role-' . $role ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $options['disable_roles'], true ) ); ?> <?php disabled( $locked ); ?> />
				<?php echo esc_html( translate_user_role( $role_name ) ); ?>
			</label>
			<?php
		}

		echo '</fieldset>';

		echo '<p class="description">' . esc_html__( 'Users with these roles always get the Classic Editor, whatever they are editing.', 'no-gutenberg' ) . '</p>';
	}

	/**
	 * Page templates of the active theme
	 *
	 * @return array File name to template name.
	 */
	private static function get_page_templates() {
		$theme = wp_get_theme();

		return $theme ? $theme->get_page_templates( null, 'page' ) : array();
	}

	/**
	 * Render the page templates checklist
	 *
	 * @param array $options Current options.
	 * @param bool  $locked  Whether the settings are read only.
	 */
	private static function render_templates_field( $options, $locked ) {
		$name = AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[disable_templates][]';

		echo '<fieldset class="nogb-checklist">';

		foreach ( self::get_page_templates() as $file => $template_name ) {
			?>
			<label for="<?php echo esc_attr( 'nogb-tpl-' . sanitize_key( $file ) ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( 'nogb-tpl-' . sanitize_key( $file ) ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $file ); ?>" <?php checked( in_array( $file, $options['disable_templates'], true ) ); ?> <?php disabled( $locked ); ?> />
				<?php echo esc_html( $template_name ); ?>
				<code><?php echo esc_html( $file ); ?></code>
			</label>
			<?php
		}

		echo '</fieldset>';
	}

	/**
	 * Render the individual post IDs field
	 *
	 * @param array $options Current options.
	 * @param bool  $locked  Whether the settings are read only.
	 */
	private static function render_ids_field( $options, $locked ) {
		$name  = AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[disable_ids]';
		$value = implode( ', ', $options['disable_ids'] );
		?>
		<input type="text" class="regular-text" id="nogb-ids" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="12, 34, 56" <?php disabled( $locked ); ?> />
		<p class="description"><?php echo esc_html__( 'Entry IDs separated by commas. Useful for that one landing page that has to stay on the Classic Editor.', 'no-gutenberg' ); ?></p>
		<?php
	}

	/**
	 * Render the site wide items
	 *
	 * @param array $options Current options.
	 * @param bool  $locked  Whether the settings are read only.
	 */
	private static function render_site_items( $options, $locked ) {
		$labels = self::get_item_labels();

		echo '<fieldset class="nogb-items">';

		foreach ( AyudaWP_No_Gutenberg_Options::site_items() as $item ) {
			$id = 'nogb-' . $item;
			?>
			<p class="nogb-item">
				<label for="<?php echo esc_attr( $id ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( AyudaWP_No_Gutenberg_Options::OPTION_NAME . '[' . $item . ']' ); ?>" value="1" <?php checked( ! empty( $options[ $item ] ) ); ?> <?php disabled( $locked ); ?> />
					<?php echo esc_html( $labels[ $item ]['label'] ); ?>
				</label>
				<span class="description"><?php echo esc_html( $labels[ $item ]['description'] ); ?></span>
			</p>
			<?php
		}

		echo '</fieldset>';
	}
}

<?php
/**
 * Settings page: Settings > No Gutenberg.
 *
 * The screen is built around scope, not around a list of modules: one master
 * switch that disables the block editor everywhere, the rules that say where to
 * disable it when the master switch is off, and the items that belong to the
 * whole site. Every checkbox has the same meaning, checked means disabled, so
 * there is never a setting that undoes another one.
 *
 * Everything is on screen at all times. Until 2.4.0 the master switch hid the
 * rest of the page, which read as "there is nothing else to decide" while the
 * plugin was deciding plenty, two of them able to strip the styles off content
 * that is already published.
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
	 * Action name of the Classic Editor import handler.
	 */
	const IMPORT_ACTION = 'ayudawp_no_gutenberg_import_classic';

	/**
	 * Register the hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'update_option_' . AyudaWP_No_Gutenberg_Options::OPTION_NAME, array( 'AyudaWP_No_Gutenberg_Status', 'flush_content_cache' ) );
		add_action( 'admin_post_' . self::IMPORT_ACTION, array( __CLASS__, 'handle_classic_editor_import' ) );
	}

	/**
	 * URL of the settings page
	 *
	 * @return string
	 */
	public static function page_url() {
		return admin_url( 'options-general.php?page=' . AyudaWP_No_Gutenberg_Options::PAGE_SLUG );
	}

	/**
	 * Add the settings page under the Settings menu
	 */
	public static function add_menu() {
		if ( AyudaWP_No_Gutenberg_Options::is_hidden() ) {
			return;
		}

		self::$hook_suffix = add_options_page(
			__( 'No Gutenberg?', 'no-gutenberg' ),
			__( 'No Gutenberg?', 'no-gutenberg' ),
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

		// Progressive enhancement, and nothing else: both notes are rendered
		// with their real state from PHP, so the page says the same thing with
		// JavaScript off. The script only keeps them in sync while boxes are
		// ticked, and it never changes a checkbox, because a page that silently
		// unticks what you are looking at cannot be trusted to save what it
		// shows.
		$ids = array();

		foreach ( AyudaWP_No_Gutenberg_Options::content_dependent_items() as $item ) {
			$ids[] = 'nogb-' . $item;
		}

		$script = sprintf(
			'( function() {
				var master = document.getElementById( "nogb-complete" );
				var risky = %1$s;
				var atRisk = %2$s;
				var riskyNote = document.getElementById( "nogb-risky-note" );
				var masterNote = document.getElementById( "nogb-master-note" );
				function sync() {
					if ( masterNote && master ) {
						masterNote.hidden = ! master.checked;
					}
					if ( ! riskyNote ) {
						return;
					}
					var checked = risky.some( function( id ) {
						var box = document.getElementById( id );
						return box && box.checked;
					} );
					riskyNote.hidden = ! ( atRisk && checked );
				}
				if ( master ) {
					master.addEventListener( "change", sync );
				}
				risky.forEach( function( id ) {
					var box = document.getElementById( id );
					if ( box ) {
						box.addEventListener( "change", sync );
					}
				} );
				sync();
			} )();',
			wp_json_encode( $ids ),
			wp_json_encode( AyudaWP_No_Gutenberg_Options::content_needs_block_styles() )
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
			<h1><?php echo esc_html__( 'No Gutenberg?', 'no-gutenberg' ); ?></h1>
			<hr class="wp-header-end" />

			<?php settings_errors(); ?>

			<div class="nogb-page">

			<?php
			if ( $locked ) {
				self::render_lock_notice();
			}

			self::render_classic_editor_import( $locked );

			self::render_status_panel();
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
						<?php echo esc_html__( 'The block editor goes away for every post type, every user and every page template, so none of the rules below is needed. Uncheck it to disable the block editor only where you say. The items under "Site wide" are decided on their own, with this box checked or not.', 'no-gutenberg' ); ?>
					</p>

					<p class="nogb-guard">
						<label for="nogb-force_classic_on_blocks">
							<input type="checkbox" id="nogb-force_classic_on_blocks" name="<?php echo esc_attr( AyudaWP_No_Gutenberg_Options::OPTION_NAME ); ?>[force_classic_on_blocks]" value="1" <?php checked( ! empty( $options['force_classic_on_blocks'] ) ); ?> <?php disabled( $locked ); ?> />
							<?php echo esc_html__( 'Also on entries that are already built with blocks', 'no-gutenberg' ); ?>
						</label>
						<span class="description">
							<?php echo esc_html__( 'Unchecked, an entry that contains blocks keeps the block editor whatever the rules above say, so nothing you set here can break existing content. Check it and the rules apply to everything, block content included: the Classic Editor reflows block markup, so those entries can lose their blocks the first time somebody saves them.', 'no-gutenberg' ); ?>
						</span>
					</p>

				</div>

				<div id="nogb-scope" class="nogb-scope">
					<h2><?php echo esc_html__( 'Disable the block editor for...', 'no-gutenberg' ); ?></h2>
					<p class="description">
						<?php echo esc_html__( 'Rules are independent: anything that matches gets the Classic Editor. Leave every rule empty and the block editor stays available.', 'no-gutenberg' ); ?>
					</p>

					<p id="nogb-master-note" class="nogb-inline-note" <?php echo empty( $options['complete'] ) ? 'hidden' : ''; ?>>
						<?php echo esc_html__( 'These rules are not in use while "Disable Gutenberg completely" is checked above. They stay on screen so you can see what is set, and they come into play the moment you uncheck it.', 'no-gutenberg' ); ?>
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

					<h2 class="nogb-section"><?php echo esc_html__( 'Site wide', 'no-gutenberg' ); ?></h2>
					<p class="description">
						<?php echo esc_html__( 'These belong to the whole site, not to a post type, so they are not affected by the rules above.', 'no-gutenberg' ); ?>
					</p>

					<?php self::render_risk_note( $options ); ?>

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

			</div><!-- .nogb-page -->
		</div>
		<?php
	}

	/**
	 * Render the status panel
	 *
	 * It is a panel and not a notice on purpose: it is the permanent answer to
	 * "what is this plugin doing to my site", it is read every time the page is
	 * opened, and it is never dismissed. Dressing it as an admin notice made it
	 * look like something that was about to go away.
	 */
	private static function render_status_panel() {
		$rows = AyudaWP_No_Gutenberg_Status::get_rows();
		?>
		<div class="nogb-status">
			<h2>
				<span class="dashicons dashicons-info" aria-hidden="true"></span>
				<?php echo esc_html__( 'What is happening on this site', 'no-gutenberg' ); ?>
			</h2>
			<table class="nogb-status-table">
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td class="<?php echo esc_attr( 'nogb-tone-' . $row['tone'] ); ?>">
							<?php echo esc_html( $row['value'] ); ?>
							<?php if ( ! empty( $row['links'] ) ) : ?>
								<span class="nogb-status-links">
									<?php foreach ( $row['links'] as $link ) : ?>
										<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
									<?php endforeach; ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the notice shown when wp-config.php locks the settings
	 *
	 * Styled by this plugin instead of with the core notice classes, like every
	 * other box on this page: core moves every div.notice to the top of the
	 * screen (wp-admin/js/common.js:1090), which takes it out of the container
	 * that gives this page a single width.
	 */
	private static function render_lock_notice() {
		?>
		<div class="nogb-lock-notice">
			<p>
				<strong><?php echo esc_html__( 'Settings locked from wp-config.php', 'no-gutenberg' ); ?></strong>
			</p>
			<p>
				<?php echo esc_html__( 'This configuration is fixed in code, so it cannot be changed from here. Remove the No Gutenberg? constants from wp-config.php to edit it again.', 'no-gutenberg' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the warning about block content losing its styles
	 *
	 * The two items it talks about are the only ones in the page that can break
	 * something that already works, so the warning is rendered from PHP with
	 * the real state of the options and only hidden when there is nothing to
	 * warn about. It is a warning and not an automatic correction: a page that
	 * unticks boxes while you read it cannot be trusted to save what it shows.
	 *
	 * @param array $options Current options.
	 */
	private static function render_risk_note( $options ) {
		if ( ! AyudaWP_No_Gutenberg_Options::content_needs_block_styles() ) {
			return;
		}

		$checked = false;

		foreach ( AyudaWP_No_Gutenberg_Options::content_dependent_items() as $item ) {
			if ( ! empty( $options[ $item ] ) ) {
				$checked = true;
				break;
			}
		}
		?>
		<p id="nogb-risky-note" class="nogb-inline-notice" <?php echo $checked ? '' : 'hidden'; ?>>
			<?php
			if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
				echo esc_html__( 'The active theme is a block theme, so everything it paints comes out of theme.json. With the two options below checked, the frontend of this site renders with no styles at all. Uncheck them, or move to a classic theme.', 'no-gutenberg' );
			} else {
				$stats = AyudaWP_No_Gutenberg_Status::block_content_stats();

				printf(
					/* translators: %d: number of entries built with blocks. */
					esc_html( _n(
						'This site has %d entry built with blocks. With the two options below checked, it renders on the frontend without any block styles: no layout, no colors, no spacing. The entry keeps the block editor, so its markup is safe, but what visitors see is not.',
						'This site has %d entries built with blocks. With the two options below checked, they render on the frontend without any block styles: no layout, no colors, no spacing. The entries keep the block editor, so their markup is safe, but what visitors see is not.',
						$stats['with_blocks'],
						'no-gutenberg'
					) ),
					(int) $stats['with_blocks']
				);
			}
			?>
		</p>
		<?php
	}

	/**
	 * Render the offer to reproduce a Classic Editor configuration
	 *
	 * @param bool $locked Whether the settings are read only.
	 */
	private static function render_classic_editor_import( $locked ) {
		if ( $locked ) {
			return;
		}

		$detected = AyudaWP_No_Gutenberg_Options::pending_classic_editor_import();

		if ( ! $detected ) {
			return;
		}
		?>
		<div class="nogb-import">
			<h2><?php echo esc_html__( 'This site was using Classic Editor', 'no-gutenberg' ); ?></h2>
			<p><?php echo esc_html( self::classic_editor_summary( $detected ) ); ?></p>

			<h3><?php echo esc_html__( 'What importing it would write', 'no-gutenberg' ); ?></h3>
			<table class="nogb-import-table">
				<tbody>
					<?php foreach ( self::classic_editor_preview( $detected ) as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td><?php echo esc_html( $row['value'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description">
				<?php echo esc_html__( 'The per user preference of Classic Editor is not imported, because there is no equivalent here. You are asked once: Classic Editor deletes its own settings when it is uninstalled, so there would be nothing left to read later.', 'no-gutenberg' ); ?>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>" />
				<?php wp_nonce_field( self::IMPORT_ACTION ); ?>
				<p>
					<button type="submit" class="button button-primary" name="nogb-action" value="import"><?php echo esc_html__( 'Import these settings', 'no-gutenberg' ); ?></button>
					<button type="submit" class="button" name="nogb-action" value="dismiss"><?php echo esc_html__( 'Keep my current settings', 'no-gutenberg' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * What the import would write, line by line
	 *
	 * Built from the same classic_editor_equivalent() the handler stores, so
	 * what is on screen is the configuration that is about to be saved and not
	 * a description of it that can drift away from the code. Anything the
	 * import erases is named here with what is in it today: the rules that
	 * Classic Editor does not have are cleared, and that is the only part of
	 * this that cannot be undone with a click.
	 *
	 * @param array $detected Configuration as Classic Editor had it.
	 * @return array Rows of label and value.
	 */
	private static function classic_editor_preview( $detected ) {
		$equivalent = AyudaWP_No_Gutenberg_Options::classic_editor_equivalent( $detected );
		$current    = AyudaWP_No_Gutenberg_Options::get();
		$labels     = self::get_item_labels();

		$rows = array(
			array(
				'label' => __( 'Disable Gutenberg completely', 'no-gutenberg' ),
				'value' => __( 'Unchecked. Classic Editor says it post type by post type, so that is what the rules below will do.', 'no-gutenberg' ),
			),
			array(
				'label' => __( 'Classic Editor for', 'no-gutenberg' ),
				'value' => self::post_type_list( $equivalent['disable_post_types'] ),
			),
			array(
				'label' => __( 'Let each entry switch', 'no-gutenberg' ),
				'value' => self::post_type_list( $equivalent['switch_post_types'] ),
			),
		);

		$erased = array();

		if ( $current['disable_roles'] ) {
			$erased[] = sprintf(
				/* translators: %d: number of user roles with a rule. */
				_n( '%d user role', '%d user roles', count( $current['disable_roles'] ), 'no-gutenberg' ),
				count( $current['disable_roles'] )
			);
		}

		if ( $current['disable_templates'] ) {
			$erased[] = sprintf(
				/* translators: %d: number of page templates with a rule. */
				_n( '%d page template', '%d page templates', count( $current['disable_templates'] ), 'no-gutenberg' ),
				count( $current['disable_templates'] )
			);
		}

		if ( $current['disable_ids'] ) {
			$erased[] = sprintf(
				/* translators: %d: number of individual entries with a rule. */
				_n( '%d individual entry', '%d individual entries', count( $current['disable_ids'] ), 'no-gutenberg' ),
				count( $current['disable_ids'] )
			);
		}

		$rows[] = array(
			'label' => __( 'User roles, page templates and entries', 'no-gutenberg' ),
			'value' => $erased
				? sprintf(
					/* translators: %s: list of the rules that would be deleted, already formatted. */
					__( 'Cleared, and you have rules there right now: %s. Classic Editor has nothing of the kind, so there is nothing to import into them.', 'no-gutenberg' ),
					implode( ', ', $erased )
				)
				: __( 'Left empty, which is how they are now. Classic Editor has no rules of this kind.', 'no-gutenberg' ),
		);

		$unchecked = array();

		foreach ( AyudaWP_No_Gutenberg_Options::content_dependent_items() as $item ) {
			if ( ! empty( $current[ $item ] ) && empty( $equivalent[ $item ] ) ) {
				$unchecked[] = $labels[ $item ]['label'];
			}
		}

		$rows[] = array(
			'label' => __( 'Site wide items', 'no-gutenberg' ),
			'value' => $unchecked
				? sprintf(
					/* translators: %s: list of site wide items that would be unchecked, already formatted. */
					__( 'Left as they are, except these, unchecked because this site has content built with blocks that needs them: %s', 'no-gutenberg' ),
					implode( '; ', $unchecked )
				)
				: __( 'Left exactly as they are. Classic Editor has nothing to say about them.', 'no-gutenberg' ),
		);

		return $rows;
	}

	/**
	 * Post type names of a list of slugs, ready to print
	 *
	 * @param array $slugs Post type slugs.
	 * @return string
	 */
	private static function post_type_list( $slugs ) {
		$names = array();

		foreach ( $slugs as $slug ) {
			$post_type = get_post_type_object( $slug );
			$names[]   = $post_type ? $post_type->labels->name : $slug;
		}

		if ( ! $names ) {
			return __( 'No post type', 'no-gutenberg' );
		}

		return implode( ', ', $names );
	}

	/**
	 * One sentence saying what Classic Editor had configured
	 *
	 * The block editor cases get a second sentence, because reproducing them is
	 * a reasonable thing to want and also the one import that can leave this
	 * plugin with nothing to do. Saying it before the click is cheaper than a
	 * support thread about a plugin that does nothing.
	 *
	 * @param array $detected Configuration as Classic Editor had it.
	 * @return string
	 */
	private static function classic_editor_summary( $detected ) {
		if ( 'classic' === $detected['replace'] ) {
			return __( 'It opened everything in the Classic Editor and let each entry switch to the block editor. Here that is one rule per post type, plus the per entry switch on all of them.', 'no-gutenberg' );
		}

		if ( ! empty( $detected['allow'] ) ) {
			return __( 'It opened everything in the block editor and let each entry switch to the Classic Editor. Reproducing that leaves this plugin with no rule disabling anything: the block editor stays, and the only way out is the per entry switch.', 'no-gutenberg' );
		}

		return __( 'It opened everything in the block editor, with no way to switch. Reproducing that leaves this plugin with nothing to disable, so you would probably rather keep what you have now and set your own rules.', 'no-gutenberg' );
	}

	/**
	 * Apply or dismiss the Classic Editor import
	 */
	public static function handle_classic_editor_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'no-gutenberg' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::IMPORT_ACTION );

		$action   = isset( $_POST['nogb-action'] ) ? sanitize_text_field( wp_unslash( $_POST['nogb-action'] ) ) : '';
		$detected = AyudaWP_No_Gutenberg_Options::pending_classic_editor_import();

		if ( 'import' === $action && $detected && ! AyudaWP_No_Gutenberg_Options::is_locked() ) {
			update_option(
				AyudaWP_No_Gutenberg_Options::OPTION_NAME,
				AyudaWP_No_Gutenberg_Options::classic_editor_equivalent( $detected )
			);

			add_settings_error(
				AyudaWP_No_Gutenberg_Options::OPTION_GROUP,
				'nogb-imported',
				__( 'The Classic Editor configuration was reproduced. Check it below before you leave this page.', 'no-gutenberg' ),
				'success'
			);
		} else {
			add_settings_error(
				AyudaWP_No_Gutenberg_Options::OPTION_GROUP,
				'nogb-dismissed',
				__( 'Nothing was changed, and the Classic Editor configuration will not be offered again.', 'no-gutenberg' ),
				'info'
			);
		}

		AyudaWP_No_Gutenberg_Options::forget_classic_editor_import();
		AyudaWP_No_Gutenberg_Options::flush_cache();

		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', self::page_url() ) );
		exit;
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
				foreach ( AyudaWP_No_Gutenberg_Options::manageable_post_types() as $slug ) {
					$post_type = get_post_type_object( $slug );

					if ( ! $post_type ) {
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

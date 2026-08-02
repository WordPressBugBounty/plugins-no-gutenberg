<?php
/**
 * Promo Banner class
 *
 * Promotional banner that rotates AyudaWP's WordPress services. On every
 * settings-page load it shows a random subset of service cards.
 *
 * Mirror of ayudawp-promo-banner-catalog-servicios.md (the single source of
 * truth). The banner shows only services, so there is no host or sibling plugin
 * to exclude and no wordpress.org install modal (Thickbox) to load for it.
 *
 * @package No Gutenberg
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * No Gutenberg Promo Banner class
 */
class AyudaWP_No_Gutenberg_Promo_Banner {

	/**
	 * How many service cards the banner shows at once.
	 *
	 * @var int
	 */
	const CARDS = 3;

	/**
	 * CSS class prefix.
	 *
	 * @var string
	 */
	private $css_prefix;

	/**
	 * Constructor.
	 *
	 * @param string $css_prefix CSS class prefix.
	 */
	public function __construct( $css_prefix ) {
		$this->css_prefix = $css_prefix;
	}

	/**
	 * Get services catalog.
	 *
	 * Mirror of ayudawp-promo-banner-catalog-servicios.md. All strings use the
	 * literal text domain (WPCS / make-pot requirement).
	 *
	 * @return array
	 */
	private function get_services_catalog() {
		return array(
			'maintenance' => array(
				'icon'        => 'dashicons-admin-tools',
				'title'       => __( 'Need help with your website?', 'no-gutenberg' ),
				'description' => __( 'Professional WordPress maintenance: security monitoring, regular backups, performance optimization, and priority support.', 'no-gutenberg' ),
				'button'      => __( 'Learn more', 'no-gutenberg' ),
				'url'         => __( 'https://mantenimiento.ayudawp.com/en/', 'no-gutenberg' ),
			),
			'consultancy' => array(
				'icon'        => 'dashicons-businessman',
				'title'       => __( 'WordPress consultancy', 'no-gutenberg' ),
				'description' => __( 'One-on-one online sessions to solve your WordPress doubts, get expert advice, and make better decisions for your project.', 'no-gutenberg' ),
				'button'      => __( 'Book a session', 'no-gutenberg' ),
				'url'         => 'https://servicios.ayudawp.com/producto/consultoria-online-wordpress/',
			),
			'hacked'      => array(
				'icon'        => 'dashicons-sos',
				'title'       => __( 'Hacked website?', 'no-gutenberg' ),
				'description' => __( 'Fast recovery service for compromised WordPress sites. We clean malware, fix vulnerabilities, and restore your site security.', 'no-gutenberg' ),
				'button'      => __( 'Get help now', 'no-gutenberg' ),
				'url'         => 'https://servicios.ayudawp.com/producto/wordpress-hackeado/',
			),
			'development' => array(
				'icon'        => 'dashicons-editor-code',
				'title'       => __( 'Custom development', 'no-gutenberg' ),
				'description' => __( 'Need a custom plugin, theme modifications, or specific functionality? We build tailored WordPress solutions for your needs.', 'no-gutenberg' ),
				'button'      => __( 'Request a quote', 'no-gutenberg' ),
				'url'         => 'https://servicios.ayudawp.com/producto/desarrollo-wordpress/',
			),
			'hosting'     => array(
				'icon'        => 'dashicons-cloud-saved',
				'title'       => __( 'Hosting built for WordPress', 'no-gutenberg' ),
				'description' => __( 'Google Cloud servers, automatic geo-located daily backups, and 24/7 expert support. Speed, security, and migration tools included.', 'no-gutenberg' ),
				'button'      => __( 'Learn more', 'no-gutenberg' ),
				/* translators: SiteGround affiliate URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://stgrnd.co/telladowpbox', 'no-gutenberg' ),
			),
		);
	}

	/**
	 * Get a random subset of services to display.
	 *
	 * @param int $count Number of services to return.
	 * @return array Subset of the catalog, keyed by service key.
	 */
	private function get_random_services( $count = self::CARDS ) {
		$services = $this->get_services_catalog();

		$count       = max( 1, min( (int) $count, count( $services ) ) );
		$random_keys = array_rand( $services, $count );
		if ( ! is_array( $random_keys ) ) {
			$random_keys = array( $random_keys );
		}

		$result = array();
		foreach ( $random_keys as $key ) {
			$result[ $key ] = $services[ $key ];
		}

		return $result;
	}

	/**
	 * Render the promotional banner as stacked sidebar boxes.
	 */
	public function render() {
		$services = $this->get_random_services();
		$prefix   = $this->css_prefix;

		foreach ( $services as $service ) :
			?>
			<div class="<?php echo esc_attr( $prefix ); ?>-promo-box">
				<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?> <?php echo esc_attr( $prefix ); ?>-promo-icon"></span>
				<h4 class="<?php echo esc_attr( $prefix ); ?>-promo-box-title"><?php echo esc_html( $service['title'] ); ?></h4>
				<p class="<?php echo esc_attr( $prefix ); ?>-promo-box-description"><?php echo esc_html( $service['description'] ); ?></p>
				<a href="<?php echo esc_url( $service['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
					<?php echo esc_html( $service['button'] ); ?>
				</a>
			</div>
			<?php
		endforeach;
	}
}

<?php
/**
 * Plugin Name: No Gutenberg - Disable Blocks Editor and Global Styles - Back to Classic Editor
 * Plugin URI: https://servicios.ayudawp.com/
 * Description: Complete elimination of Gutenberg Block Editor, FSE Global Styles, Block Widgets, Patterns, and WooCommerce blocks. Get back to the reliable Classic Editor with zero block-related overhead.
 * Version: 2.1.1
 * Author: Fernando Tellado
 * Author URI: https://ayudawp.com/
 *
 * @package No Gutenberg
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: no-gutenberg
 * Requires at least: 4.9
 * Requires PHP: 7.4
 * Tested up to: 6.9
 *
 * No Gutenberg plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * No Gutenberg plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with No Gutenberg. If not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Main plugin class
 */
class AyudaWP_No_Gutenberg {

    /**
     * Plugin version
     */
    const VERSION = '2.1.1';

    /**
     * Initialize the plugin
     */
    public static function init() {
        // Load text domain for translations
        add_action( 'plugins_loaded', array( __CLASS__, 'ayudawp_load_textdomain' ) );
        
        // Disable Gutenberg Block Editor
        add_action( 'init', array( __CLASS__, 'ayudawp_disable_gutenberg_editor' ) );
        
        // Remove Gutenberg assets and features
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_gutenberg_assets' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_gutenberg_admin_assets' ) );
        
        // Disable FSE features
        add_action( 'after_setup_theme', array( __CLASS__, 'ayudawp_disable_fse_features' ) );
        
        // Remove block-based widgets
        add_action( 'init', array( __CLASS__, 'ayudawp_disable_block_widgets' ) );
        
        // Remove block patterns and directory
        add_action( 'init', array( __CLASS__, 'ayudawp_disable_block_patterns' ) );
        
        // Remove Gutenberg dashboard widgets and admin features
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'ayudawp_remove_dashboard_widgets' ) );
        
        // Remove Site Editor and Patterns admin menus (WP 6.5+)
        add_action( 'admin_menu', array( __CLASS__, 'ayudawp_remove_site_editor_menus' ), 999 );
        
        // Block direct access to Site Editor
        add_action( 'current_screen', array( __CLASS__, 'ayudawp_block_site_editor_access' ) );
        
        // Disable WooCommerce blocks if WooCommerce is active
        add_action( 'init', array( __CLASS__, 'ayudawp_disable_woocommerce_blocks' ) );
        
        // Remove theme.json support
        add_action( 'after_setup_theme', array( __CLASS__, 'ayudawp_remove_theme_json_support' ) );
        
        // Remove block editor from custom post types
        add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
    }

    /**
     * Load plugin text domain for translations
     * Note: Since WordPress 4.6, this is handled automatically for plugins hosted on WordPress.org
     */
    public static function ayudawp_load_textdomain() {
        // Removed load_plugin_textdomain() as it's handled automatically by WordPress.org
    }

    /**
     * Disable Gutenberg Block Editor completely
     */
    public static function ayudawp_disable_gutenberg_editor() {
        // Remove Gutenberg from all post types
        add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
        
        // Legacy support for older WordPress versions
        if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '<' ) ) {
            add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
        }
        
        // Remove "Try Gutenberg" dashboard widget
        remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );
        
        // Remove Gutenberg admin menu
        remove_action( 'admin_menu', 'gutenberg_menu' );
        remove_action( 'admin_init', 'gutenberg_redirect_demo' );
    }

    /**
     * Remove Gutenberg assets from frontend
     */
    public static function ayudawp_remove_gutenberg_assets() {
        // Remove block library CSS
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-blocks-style' );
        wp_dequeue_style( 'wc-blocks-vendors-style' );
        
        // Remove global styles (FSE)
        wp_dequeue_style( 'global-styles' );
        wp_dequeue_style( 'classic-theme-styles' );
        
        // Remove block editor related scripts
        wp_dequeue_script( 'wp-block-library' );
        wp_dequeue_script( 'wp-blocks' );
        wp_dequeue_script( 'wp-edit-post' );
        wp_dequeue_script( 'wp-block-editor' );
        
        // Remove WooCommerce block styles if present
        if ( class_exists( 'WooCommerce' ) ) {
            wp_dequeue_style( 'wc-blocks-style' );
            wp_dequeue_style( 'wc-blocks-vendors-style' );
        }
    }

    /**
     * Remove Gutenberg assets from admin area
     */
    public static function ayudawp_remove_gutenberg_admin_assets() {
        // Remove block editor CSS and JS from admin
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
        // Remove theme.json support
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
        
        // Disable site editor
        add_filter( 'block_editor_settings_all', array( __CLASS__, 'ayudawp_disable_site_editor' ) );
    }

    /**
     * Disable site editor
     */
    public static function ayudawp_disable_site_editor( $settings ) {
        $settings['canUser'] = false;
        return $settings;
    }

    /**
     * Disable block-based widgets
     */
    public static function ayudawp_disable_block_widgets() {
        // Force classic widgets
        add_filter( 'use_widgets_block_editor', '__return_false' );
        
        // Remove block widgets
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
     */
    public static function ayudawp_disable_block_patterns() {
        // Remove block patterns
        remove_theme_support( 'core-block-patterns' );
        
        // Disable remote block patterns
        add_filter( 'should_load_remote_block_patterns', '__return_false' );
        
        // Remove block directory
        remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
        
        // Remove pattern categories
        add_action( 'init', function() {
            remove_all_actions( 'init', 11 );
        }, 9 );
    }

    /**
     * Remove Gutenberg-related dashboard widgets
     */
    public static function ayudawp_remove_dashboard_widgets() {
        // Remove "Try Gutenberg" widget
        remove_meta_box( 'try_gutenberg', 'dashboard', 'normal' );
        
        // Remove "Welcome" panel that promotes Gutenberg
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
    }

    /**
     * Remove Site Editor and Patterns admin menus
     * Since WP 6.5+, Patterns submenu is shown even for classic themes
     */
    public static function ayudawp_remove_site_editor_menus() {
        // Remove Site Editor menu (for block themes)
        remove_submenu_page( 'themes.php', 'site-editor.php' );
        
        // Remove Patterns submenu (added in WP 6.5+ for all themes)
        remove_submenu_page( 'themes.php', 'site-editor.php?path=/patterns' );
        
        // Also remove the wp_block post type edit link if present
        remove_submenu_page( 'themes.php', 'edit.php?post_type=wp_block' );
    }

    /**
     * Block direct access to Site Editor pages
     *
     * @param WP_Screen $screen Current screen object.
     */
    public static function ayudawp_block_site_editor_access( $screen ) {
        if ( 'site-editor' === $screen->id && ! headers_sent() ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
    }

    /**
     * Disable WooCommerce blocks if WooCommerce is installed
     */
    public static function ayudawp_disable_woocommerce_blocks() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Remove WooCommerce block assets
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ayudawp_remove_woocommerce_block_assets' ), 100 );
        
        // Disable WooCommerce blocks
        add_filter( 'woocommerce_enable_gutenberg_product_editor', '__return_false' );
        add_filter( 'woocommerce_admin_features', array( __CLASS__, 'ayudawp_disable_wc_admin_features' ) );
        
        // Force classic checkout and cart
        add_filter( 'woocommerce_feature_product_block_editor_enabled', '__return_false' );
    }

    /**
     * Remove WooCommerce block assets
     */
    public static function ayudawp_remove_woocommerce_block_assets() {
        // WooCommerce block styles and scripts
        wp_dequeue_style( 'wc-blocks-style' );
        wp_dequeue_style( 'wc-blocks-vendors-style' );
        wp_dequeue_script( 'wc-blocks' );
        wp_dequeue_script( 'wc-blocks-vendors' );
        wp_dequeue_script( 'wc-blocks-checkout' );
        wp_dequeue_script( 'wc-blocks-cart' );
        
        // Additional WooCommerce block assets
        wp_dequeue_style( 'wc-blocks-packages-style' );
        wp_dequeue_script( 'wc-blocks-packages' );
    }

    /**
     * Disable WooCommerce admin block features
     */
    public static function ayudawp_disable_wc_admin_features( $features ) {
        return array_diff( $features, array(
            'product-block-editor',
            'new-product-management-experience'
        ) );
    }

    /**
     * Remove theme.json support completely
     */
    public static function ayudawp_remove_theme_json_support() {
        // Remove theme.json processing safely
        add_filter( 'wp_theme_json_data_default', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );
        add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );
        add_filter( 'wp_theme_json_data_user', array( __CLASS__, 'ayudawp_return_empty_theme_json' ) );
        
        // Remove duotone support
        remove_filter( 'render_block', 'wp_render_duotone_support' );
        
        // Remove layout support
        remove_filter( 'render_block', 'wp_render_layout_support_flag' );
        
        // Remove spacing support
        remove_filter( 'render_block', 'wp_render_spacing_support_flag' );
    }

    /**
     * Return empty theme JSON data properly
     */
    public static function ayudawp_return_empty_theme_json( $theme_json ) {
        if ( class_exists( 'WP_Theme_JSON' ) ) {
            return new WP_Theme_JSON( array(), 'default' );
        }
        return $theme_json;
    }
}

// Initialize the plugin
AyudaWP_No_Gutenberg::init();

/**
 * Plugin activation hook - display notice about successful activation
 */
register_activation_hook( __FILE__, 'ayudawp_no_gutenberg_activation' );
function ayudawp_no_gutenberg_activation() {
    // Set a transient to show activation notice
    set_transient( 'ayudawp_no_gutenberg_activated', true, 60 );
}

/**
 * Show activation notice and FSE theme warnings
 */
add_action( 'admin_notices', 'ayudawp_no_gutenberg_admin_notices' );
function ayudawp_no_gutenberg_admin_notices() {
    // Show activation success notice
    if ( get_transient( 'ayudawp_no_gutenberg_activated' ) ) {
        $current_theme = wp_get_theme();
        $is_fse_theme = ayudawp_is_fse_theme();
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>' . esc_html__( 'No Gutenberg activated successfully!', 'no-gutenberg' ) . '</strong> ';
        echo esc_html__( 'Gutenberg Block Editor, FSE features, and all block-related assets have been completely disabled. Welcome back to the Classic Editor!', 'no-gutenberg' );
        echo '</p>';
        
        if ( $is_fse_theme ) {
            echo '<p><strong style="color: #d63638;">' . esc_html__( 'Warning:', 'no-gutenberg' ) . '</strong> ';
            printf( 
                // translators: %s is the name of the current active theme
                esc_html__( 'Your current theme "%s" is a Full Site Editing (FSE) theme and may not work properly with this plugin. Consider switching to a classic theme that uses the WordPress Customizer for the best experience.', 'no-gutenberg' ),
                esc_html( $current_theme->get( 'Name' ) )
            );
            echo '</p>';
        }
        echo '</div>';
        delete_transient( 'ayudawp_no_gutenberg_activated' );
    }
    
    // Show persistent FSE theme warning if user hasn't dismissed it
    if ( ayudawp_is_fse_theme() && ! get_user_meta( get_current_user_id(), 'ayudawp_no_gutenberg_fse_warning_dismissed', true ) ) {
        $current_theme = wp_get_theme();
        echo '<div class="notice notice-warning is-dismissible" data-dismissible="ayudawp_no_gutenberg_fse_warning">';
        echo '<p><strong>' . esc_html__( 'No Gutenberg - FSE Theme Warning', 'no-gutenberg' ) . '</strong></p>';
        echo '<p>';
        printf( 
            // translators: %s is the name of the current active theme
            esc_html__( 'You are currently using "%s", which is a Full Site Editing (FSE) theme. This plugin disables all FSE functionality, so your theme may not work as expected. The Site Editor will be broken and many theme features will be unavailable.', 'no-gutenberg' ),
            esc_html( $current_theme->get( 'Name' ) )
        );
        echo '</p>';
        echo '<p><strong>' . esc_html__( 'Recommendation:', 'no-gutenberg' ) . '</strong> ' . esc_html__( 'Switch to a classic theme that uses the WordPress Customizer for full compatibility.', 'no-gutenberg' ) . '</p>';
        echo '</div>';
        
        // Add script to handle dismissible notice
        echo '<script>
        jQuery(document).ready(function($) {
            $(document).on("click", ".notice[data-dismissible=\'ayudawp_no_gutenberg_fse_warning\'] .notice-dismiss", function() {
                $.post(ajaxurl, {
                    action: "ayudawp_dismiss_fse_warning",
                    nonce: "' . esc_js( wp_create_nonce( 'ayudawp_dismiss_fse_warning' ) ) . '"
                });
            });
        });
        </script>';
    }
}

/**
 * Handle AJAX request to dismiss FSE warning
 */
add_action( 'wp_ajax_ayudawp_dismiss_fse_warning', 'ayudawp_handle_dismiss_fse_warning' );
function ayudawp_handle_dismiss_fse_warning() {
    // Check if nonce exists and is valid
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ayudawp_dismiss_fse_warning' ) ) {
        wp_die( 'Security check failed' );
    }
    
    update_user_meta( get_current_user_id(), 'ayudawp_no_gutenberg_fse_warning_dismissed', true );
    wp_die();
}

/**
 * Check if current theme is FSE theme
 */
function ayudawp_is_fse_theme() {
    if ( function_exists( 'wp_is_block_theme' ) ) {
        return wp_is_block_theme();
    }
    
    // Fallback for older WordPress versions
    $theme_json_file = get_stylesheet_directory() . '/theme.json';
    $parent_theme_json_file = get_template_directory() . '/theme.json';
    
    return file_exists( $theme_json_file ) || file_exists( $parent_theme_json_file );
}

/**
 * Show warning when FSE theme is activated
 */
add_action( 'after_switch_theme', 'ayudawp_fse_theme_activation_warning' );
function ayudawp_fse_theme_activation_warning() {
    if ( ayudawp_is_fse_theme() ) {
        $current_theme = wp_get_theme();
        set_transient( 'ayudawp_fse_theme_activated_warning', $current_theme->get( 'Name' ), 60 );
    }
}

/**
 * Display FSE theme activation warning
 */
add_action( 'admin_notices', 'ayudawp_fse_theme_activation_notice' );
function ayudawp_fse_theme_activation_notice() {
    $theme_name = get_transient( 'ayudawp_fse_theme_activated_warning' );
    if ( $theme_name ) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>' . esc_html__( 'No Gutenberg - FSE Theme Conflict!', 'no-gutenberg' ) . '</strong></p>';
        echo '<p>';
        printf( 
            // translators: %s is the name of the FSE theme that was just activated
            esc_html__( 'You just activated "%s", which is a Full Site Editing (FSE) theme. The No Gutenberg plugin is currently active and disables all FSE functionality.', 'no-gutenberg' ),
            esc_html( $theme_name )
        );
        echo '</p>';
        echo '<p><strong>' . esc_html__( 'Your options:', 'no-gutenberg' ) . '</strong></p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        echo '<li>' . esc_html__( 'Deactivate the No Gutenberg plugin to use FSE features', 'no-gutenberg' ) . '</li>';
        echo '<li>' . esc_html__( 'Switch to a classic theme for full compatibility with No Gutenberg', 'no-gutenberg' ) . '</li>';
        echo '</ul>';
        echo '<p><em>' . esc_html__( 'Note: The Site Editor and many theme features will not work properly while No Gutenberg is active.', 'no-gutenberg' ) . '</em></p>';
        echo '</div>';
        delete_transient( 'ayudawp_fse_theme_activated_warning' );
    }
}

/**
 * Add action links to plugin page
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ayudawp_no_gutenberg_action_links' );
function ayudawp_no_gutenberg_action_links( $links ) {
    $support_link = '<a href="https://servicios.ayudawp.com/" target="_blank">' . esc_html__( 'Support', 'no-gutenberg' ) . '</a>';
    array_unshift( $links, $support_link );
    return $links;
}
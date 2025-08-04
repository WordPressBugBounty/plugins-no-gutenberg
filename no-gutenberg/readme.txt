=== No Gutenberg - Disable Blocks Editor and Global Styles - Back to Classic Editor ===
Contributors: fernandot, ayudawp
Tags: gutenberg, classic editor, FSE, blocks, woocommerce
Requires at least: 4.9
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 2.0
License: GPLv2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Complete elimination of Gutenberg Block Editor, FSE Global Styles, Block Widgets, Patterns, and WooCommerce blocks. Back to Classic Editor.

== Description ==

The most comprehensive solution to completely remove Gutenberg Block Editor and all its related features from your WordPress installation. This plugin doesn't just disable the block editor - it eliminates every trace of block-related functionality for maximum performance and compatibility.

**What this plugin removes:**

= Core Gutenberg Features =
* Gutenberg Block Editor (completely disabled)
* Full Site Editing (FSE) Global Styles and inline CSS
* Block-based Widget Editor (reverts to Classic Widgets)
* Block Patterns and Pattern Directory
* Theme.json support and processing
* Block Directory integration
* Site Editor functionality

= Performance Optimizations =
* Removes all block-related CSS and JavaScript files
* Eliminates Global Styles inline CSS on every page
* Removes unused block library assets
* Disables block editor admin assets
* Removes duotone and layout support filters

= WooCommerce Integration =
* Disables WooCommerce block-based checkout and cart
* Removes WooCommerce block editor for products
* Eliminates WooCommerce block assets and styles
* Forces classic WooCommerce experience

= Admin Experience =
* Removes "Try Gutenberg" dashboard widgets
* Disables Gutenberg-related admin menus
* Shows activation success notice
* Adds support link to plugin actions

**Zero Configuration Required** - Simply activate the plugin and everything is automatically disabled. No settings page needed because it just works!

This plugin is perfect for:
- Users who prefer the Classic Editor
- Sites requiring maximum compatibility with legacy themes and plugins
- Performance-focused installations
- Users who want to eliminate block-related overhead completely

== Installation ==

1. Go to your WP Dashboard > Plugins and search for 'no gutenberg' or…
2. Download the plugin from WP repository
3. Upload the 'no-gutenberg' folder to the '/wp-content/plugins/' directory
4. Activate the plugin through the 'Plugins' menu in WordPress
5. That's it! Gutenberg is completely gone and Classic Editor is restored

== Frequently Asked Questions ==

= What's new in version 2.0? =

Version 2.0 is a complete rewrite that eliminates ALL block-related functionality:
- Removes FSE Global Styles and theme.json support
- Disables block-based widgets completely
- Removes block patterns and block directory
- Eliminates WooCommerce blocks if WooCommerce is installed
- Removes all block-related CSS and JavaScript assets
- Much better performance and more thorough removal

= Does this work with WooCommerce? =

Yes! The plugin automatically detects WooCommerce and disables all WooCommerce blocks, forcing the classic checkout, cart, and product editor experience.

= Will this break my existing content? =

No. Your existing posts and pages will continue to work normally. The plugin only affects the editing experience and removes block-related overhead.

= What happens if I deactivate the plugin? =

All Gutenberg functionality will be restored immediately. Your site will return to using the block editor and all block-related features.

= Is this compatible with all themes and plugins? =

Yes. This plugin is designed for maximum compatibility. It works with all properly coded themes and plugins by simply removing block functionality rather than conflicting with it.

= Why choose this over other similar plugins? =

This plugin is the most comprehensive solution available. While other plugins only disable the editor, this one removes ALL block-related functionality including FSE styles, widgets, patterns, WooCommerce blocks, and performance-heavy assets.

== Screenshots ==

1. WordPress posts page with Classic Editor restored
2. Classic Widgets interface instead of block widgets
3. Activation success notice
4. FSE theme activation warning

== Changelog ==

= 2.0 =
* Complete plugin rewrite with object-oriented approach
* Added removal of FSE Global Styles and theme.json support
* Disabled block-based widgets completely
* Removed block patterns and block directory
* Added WooCommerce blocks removal
* Eliminated all block-related CSS and JavaScript assets
* Added activation notice and support links
* Improved performance by removing more block overhead
* Better code organization and security
* Updated minimum PHP requirement to 7.4
* Network/Multisite compatible

= 1.1.0 =
* Fix Fatal Error with prior version

= 1.0.9 =
* Tested up to WordPress 6.7.1

= 1.0.8 =
* Tested up to WordPress 6.6.2

= 1.0.7 =
* Tested up to WordPress 6.4

= 1.0.6 =
* Tested up to WordPress 6.2

= 1.0.5 =
* Tested up to WordPress 6.1

= 1.0.4 =
* Tested up to WordPress 6.0.2

= 1.0.3 =
* Tested up to WordPress 6.0

= 1.0.2 =
* Added the action to remove the FSE Global Styles

= 1.0.1 =
* Better and simply readme file

= 1.0.0 =
* Initial release with basic Gutenberg removal
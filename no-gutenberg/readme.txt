=== No Gutenberg - Disable Blocks Editor and Global Styles - Back to Classic Editor ===
Contributors: fernandot, ayudawp
Tags: gutenberg, classic editor, FSE, blocks, woocommerce
Requires at least: 6.1
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 2.2.0
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
* Removes the welcome panel that promotes the block editor
* Removes the Patterns submenu from the Appearance menu (WP 6.5+)
* Removes the Fonts submenu from the Appearance menu (WP 7.0+)
* Blocks access to Site Editor pages
* Shows activation success notice
* Warns you when a block theme is active
* Adds settings and support links to plugin actions

**Zero configuration required** - Activate the plugin and everything is disabled right away. No setup needed because it just works!

**Full control when you need it** - Since version 2.2.0 there is an optional settings page under Settings > No Gutenberg, built around one master switch. Leave it on and the plugin behaves exactly as it always did. Turn it off and you decide where the block editor goes away: by post type, by user role, by page template or by individual entry. The site wide pieces (classic widgets, block patterns, frontend block assets, theme.json and the Site Editor) are then yours to keep or remove one by one. Every checkbox means the same thing, so no setting ever undoes another one.

**Switch editors entry by entry, where it makes sense** - Editor switching is chosen per post type, right next to the rule that disables the block editor. Enable it for pages, for instance, and every page gets "Edit (Classic)" and "Edit (Blocks)" links in the list plus a switch inside both editors, while your posts stay untouched. Each entry remembers the editor chosen for it, and that choice beats the rules in both directions: one landing page can stay on the Classic Editor while the rest keep blocks, or the other way around.

**It tells you what it is doing** - The settings screen opens with a status panel for your particular site: how many block patterns are being blocked, how much weight is off every page, whether your theme is a block theme, and how much of your published content is really built with blocks. That last number is the one that should decide whether you strip the block styles from the frontend or not, and no other plugin tells you.

**Built for agencies and managed sites** - The whole configuration can be fixed from wp-config.php, and the settings screen locked as read only or hidden altogether, so a site you deliver keeps the setup you left behind.

This plugin is perfect for:
- Users who prefer the Classic Editor
- Sites requiring maximum compatibility with legacy themes and plugins
- Performance-focused installations
- Users who want to eliminate block-related overhead completely
- Sites that need the Classic Editor for everyone except a few post types or editors

== Installation ==

1. Go to your WP Dashboard > Plugins and search for 'no gutenberg' or…
2. Download the plugin from WP repository
3. Upload the 'no-gutenberg' folder to the '/wp-content/plugins/' directory
4. Activate the plugin through the 'Plugins' menu in WordPress
5. That's it! Gutenberg is completely gone and Classic Editor is restored

== Frequently Asked Questions ==

= Do I have to configure anything? =

No. The plugin disables everything as soon as you activate it, exactly as it always did. The settings page under Settings > No Gutenberg is there only if you want to bring some part of the block editor back.

= Can I disable Gutenberg only for some content or some people? =

Yes. Go to Settings > No Gutenberg, uncheck "Disable Gutenberg completely", and then choose where the block editor should go away: post types, user roles, page templates or individual entry IDs. Rules are independent, so anything that matches gets the Classic Editor and everything else keeps working as usual.

= Can I disable just one part of Gutenberg? =

Yes. Once the master switch is off, the site wide pieces are independent checkboxes, so you can, for example, restore the classic Widgets screen and remove the block patterns while leaving the editor itself alone.

= If I only disable the editor for some content, what happens to the block styles? =

That is exactly why the plugin unchecks the frontend block assets and theme.json options for you when you switch to a partial setup: the content that still uses blocks would lose its styles on the frontend. The status panel tells you how many of your published entries are built with blocks, so you can decide with real numbers.

= Can I lock the configuration so a client cannot change it? =

Yes, from wp-config.php. Use NO_GUTENBERG_LOCK_SETTINGS to show the screen as read only, NO_GUTENBERG_HIDE_SETTINGS to hide it completely, and NO_GUTENBERG_OPTIONS with an array of settings to fix the configuration in code, above whatever is stored in the database.

= Can I open one entry with the other editor? =

Yes. In the post types table there is a second column, "Let each entry switch": check it for the post types where it makes sense. Those entries then get "Edit (Classic)" and "Edit (Blocks)" links in the list and a switch inside both editors, and each one remembers the editor you chose for it.

= Does this work with WooCommerce? =

Yes. Products behave like any other post type: with the master switch on, or with Products checked in the rules, the classic product editor is forced and the WooCommerce block assets are removed from the frontend. If you leave blocks on for products, the block based product editor keeps working, without a separate setting contradicting it.

= Will this break my existing content? =

No. Your existing posts and pages will continue to work normally. The plugin only affects the editing experience and removes block-related overhead.

= What happens if I deactivate the plugin? =

All Gutenberg functionality will be restored immediately. Your site will return to using the block editor and all block-related features.

= Is this compatible with all themes and plugins? =

Yes. This plugin is designed for maximum compatibility. It works with all properly coded themes and plugins by simply removing block functionality rather than conflicting with it.

= Can I use it with a block theme? =

You can, but block themes are built around the very features this plugin removes, so their Site Editor and template editing will not work while it is active. The plugin warns you when it detects a block theme. If you need that theme, switch off the Site Editor and theme.json modules on the settings page, or use a classic theme instead.

= Why choose this over other similar plugins? =

This plugin is the most comprehensive solution available. While other plugins only disable the editor, this one removes ALL block-related functionality including FSE styles, widgets, patterns, WooCommerce blocks, and performance-heavy assets.

== Screenshots ==

1. WordPress posts page with Classic Editor restored
2. Classic Widgets interface instead of block widgets
3. Activation success notice
4. FSE theme activation warning

== Changelog ==

= 2.2.0 =
* New: Optional settings page under Settings > No Gutenberg, built around a single master switch. Leave "Disable Gutenberg completely" checked and the plugin works exactly as it always did, with no setup. Uncheck it and you choose what to disable. Every checkbox means the same thing, so no setting ever undoes another one
* New: Rules to disable the block editor by post type, by user role, by page template and by individual entry IDs. Rules are independent: anything that matches gets the Classic Editor
* New: Optional editor switching, enabled per post type next to its rule. Adds "Edit (Classic)" and "Edit (Blocks)" links to the entries of that post type and a switch inside both editors, and remembers the choice for each entry. The choice wins over every rule, in both directions, so one page can stay classic while its post type keeps blocks, or the other way around
* New: Status panel that reports what is really happening on your site: block patterns blocked, weight saved on every page, Global Styles, widgets, Site Editor, active theme, and how much of your published content is actually built with blocks
* New: Site wide pieces can be turned off one by one once the master switch is off: classic widgets, block patterns, frontend block assets, theme.json and Global Styles, Site Editor blocking and block theme notices
* New: Configuration can be fixed from wp-config.php with NO_GUTENBERG_OPTIONS, and the settings screen locked as read only with NO_GUTENBERG_LOCK_SETTINGS or hidden with NO_GUTENBERG_HIDE_SETTINGS, for agencies and managed sites
* New: Cleanup on uninstall. Plugin settings, dismissed notice user meta, and notice transients are removed when the plugin is deleted
* Improved: WooCommerce is no longer an all or nothing switch. Its product editor now follows the same rules as any other post type, so leaving blocks on for products really leaves them on, and its block styles are removed along with the rest of the frontend block assets
* Improved: The block theme warning is now shown only on the Dashboard and Appearance screens, and its dismiss script is properly enqueued instead of printed inline
* Improved: Site Editor menu removal now covers the Patterns menu slug introduced in WordPress 6.8 and the Fonts screen introduced in WordPress 7.0
* Improved: Minimum required WordPress version raised to 6.1, which is what the plugin features actually need
* Fix: Block patterns are now really removed. The previous cleanup hooked itself on an init priority that had already run, so core and theme patterns kept being registered, and it relied on remove_all_actions(), which would have wiped other plugins' callbacks had it ever run
* Fix: The Global Styles inline CSS and the per-block inline styles are now really removed from the frontend. Dequeuing the style handles was not enough, because Global Styles are printed inline
* Fix: The WooCommerce block styles are now really removed too. They are enqueued while the blocks render, long after the point where the plugin used to look for them, so they were always printed in the footer regardless of the setting
* Fix: Theme support removals now run after the active theme registers them, so disabling the FSE features takes effect on every theme
* Fix: Removed dead legacy code (Gutenberg feature plugin hooks, pre-5.0 branch, duplicated filters, a no-op editor setting) and unified duplicated WooCommerce asset removals

For older changelog entries, please check the [changelog.txt](https://plugins.svn.wordpress.org/no-gutenberg/trunk/changelog.txt) file

== Upgrade Notice ==

= 2.2.0 =
New settings page: leave the master switch on and nothing changes, turn it off to disable the block editor by post type, role, template or entry, or let entries switch editors. Adds a status panel and wp-config constants. Block patterns and Global Styles CSS are really removed now. Needs WP 6.1.

== Support ==

= Need private support or custom development? =
Do you need one-on-one help, priority troubleshooting, or a custom feature, integration, or tweak built specifically for your site? I offer private support and custom development. Just [contact me](mailto:no-gutenberg@ayudawp.com) and tell me what you need.

= Need help or have suggestions? =
* [Official website](https://servicios.ayudawp.com/)
* [WordPress support forum](https://wordpress.org/support/plugin/no-gutenberg/)
* [YouTube channel](https://www.youtube.com/AyudaWordPressES)
* [Documentation and tutorials](https://ayudawp.com/)

**Love the plugin?** Please leave us a 5-star review and help spread the word!

== About AyudaWP ==

We are specialists in WordPress security, SEO, and performance optimization plugins. We create tools that solve real problems for WordPress site owners while maintaining the highest coding standards and accessibility requirements.

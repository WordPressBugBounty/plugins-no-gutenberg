=== No Gutenberg? - Choose Where to Use the Block Editor or the Classic Editor ===
Contributors: fernandot, ayudawp
Tags: gutenberg, classic editor, FSE, blocks, woocommerce
Requires at least: 6.1
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.4.0
License: GPLv2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Decide where the block editor stays and where the Classic Editor comes back. It also removes Global Styles, patterns, block widgets and block CSS.

== Description ==

The most configurable way to leave the block editor behind. Activate it and the block editor is gone, along with Global Styles, patterns, block widgets and the Site Editor. From there you decide the scope: everywhere, or only for the post types, roles, templates and entries you choose. If the site already has content built with blocks, the block CSS it needs stays in place and the plugin tells you so, instead of leaving that content unstyled.

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
* The block library CSS and JavaScript that every page loads
* The Global Styles inline CSS, which dequeuing the handle never reaches
* The block assets of any other plugin that registers blocks, on pages where no block appears
* The block editor assets in the admin, wherever the Classic Editor is the one opening

= WooCommerce Integration =
* The block based product editor, which follows the same rules as any other post type
* The WooCommerce block styles and scripts, removed along with the rest of the block assets
* Nothing else: leave blocks on for products and the block product editor keeps working

= Admin Experience =
* Removes the welcome panel that promotes the block editor
* Removes the Patterns submenu from the Appearance menu (WP 6.5+)
* Removes the Fonts submenu from the Appearance menu (WP 7.0+)
* Blocks access to Site Editor pages
* Shows activation success notice
* Warns you when a block theme is active
* Adds settings and support links to plugin actions

**A default that asks nothing, and a settings page for everything else** - Activating the plugin disables the block editor and every block feature across the site, which is what most people install it for. The rest of this page is about wanting less than that.

**Full control when you need it** - The settings page lives under Settings > No Gutenberg?, built around one master switch. Leave it on and the whole site keeps the complete disable. Turn it off and you decide where the block editor goes away: by post type, by user role, by page template or by individual entry. The site wide pieces (classic widgets, block patterns, frontend block assets, theme.json and the Site Editor) are then yours to keep or remove one by one. Every checkbox means the same thing, so no setting ever undoes another one.

**Switch editors entry by entry, where it makes sense** - Editor switching is chosen per post type, right next to the rule that disables the block editor. Enable it for pages, for instance, and every page gets "Edit (Classic)" and "Edit (Blocks)" links in the list plus a switch inside both editors, while your posts stay untouched. Each entry remembers the editor chosen for it, and that choice beats the rules in both directions: one landing page can stay on the Classic Editor while the rest keep blocks, or the other way around.

**It tells you what it is doing** - The settings screen opens with a status panel for your particular site: how many block patterns are being blocked, how much weight is off every page, whether your theme is a block theme, and how much of your published content is really built with blocks. That last number is the one that should decide whether you strip the block styles from the frontend or not, and no other plugin tells you.

**Built for agencies and managed sites** - The whole configuration can be fixed from wp-config.php, and the settings screen locked as read only or hidden altogether, so a site you deliver keeps the setup you left behind.

**A whole network configured in one file** - This is where the plugin stands alone. Every single setting it has, and not just which editor opens, can be written in wp-config.php, the same file every site of a multisite network reads. A network of a hundred sites is set up once, all of them with the same rules, all of them with the same block CSS gone, and none of them able to drift away from it. The plugin can also be network activated and left alone, because settings are stored per site, so a network can equally let each site decide for itself. Same mechanism as the locking above, and this is how it is written.

= One configuration for a whole network, or for a site you deliver =

Everything the settings screen does can also be written in wp-config.php, where nobody changes it by accident, and what you write there wins over whatever is stored in the database. It is the same file for every site of a multisite network, so it is also how you set up a network without repeating the configuration site by site. Add the lines you need above the comment that says "That's all, stop editing! Happy publishing."

To disable Gutenberg everywhere and leave no settings screen for anyone to touch, which is the usual setup both for a network and for a site you hand over to a client:

`
define( 'NO_GUTENBERG_OPTIONS', array( 'complete' => true ) );
define( 'NO_GUTENBERG_HIDE_SETTINGS', true );
`

complete covers the block editor, everywhere and for everyone. It does not decide the site wide items, which answer for themselves: a site that is already serving the block styles because it has content built with blocks keeps serving them. Name them in the array if you want them off whatever the site has.

Drop that second line and the settings page is still there, showing the configuration the site is really running, but read only. And if all you want is to lock the screen, leaving the configuration as each site has it stored:

`
define( 'NO_GUTENBERG_LOCK_SETTINGS', true );
`

NO_GUTENBERG_OPTIONS takes any of the settings on the screen, so a selective setup is written the same way:

`
define(
	'NO_GUTENBERG_OPTIONS',
	array(
		'complete'           => false,
		'disable_post_types' => array( 'post', 'page' ),
		'disable_roles'      => array( 'editor', 'author' ),
		'frontend_css'       => false,
	)
);
`

The keys are the settings on the screen. complete, widgets, patterns, frontend_css, theme_json, site_editor and fse_notices take true or false, while disable_post_types, disable_roles, disable_templates and switch_post_types take an array of slugs, and disable_ids an array of entry IDs. Any key you leave out keeps the value stored on the site, and on a site with block content frontend_css and theme_json are stored as false, so write them here if you really want them on. Note that defining NO_GUTENBERG_OPTIONS already turns the whole screen read only, so NO_GUTENBERG_LOCK_SETTINGS is not needed on top of it.

It fits sites that:
- Work better in the Classic Editor and want it back
- Run classic themes and plugins that were never built around blocks
- Are paying for block CSS and JavaScript on pages with no blocks in them
- Need the Classic Editor for everyone except a few post types, roles or people
- Are a multisite network that wants the same editing experience on every site

== Installation ==

1. Go to your WP Dashboard > Plugins and search for 'no gutenberg' or…
2. Download the plugin from WP repository
3. Upload the 'no-gutenberg' folder to the '/wp-content/plugins/' directory
4. Activate the plugin through the 'Plugins' menu in WordPress
5. That is it. The block editor is disabled across the site and the Classic Editor is back

On a multisite network you can also Network Activate it, so it runs on every site of the network at once. Settings stay per site, and the description explains how to configure the whole network from wp-config.php.

== Frequently Asked Questions ==

= Do I have to configure anything? =

No. Activating the plugin disables everything across the site. The settings page under Settings > No Gutenberg? is there only if you want to bring some part of the block editor back.

= Can I disable Gutenberg only for some content or some people? =

Yes. Go to Settings > No Gutenberg?, uncheck "Disable Gutenberg completely", and then choose where the block editor should go away: post types, user roles, page templates or individual entry IDs. Rules are independent, so anything that matches gets the Classic Editor and everything else keeps working as usual.

= Can I disable just one part of Gutenberg? =

Yes. Once the master switch is off, the site wide pieces are independent checkboxes, so you can, for example, restore the classic Widgets screen and remove the block patterns while leaving the editor itself alone.

= If I only disable the editor for some content, what happens to the block styles? =

That is exactly why the plugin unchecks the frontend block assets and theme.json options for you when you switch to a partial setup: the content that still uses blocks would lose its styles on the frontend. The status panel tells you how many of your published entries are built with blocks, so you can decide with real numbers.

= Can I lock the configuration so a client cannot change it? =

Yes, from wp-config.php. `define( 'NO_GUTENBERG_LOCK_SETTINGS', true );` leaves Settings > No Gutenberg? visible but read only, and `define( 'NO_GUTENBERG_HIDE_SETTINGS', true );` takes it out of the menu. To fix the configuration itself, and not only lock the screen, there is NO_GUTENBERG_OPTIONS. The description has the three of them together, with the settings you can fix and the details worth knowing.

= Does it work on a multisite network? =

Yes, and it is what the plugin is best at. Activate it site by site, or Network Activate it and it runs on every site of the network. Settings are stored per site, so each site has its own Settings > No Gutenberg? and each site administrator manages their own.

For the whole network at once, the place is wp-config.php, the same file every site reads: what you define there reaches all of them, covers every setting the plugin has and wins over whatever each site has stored. There is no network settings screen, because that file does the job for the entire network in a couple of lines. They are in the description, under "One configuration for a whole network, or for a site you deliver".

= Can I open one entry with the other editor? =

Yes. In the post types table there is a second column, "Let each entry switch": check it for the post types where it makes sense. Those entries then get "Edit (Classic)" and "Edit (Blocks)" links in the list and a switch inside both editors, and each one remembers the editor you chose for it.

= Does this work with WooCommerce? =

Yes. Products behave like any other post type: with the master switch on, or with Products checked in the rules, the classic product editor is forced and the WooCommerce block assets are removed from the frontend. If you leave blocks on for products, the block based product editor keeps working, without a separate setting contradicting it.

= Will this break my existing content? =

No, and the plugin goes out of its way to keep it that way. It never rewrites a single entry, and an entry that is already built with blocks keeps the block editor whatever the rules say, so nothing you set here can send it to an editor that would reflow its markup. The entries list labels those entries and can be narrowed down to them, so you can see exactly what you have.

There is one visible change on the frontend: with the frontend block assets removed, content built with blocks loses the block styles. The status panel tells you how many entries are affected before you decide.

= I want the Classic Editor on everything, block content included. Can I? =

Yes. Uncheck the content protection under the master switch, on the settings page. From that point the rules apply to every entry, and the plugin warns you inside the Classic Editor whenever you open one that is built with blocks, because saving it there can break its blocks.

= What happens if I deactivate the plugin? =

Everything comes back immediately: the block editor, Global Styles, the patterns and the block assets. Your content is exactly as it was, because the plugin never rewrote any of it.

= Is this compatible with my theme and my plugins? =

With classic themes, and with plugins that do not depend on blocks, yes: it removes block features instead of fighting them for control. Block themes are the exception, and there is a question about them right below. A plugin that registers blocks of its own keeps working, but those blocks lose their styles on the frontend if you remove the block assets, which is what the status panel is there to warn you about.

= Can I use it with a block theme? =

You can, but block themes are built around the very features this plugin removes, so their Site Editor and template editing will not work while it is active. The plugin warns you when it detects a block theme. If you need that theme, switch off the Site Editor and theme.json modules on the settings page, or use a classic theme instead.

= Why choose this over other similar plugins? =

Because it does not stop at the editor. Others disable the block editor and leave the rest running: this one also removes the FSE Global Styles, the block widgets, the patterns, the WooCommerce blocks and the block CSS and JavaScript that every page of your site is loading. It also tells you what it is doing, with a status panel measured on your own site instead of a list of promises. And on a multisite network it is configured for every site at once from a single file, all of it, not only which editor opens.

== Screenshots ==

1. WordPress posts page with Classic Editor restored
2. Classic Widgets interface instead of block widgets
3. Activation success notice
4. FSE theme activation warning
5. Settings page

== Changelog ==

= 2.4.0 =
If this plugin was taking the block styles off a site that has content built with blocks, updating puts them back: that was never a choice, the old code forced it. A new install does not make that choice either, and every setting is now on screen.

* New: A Classic Editor configuration found on the site can be imported here. The page says what was found and then lists, line by line, what importing it would write and what it would clear, with a button to keep the settings you already have. It is offered once, because Classic Editor deletes its own settings when it is removed
* New: A warning when Classic Editor is running at the same time as this plugin. Both of them choose which editor opens, through the same WordPress filters, and the one that runs last wins
* Improved: The whole configuration is on screen at all times. The master switch used to hide the rest of the page, which read as there being nothing left to decide while two of the hidden settings could take the styles off content that was already published
* Improved: The site wide items are decided on their own, with the master switch checked or not. A complete disable used to force every one of them on, so the two that reach the frontend could not be turned off on a site with block content, and saving the page put them back
* Improved: A warning next to the site wide items whenever the block styles are being removed from a site that needs them, with the number of entries it affects
* Improved: The settings page now confirms when settings are saved, which it never did
* Improved: The settings page was laid out again. Every box on it ends on the same pixel, and what the plugin is doing to the site is a panel that belongs there, instead of looking like a notice waiting to be dismissed
* Fix: Installing on a site that already has content built with blocks no longer removes the block CSS and neutralizes theme.json. Those entries kept the block editor, so their markup was safe, but visitors saw them with no styles, no layout and no spacing presets. Both options now start unchecked on a site with block content or a block theme, and the page says why
* Fix: The activation notice said that block content would keep the block editor so nothing would break its markup, while the same configuration had just taken its styles off the frontend. It now also says what was left in place, and why
* Fix: The status panel reported the frontend block assets and theme.json as removed whenever the master switch was checked, whatever the real setting said
* Fix: Activating the plugin for a whole network configured only the site the network administrator was on. Every other site of that network was treated as one coming from before 2.3.0, so it ended up with the protection of existing block content off and the block styles removed from the frontend. Each of them now gets the defaults of its own site the first time its admin is opened. This is about networks activated from this version on: on a network that was already activated, every site keeps what it has
* Fix: Updating gives the block styles back to a site that has content built with blocks and was serving it with none. Until this version the master switch forced the frontend block assets and the theme.json item on while it was checked, so on those sites nobody chose that: the stored value could not even be read back, and the settings page showed a state that was not the real one. A site that had the master switch unchecked did choose, so its two values are left exactly as they are and the settings page warns about them instead
* Fix: A site with no configuration of its own, which is what a site of a network activation has until somebody opens its admin there, no longer removes the block styles while it waits. Nothing has looked at whether that site needs them yet, and removing them is the half that cannot be undone by reading the page
* Fix: Uninstall also removes the stored Classic Editor detection

For older changelog entries, please check the [changelog.txt](https://plugins.svn.wordpress.org/no-gutenberg/trunk/changelog.txt) file

== Upgrade Notice ==

= 2.4.0 =
If this plugin was taking the block styles off a site that has content built with blocks, updating puts them back: that was never a choice, the old code forced it. A new install does not make that choice either, and every setting is now on screen.

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

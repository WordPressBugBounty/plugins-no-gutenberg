/**
 * Adds a "Switch to the Classic Editor" item to the block editor options menu.
 *
 * Uses wp.editor when available and falls back to the older wp.editPost, which
 * is where PluginMoreMenuItem lived before WordPress 6.6.
 */
( function ( wp, settings ) {
	if ( ! wp || ! wp.plugins || ! wp.element || ! settings || ! settings.url ) {
		return;
	}

	var namespace = wp.editor || wp.editPost;

	if ( ! namespace || ! namespace.PluginMoreMenuItem ) {
		return;
	}

	var createElement = wp.element.createElement;

	wp.plugins.registerPlugin( 'no-gutenberg-editor-switch', {
		render: function () {
			return createElement(
				namespace.PluginMoreMenuItem,
				{
					icon: 'edit',
					onClick: function () {
						window.location.href = settings.url;
					},
				},
				settings.label
			);
		},
	} );
} )( window.wp, window.ayudawpNoGutenbergSwitch );

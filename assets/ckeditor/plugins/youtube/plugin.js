/**
 * YouTube embed for CKEditor 4.2.
 *
 * Replaces the retired Flash button: takes any YouTube link (watch, youtu.be,
 * embed, shorts, live) or a bare video id and inserts a plain <iframe> embed,
 * optionally wrapped in a responsive 16:9 box.
 *
 * Inside the editor the embed is shown as a fake object carrying the video's
 * own thumbnail, so the author sees the video rather than the generic IFRAME
 * placeholder the bundled iframe plugin would produce. The real markup is kept
 * in data-cke-realelement and restored by the fakeobjects plugin on save.
 *
 * Uses 4.2 APIs only (button + dialog + fakeobjects), so the editor is not
 * upgraded.
 */
( function() {
	'use strict';

	var FAKE_CLASS = 'cke_youtube',
		FAKE_TYPE = 'youtube',
		// Fully percent-encoded, quotes included: this goes into an unquoted
		// url() in a style attribute, and an unquoted URL token may contain
		// neither whitespace nor quotes -- one stray quote invalidates the
		// whole background-image declaration, which is why the preview came
		// out as a plain black box.
		BADGE = "data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20viewBox=%270%200%2068%2048%27%3E%3Cpath%20d=%27M66.5%207.7a8.6%208.6%200%200%200-6-6C55%200%2034%200%2034%200S13%200%207.5%201.7a8.6%208.6%200%200%200-6%206A90%2090%200%200%200%200%2024a90%2090%200%200%200%201.5%2016.3%208.6%208.6%200%200%200%206%206C13%2048%2034%2048%2034%2048s21%200%2026.5-1.7a8.6%208.6%200%200%200%206-6A90%2090%200%200%200%2068%2024a90%2090%200%200%200-1.5-16.3z%27%20fill=%27%23e02f2f%27/%3E%3Cpath%20d=%27M27%2034l18-10-18-10z%27%20fill=%27%23fff%27/%3E%3C/svg%3E";

	CKEDITOR.plugins.add( 'youtube', {
		requires: 'dialog,fakeobjects',

		init: function( editor ) {
			var label = 'Insert YouTube video';

			CKEDITOR.dialog.add( 'youtube', this.path + 'dialogs/youtube.js' );

			editor.addCommand( 'youtube', new CKEDITOR.dialogCommand( 'youtube' ) );

			editor.ui.addButton( 'Youtube', {
				label: label,
				title: label,
				command: 'youtube',
				toolbar: 'insert,50'
			} );

			// Double-clicking the preview reopens the dialog on that embed.
			editor.on( 'doubleclick', function( ev ) {
				if ( api.isFake( ev.data.element ) )
					ev.data.dialog = 'youtube';
			} );
		},

		afterInit: function( editor ) {
			var filter = editor.dataProcessor && editor.dataProcessor.dataFilter;

			if ( !filter )
				return;

			wrapperRule.editor = editor;

			// Priority 5 so this runs before the bundled iframe plugin (10)
			// turns the <iframe> into its own generic placeholder. Replacing
			// the wrapper wholesale also keeps the nested iframe away from it.
			filter.addRules( {
				elements: {
					// span is what build() emits now; div covers embeds saved by the
					// first version of this plugin.
					span: wrapperRule,
					div: wrapperRule,

					iframe: function( element ) {
						var video = api.fromEmbed( element.attributes.src );

						if ( !video )
							return null;

						return fakeParserElement( editor, element, video, {
							responsive: false,
							width: parseInt( element.attributes.width, 10 ) || 560,
							height: parseInt( element.attributes.height, 10 ) || 315
						} );
					}
				}
			// NOTE: in 4.2 the second argument is the priority number itself,
			// not an options object -- the iframe plugin's rule sits at the
			// default 10, so 5 puts this one first.
			}, 5 );
		}
	} );

	function wrapperRule( element ) {
		if ( !/(^|\s)yt-embed(\s|$)/.test( element.attributes[ 'class' ] || '' ) )
			return null;

		var iframe = findIframe( element ),
			video = iframe && api.fromEmbed( iframe.attributes.src );

		if ( !video )
			return null;

		return fakeParserElement( wrapperRule.editor, element, video, { responsive: true } );
	}

	function findIframe( element ) {
		var children = element.children || [];

		for ( var i = 0; i < children.length; i++ ) {
			var child = children[ i ];

			if ( child.type != CKEDITOR.NODE_ELEMENT )
				continue;

			if ( child.name == 'iframe' )
				return child;

			var nested = findIframe( child );

			if ( nested )
				return nested;
		}

		return null;
	}

	function fakeParserElement( editor, element, video, options ) {
		var fake = editor.createFakeParserElement( element, FAKE_CLASS, FAKE_TYPE, false );

		fake.attributes.style = api.previewStyle( video, options );
		fake.attributes.alt = fake.attributes.title = api.previewTitle;

		return fake;
	}

	var api = CKEDITOR.plugins.youtube = {

		previewTitle: 'YouTube video - double-click to edit',

		/**
		 * Pulls the video id out of anything a user is likely to paste.
		 * Returns null when nothing usable is found.
		 */
		parse: function( input ) {
			input = CKEDITOR.tools.trim( String( input || '' ) );

			if ( !input )
				return null;

			var id = null,
				start = 0,
				patterns = [
					/(?:youtube\.com|youtube-nocookie\.com)\/watch\?(?:.*&)?v=([\w-]{11})/i,
					/youtu\.be\/([\w-]{11})/i,
					/(?:youtube\.com|youtube-nocookie\.com)\/embed\/([\w-]{11})/i,
					/(?:youtube\.com|youtube-nocookie\.com)\/(?:shorts|live|v)\/([\w-]{11})/i,
					/^([\w-]{11})$/
				];

			for ( var i = 0; i < patterns.length; i++ ) {
				var match = input.match( patterns[ i ] );

				if ( match ) {
					id = match[ 1 ];
					break;
				}
			}

			if ( !id )
				return null;

			// Start time, either ?t=90 / ?t=1m30s (share links) or ?start=90.
			var time = input.match( /[?&](?:t|start)=([\dhms]+)/i );

			if ( time ) {
				var value = time[ 1 ];

				if ( /^\d+$/.test( value ) ) {
					start = parseInt( value, 10 );
				} else {
					var parts = value.match( /(\d+h)?(\d+m)?(\d+s)?/i );

					if ( parts ) {
						start = ( parseInt( parts[ 1 ], 10 ) || 0 ) * 3600 +
							( parseInt( parts[ 2 ], 10 ) || 0 ) * 60 +
							( parseInt( parts[ 3 ], 10 ) || 0 );
					}
				}
			}

			return { id: id, start: start };
		},

		/**
		 * Reads back an embed URL produced by build(), so an existing embed can
		 * be reopened in the dialog with its options intact.
		 */
		fromEmbed: function( src ) {
			src = String( src || '' );

			var match = src.match( /(?:youtube\.com|youtube-nocookie\.com)\/embed\/([\w-]{11})/i );

			if ( !match )
				return null;

			var start = src.match( /[?&](?:amp;)?start=(\d+)/ );

			return {
				id: match[ 1 ],
				start: start ? parseInt( start[ 1 ], 10 ) : 0,
				privacy: /youtube-nocookie\.com/i.test( src ),
				related: !/[?&](?:amp;)?rel=0(?:&|$)/.test( src )
			};
		},

		isFake: function( element ) {
			return !!element && element.type == CKEDITOR.NODE_ELEMENT && element.is( 'img' ) &&
				element.data( 'cke-real-element-type' ) == FAKE_TYPE;
		},

		thumbnail: function( id ) {
			return 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';
		},

		/**
		 * Inline style for the in-editor preview: the video thumbnail with the
		 * play badge on top.
		 */
		previewStyle: function( video, options ) {
			var width = options.responsive ? 480 : ( options.width || 560 ),
				height = options.responsive ? 270 : ( options.height || 315 );

			return 'background-image:url(' + BADGE + '),url(' + api.thumbnail( video.id ) + ');' +
				'background-position:center center,center center;' +
				'background-repeat:no-repeat,no-repeat;' +
				'background-size:64px auto,cover;' +
				'background-color:#111827;' +
				'width:' + width + 'px;height:' + height + 'px;';
		},

		/**
		 * Builds the embed markup. Dimensions are only used when not responsive.
		 */
		build: function( video, options ) {
			var host = options.privacy ? 'www.youtube-nocookie.com' : 'www.youtube.com',
				query = [];

			if ( video.start )
				query.push( 'start=' + video.start );

			if ( !options.related )
				query.push( 'rel=0' );

			// &amp; so the attribute stays valid HTML once serialised.
			var src = 'https://' + host + '/embed/' + video.id + ( query.length ? '?' + query.join( '&amp;' ) : '' ),
				attributes = ' src="' + src + '" title="YouTube video player" frameborder="0"' +
					' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"' +
					' referrerpolicy="strict-origin-when-cross-origin"' +
					' allowfullscreen';

			if ( options.responsive ) {
				// A <span style="display:block"> rather than a <div>: the editor
				// stands the embed in with an inline <img>, so a block element
				// here comes back inside the paragraph as invalid <p><div>.
				return '<span class="yt-embed" style="display:block;position:relative;width:100%;max-width:100%;padding-bottom:56.25%;height:0;overflow:hidden">' +
					'<iframe style="position:absolute;top:0;left:0;width:100%;height:100%"' + attributes + '></iframe>' +
					'</span>';
			}

			return '<iframe width="' + options.width + '" height="' + options.height + '"' + attributes + '></iframe>';
		},

		/**
		 * The preview element that stands in for the embed while editing.
		 */
		createFake: function( editor, video, options ) {
			var real = CKEDITOR.dom.element.createFromHtml( api.build( video, options ), editor.document ),
				fake = editor.createFakeElement( real, FAKE_CLASS, FAKE_TYPE, false );

			fake.setAttribute( 'style', api.previewStyle( video, options ) );
			fake.setAttribute( 'alt', api.previewTitle );
			fake.setAttribute( 'title', api.previewTitle );

			return fake;
		}
	};
} )();

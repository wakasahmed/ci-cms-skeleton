/**
 * Emoji picker for CKEditor 4.2.
 *
 * The bundled "smiley" plugin inserts GIF images from the editor folder, which
 * is both dated and fragile once the content is rendered outside the admin.
 * This plugin replaces it with a drop-down panel of plain Unicode emoji that
 * are inserted as text, so nothing extra has to be hosted or resized.
 *
 * Written against 4.2 APIs only (panelbutton + floatpanel, both bundled in
 * ckeditor.js) so the editor does not need to be upgraded.
 */
( function() {
	'use strict';

	var groups = [
		{
			label: 'Smileys & people',
			items: '😀 😃 😄 😁 😆 😅 😂 🙂 🙃 😉 😊 😇 😍 😘 😗 😋 😜 🤪 🤨 🧐 🤓 😎 🥳 😏 😒 😞 😔 😟 😕 🙁 😣 😖 😫 😩 🥺 😢 😭 😤 😠 😡 🤯 😳 😱 😨 😰 😥 😓 🤗 🤔 🤭 🤫 😐 😑 😬 🙄 😯 😴 🤤 😪 😵 🤐 🤢 🤮 🤧 😷 🤒 🤕 🤑 😈 💀 👻 👽 🤖 🎃'
		},
		{
			label: 'Gestures & body',
			items: '👍 👎 👌 ✌️ 🤞 🤟 🤘 🤙 👈 👉 👆 👇 ☝️ ✋ 🤚 🖐️ 🖖 👋 🤝 🙏 ✍️ 💪 🦵 👀 👁️ 👄 👶 🧒 👦 👧 🧑 👨 👩 🧓 👴 👵 🙋 🙆 🙅 💁 🤦 🤷 🚶 🏃'
		},
		{
			label: 'Hearts & symbols',
			items: '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❣️ 💕 💞 💓 💗 💖 💘 💝 ⭐ 🌟 ✨ ⚡ 🔥 💥 💫 💦 💨 ✅ ❌ ❗ ❓ ⚠️ 🚫 ♻️ 🔴 🟠 🟡 🟢 🔵 🟣 ⚫ ⚪ 🔺 🔻 🔔 📌 📍 🏷️'
		},
		{
			label: 'Travel & places',
			items: '🕋 🕌 🕍 ⛪ 🛕 🏛️ 🏕️ 🏖️ 🏜️ 🏝️ 🏔️ ⛰️ 🌋 🗺️ 🧭 🧳 🛫 🛬 ✈️ 🚁 🚌 🚐 🚗 🚕 🚙 🚎 🛺 🚉 🚆 🛳️ ⛴️ ⚓ 🏨 🏩 🏬 🏦 🏤 🌇 🌆 🌃 🌉 🗓️ 🕐 ⌛'
		},
		{
			label: 'Nature & food',
			items: '☀️ 🌤️ ⛅ 🌧️ ⛈️ ❄️ 🌈 🌙 🌍 🌱 🌴 🌵 🌷 🌸 🌹 🌺 🌻 🍀 🍁 🐪 🐫 🦅 🕊️ 🐑 🍇 🍉 🍊 🍋 🍌 🍎 🍑 🍓 🥝 🥑 🍅 🥕 🍞 🧀 🍗 🥗 🍚 🍰 ☕ 🍵 🥤 💧'
		},
		{
			label: 'Objects & activity',
			items: '📱 💻 🖥️ ⌨️ 🖱️ 🖨️ 📷 📹 🎥 🎬 🎧 🎵 🎤 📞 ☎️ 📧 📨 📩 📤 📥 📦 📝 📄 📃 📑 📊 📈 📉 📅 📆 🗂️ 📁 📂 🔍 🔎 🔒 🔓 🔑 🛠️ ⚙️ 🧾 💰 💳 🎁 🎉 🎊 🏆 🥇 ⚽ 🏀 🎯'
		}
	];

	CKEDITOR.plugins.add( 'emoji', {
		requires: 'panelbutton,floatpanel',

		init: function( editor ) {
			var pluginPath = this.path,
				label = 'Insert emoji';

			// 4.2 has no ui.addPanelButton() shortcut -- the bundled colorbutton
			// registers its panel button through ui.add() the same way.
			editor.ui.add( 'Emoji', CKEDITOR.UI_PANELBUTTON, {
				label: label,
				title: label,
				modes: { wysiwyg: 1 },
				editorFocus: false,
				toolbar: 'insert,60',
				// Adds "cke_emoji_panel" to the panel element in the host
				// document; sized there, see skins/moono/admin-theme.css.
				className: 'cke_emoji',

				panel: {
					css: [ CKEDITOR.skin.getPath( 'editor' ), CKEDITOR.getUrl( pluginPath + 'panel.css' ) ],
					attributes: { role: 'listbox', 'aria-label': label }
				},

				onBlock: function( panel, block ) {
					// Deliberately not autoSize: it measures the block element,
					// which is a full-width block inside the panel iframe, and
					// blows the panel up to the width of the page. The panel is
					// given a fixed size in CSS instead.
					block.element.addClass( 'cke_emoji_block' );
					block.element.setHtml( render() );
					block.element.getDocument().getBody().setStyle( 'overflow', 'hidden' );

					// Delegated listener rather than inline onclick handlers: the
					// panel lives in its own iframe, so nothing in there should
					// have to reach back out to the CKEDITOR namespace.
					block.element.on( 'click', function( ev ) {
						var item = closestItem( ev.data.getTarget() );

						if ( !item )
							return;

						ev.data.preventDefault();

						var emoji = item.getAttribute( 'data-emoji' );

						panel.hide();
						editor.focus();
						editor.fire( 'saveSnapshot' );
						editor.insertText( emoji );
						editor.fire( 'saveSnapshot' );
					} );
				}
			} );
		}
	} );

	function closestItem( node ) {
		while ( node && node.type == CKEDITOR.NODE_ELEMENT ) {
			if ( node.hasClass( 'cke_emoji_item' ) )
				return node;

			node = node.getParent();
		}

		return null;
	}

	function render() {
		var out = [ '<div class="cke_emoji_body">' ];

		for ( var i = 0; i < groups.length; i++ ) {
			var items = groups[ i ].items.split( ' ' );

			out.push( '<div class="cke_emoji_grouptitle">', encode( groups[ i ].label ), '</div>' );
			out.push( '<div class="cke_emoji_grid">' );

			for ( var j = 0; j < items.length; j++ ) {
				if ( !items[ j ] )
					continue;

				out.push(
					'<a class="cke_emoji_item" href="javascript:void(0)" role="option"',
					' data-emoji="', encode( items[ j ] ), '"',
					' title="', encode( items[ j ] ), '">',
					items[ j ],
					'</a>'
				);
			}

			out.push( '</div>' );
		}

		out.push( '</div>' );

		return out.join( '' );
	}

	function encode( text ) {
		return CKEDITOR.tools.htmlEncodeAttr( text );
	}
} )();

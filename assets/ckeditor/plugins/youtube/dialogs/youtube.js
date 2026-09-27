/**
 * Dialog for the youtube plugin. Loaded on demand by CKEDITOR.dialog.add().
 */
CKEDITOR.dialog.add( 'youtube', function( editor ) {
	'use strict';

	var api = CKEDITOR.plugins.youtube,
		invalid = 'Enter a YouTube link (watch, youtu.be, embed or shorts) or an 11-character video ID.';

	function field( dialog, id ) {
		return dialog.getContentElement( 'info', id );
	}

	// Reads an existing preview back into the form fields.
	function restore( dialog, fake ) {
		var real = editor.restoreRealElement( fake );

		if ( !real )
			return;

		// No findOne() in 4.2. The wrapper is a span now (a div in embeds saved
		// by the first version of the plugin).
		var responsive = real.hasClass( 'yt-embed' ),
			iframe = responsive ? real.getElementsByTag( 'iframe' ).getItem( 0 ) : real,
			video = iframe && api.fromEmbed( iframe.getAttribute( 'src' ) );

		if ( !video )
			return;

		field( dialog, 'url' ).setValue( 'https://youtu.be/' + video.id + ( video.start ? '?t=' + video.start : '' ) );
		field( dialog, 'responsive' ).setValue( responsive );
		field( dialog, 'privacy' ).setValue( video.privacy );
		field( dialog, 'related' ).setValue( video.related );

		if ( !responsive ) {
			field( dialog, 'width' ).setValue( real.getAttribute( 'width' ) || '560' );
			field( dialog, 'height' ).setValue( real.getAttribute( 'height' ) || '315' );
		}
	}

	// Fixed dimensions are meaningless while the embed is responsive.
	function toggleSize( dialog, responsive ) {
		var width = field( dialog, 'width' ),
			height = field( dialog, 'height' );

		if ( !width || !height )
			return;

		if ( responsive ) {
			width.disable();
			height.disable();
		} else {
			width.enable();
			height.enable();
		}
	}

	return {
		title: 'YouTube video',
		minWidth: 480,
		minHeight: 200,

		contents: [ {
			id: 'info',
			label: 'General',
			elements: [
				{
					type: 'text',
					id: 'url',
					label: 'Video URL or ID',
					required: true,
					validate: function() {
						return api.parse( this.getValue() ) ? true : invalid;
					}
				},
				{
					type: 'checkbox',
					id: 'responsive',
					label: 'Responsive (fills the available width, 16:9)',
					default: true,
					onChange: function() {
						toggleSize( this.getDialog(), this.getValue() );
					}
				},
				{
					type: 'hbox',
					widths: [ '50%', '50%' ],
					children: [
						{
							type: 'text',
							id: 'width',
							label: 'Width',
							default: '560',
							validate: CKEDITOR.dialog.validate.integer( 'Width must be a number.' )
						},
						{
							type: 'text',
							id: 'height',
							label: 'Height',
							default: '315',
							validate: CKEDITOR.dialog.validate.integer( 'Height must be a number.' )
						}
					]
				},
				{
					type: 'checkbox',
					id: 'privacy',
					label: 'Privacy-enhanced mode (youtube-nocookie.com)',
					default: true
				},
				{
					type: 'checkbox',
					id: 'related',
					label: 'Show related videos when the video ends',
					default: false
				}
			]
		} ],

		onShow: function() {
			// Editing an existing embed: the selection is the preview element
			// standing in for it, so read the options back out of it.
			var selected = editor.getSelection().getSelectedElement();

			this.fakeElement = api.isFake( selected ) ? selected : null;

			if ( this.fakeElement )
				restore( this, this.fakeElement );

			toggleSize( this, field( this, 'responsive' ).getValue() );
		},

		onOk: function() {
			var video = api.parse( field( this, 'url' ).getValue() );

			if ( !video )
				return false;

			var fake = api.createFake( editor, video, {
				responsive: field( this, 'responsive' ).getValue(),
				privacy: field( this, 'privacy' ).getValue(),
				related: field( this, 'related' ).getValue(),
				width: parseInt( field( this, 'width' ).getValue(), 10 ) || 560,
				height: parseInt( field( this, 'height' ).getValue(), 10 ) || 315
			} );

			if ( this.fakeElement ) {
				fake.replace( this.fakeElement );
				editor.getSelection().selectElement( fake );
			} else {
				editor.insertElement( fake );
			}
		}
	};
} );

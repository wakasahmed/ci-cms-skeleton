/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	// NOTE: this used to be two assignments, the second overwriting the first,
	// so 'mediaembed' has never actually been loaded. Left out deliberately to
	// keep the toolbar as it is -- add it here if that button is wanted.
	config.extraPlugins = 'lineheight,emoji,youtube';
	// The bundled smiley dialog inserts GIF images; plugins/emoji replaces it
	// with a Unicode emoji panel. Flash is dropped for good (see the
	// removePlugins list in the manage/* controllers); plugins/youtube covers
	// the video case instead.
	config.removeButtons = 'Smiley,Flash';
	config.allowedContent = true; 
	config.protectedSource.push(/<i[^>]*><\/i>/g);  // <i></i>
	config.protectedSource.push(/<span[^>]*><\/span>/g);  //<span></span>
	CKEDITOR.dtd.$removeEmpty.span = false;
	CKEDITOR.dtd.$removeEmpty['i'] = false;
	config.templates_replaceContent = false;

	// Preview admin-authored content the way it renders on the live site.
	// CKEDITOR.basePath is always "<site base>/assets/ckeditor/" (every
	// controller sets $this->ckeditor->basePath = base_url('assets/ckeditor/')),
	// so the frontend stylesheet sits one directory up from it. bodyClass
	// mirrors the ".page-contents" wrapper the frontend puts around this same
	// HTML (see inc/home.php, inc/legal-body.php, page.php, blog.php, etc.).
	config.contentsCss = [
		CKEDITOR.basePath + 'contents.css',
		CKEDITOR.basePath.replace(/ckeditor\/$/, 'frontend/css/app.css')
	];
	config.bodyClass = 'page-contents';
};

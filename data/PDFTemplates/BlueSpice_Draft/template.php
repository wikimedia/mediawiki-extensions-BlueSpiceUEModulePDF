<?php
/**
 * This is the main description file for the template. It contains all
 * information necessary to load and process the template.
 */

return [

	/* A brief description. This information may be used in the user interface */
	'info' => [
		'name'      => 'BlueSpice Draft',
		'author'    => 'Hallo Welt!',
		'copyright' => 'Hallo Welt! GmbH',
		'url'       => 'http://www.hallowelt.com',
		'description'      => 'This is the default BlueSpice Draft PDF Template'
	],

	/**
	 * The following resources are used in the conversion from xhtml to PDF.
	 * You may reference them in your template files
	 */
	'resources' => [
		'ATTACHMENT' => [
			'attachments/Disclaimer.txt'
			],
		'STYLESHEET' => [
			'stylesheets/draft.css',
			'../BlueSpice/stylesheets/styles.css', // Inherit from BlueSpice
			'../common/stylesheets/page.css',
			'../common/stylesheets/mediawiki.css',
			'../common/stylesheets/geshi-php.css',
			'../common/stylesheets/bluespice.css',
			'../common/stylesheets/tables.css',
			'../common/stylesheets/fonts.css',
			'../common/fonts/DejaVuSans.ttf',
			'../common/fonts/DejaVuSans-Bold.ttf',
			'../common/fonts/DejaVuSans-Oblique.ttf',
			'../common/fonts/DejaVuSans-BoldOblique.ttf',
			'../common/fonts/DejaVuSansMono.ttf',
			'../common/fonts/DejaVuSansMono-Bold.ttf',
			'../common/fonts/DejaVuSansMono-Oblique.ttf',
			'../common/fonts/DejaVuSansMono-BoldOblique.ttf'
		],
		'IMAGE' => [
			'../BlueSpice/images/bs-logo.png',
			'images/bs-draft.png'
		]
	],

	/**
	 * Here you can define messages for internationalization of your template.
	 */
	'messages' => [
		'en' => [
			'desc'        => 'This is the default draft PDFTemplate of BlueSpice for single article export.',
			'exportdate'  => 'Export date:',
			'page'        => 'Page ',
			'of'          => ' of ',
			'disclaimer'  => 'This document was created with BlueSpice'
		],
		'de' => [
			'desc'        => 'Dies ist das Standard-Entwurfs-PDFTemplate von BlueSpice für den Export einzelner Artikel.',
			'exportdate'  => 'Ausgabe:',
			'page'        => 'Seite ',
			'of'          => ' von ',
			'disclaimer'  => 'Dieses Dokument wurde erzeugt mit BlueSpice' ],
		'de-formal' => [
			'desc'        => 'Dies ist das Standard-Entwurfs-PDFTemplate von BlueSpice für den Export einzelner Artikel.',
			'exportdate'  => 'Ausgabe:',
			'page'        => 'Seite ',
			'of'          => ' von ',
			'disclaimer'  => 'Dieses Dokument wurde erzeugt mit BlueSpice' ],
	]
];

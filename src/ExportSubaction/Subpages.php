<?php

namespace BlueSpice\UEModulePDF\ExportSubaction;

use BlueSpice\UniversalExport\ExportSubaction\Subpages as SubpagesBase;
use BlueSpice\Utility\UrlTitleParser;
use BsExportModulePDF;
use BsPDFPageProvider;
use BsUniversalExportHelper;
use DOMDocument;
use DOMNode;
use Hooks;
use MediaWiki\MediaWikiServices;
use Message;
use Title;

class Subpages extends SubpagesBase {
	/** @var MediaWikiServices */
	protected $services;
	/** @var BsExportModulePDF */
	protected $pdfModule;

	/**
	 * @param MediaWikiServices $services
	 * @param BsExportModulePDF $modulePDF
	 * @return static
	 */
	public static function factory( MediaWikiServices $services, BsExportModulePDF $modulePDF ) {
		return new static( $services, $modulePDF );
	}

	/**
	 *
	 * @param MediaWikiServices $services
	 * @param BsExportModulePDF $pdfModule
	 */
	public function __construct( MediaWikiServices $services, BsExportModulePDF $pdfModule ) {
		$this->services = $services;
		$this->pdfModule = $pdfModule;
	}

	public function apply( &$template, &$contents, $caller ) {
		parent::apply( $template, $contents, $caller );

		foreach ( $contents['content'] as $oDom ) {
			$this->rewriteLinks( $oDom, $this->titleMap );
		}

		$this->makeBookmarks( $template, $this->includedTitles );

		$documentToc = $this->makeToc( $this->titleMap );
		array_unshift( $contents['content'], $documentToc->documentElement );

		Hooks::run(
			'UEModulePDFSubpagesAfterContent',
			[
				$this->pdfModule,
				&$contents
			]
		);

		return true;
	}

	/**
	 * @return string
	 */
	public function getPermission() {
		return 'uemodulepdfsubpages-export';
	}

	/**
	 * @return mixed
	 */
	protected function getPageProvider() {
		return new BsPDFPageProvider();
	}

	/**
	 *
	 * @param array &$template
	 * @param array $includedTitles
	 */
	protected function makeBookmarks( &$template, $includedTitles ) {
		foreach ( $includedTitles as $name => $content ) {
			$bookmarkNode = BsUniversalExportHelper::getBookmarkElementForPageDOM(
				$content['dom']
			);
			$bookmarkNode = $template['dom']->importNode( $bookmarkNode, true );

			$template['bookmarks-element']->appendChild( $bookmarkNode );
		}
	}

	/**
	 *
	 * @param DOMNode &$domNode
	 * @param array $linkMap
	 */
	protected function rewriteLinks( &$domNode, $linkMap ) {
		$anchors = $domNode->getElementsByTagName( 'a' );
		foreach ( $anchors as $anchor ) {
			$linkTitle = $anchor->getAttribute( 'data-bs-title' );
			$href  = $anchor->getAttribute( 'href' );

			if ( $linkTitle ) {
				$pathBasename = str_replace( '_', ' ', $linkTitle );

				$parsedHref = parse_url( $href );

				if ( isset( $linkMap[$pathBasename] ) && isset( $parsedHref['fragment'] ) ) {
					$linkMap[$pathBasename] = $linkMap[$pathBasename] . '-'
						. md5( $parsedHref['fragment'] );
				}
			} else {
				$class = $anchor->getAttribute( 'class' );

				if ( empty( $href ) ) {
					// Jumplink targets
					continue;
				}

				$classes = explode( ' ', $class );
				if ( in_array( 'external', $classes ) ) {
					continue;
				}

				$parsedHref = parse_url( $href );
				if ( !isset( $parsedHref['path'] ) ) {
					continue;
				}

				$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );
				$parser = new UrlTitleParser( $href, $config, true );
				$parsedTitle = $parser->parseTitle();

				if ( !$parsedTitle instanceof Title ) {
					continue;
				}

				$pathBasename = $parsedTitle->getPrefixedText();
			}

			if ( !isset( $linkMap[$pathBasename] ) ) {
				continue;
			}

			$anchor->setAttribute( 'href', '#' . $linkMap[$pathBasename] );
		}
	}

	/**
	 * @param array $linkMap
	 * @return DOMDocument
	 */
	protected function makeTOC( $linkMap ) {
		$tocDocument = new DOMDocument();

		$tocWrapper = $tocDocument->createElement( 'div' );
		$tocWrapper->setAttribute( 'class', 'bs-page-content bs-page-toc' );

		$tocHeading = $tocDocument->createElement( 'h1' );
		$tocHeading->appendChild( $tocDocument->createTextNode(
			Message::newFromKey( 'toc' )->text() )
		);

		$tocWrapper->appendChild( $tocHeading );

		$tocList = $tocDocument->createElement( 'ul' );
		$tocList->setAttribute( 'class', 'toc' );

		$count = 1;
		foreach ( $linkMap as $linkname => $linkHref ) {
			$liClass = 'toclevel-1';
			if ( $count === 1 ) {
				$liClass .= ' bs-source-page';
			}
			$tocListItem = $tocList->appendChild( $tocDocument->createElement( 'li' ) );
			$tocListItem->setAttribute( 'class', $liClass );

			$tocListItemLink = $tocListItem->appendChild( $tocDocument->createElement( 'a' ) );
			$tocListItemLink->setAttribute( 'href', '#' . $linkHref );
			$tocListItemLink->setAttribute( 'class', 'toc-link' );

			$tocLinkSpanNumber = $tocListItemLink->appendChild(
				$tocDocument->createElement( 'span' )
			);
			$tocLinkSpanNumber->setAttribute( 'class', 'tocnumber' );
			$tocLinkSpanNumber->appendChild( $tocDocument->createTextNode( $count . '.' ) );

			$tocListSpanText = $tocListItemLink->appendChild(
				$tocDocument->createElement( 'span' )
			);
			$tocListSpanText->setAttribute( 'class', 'toctext' );
			$tocListSpanText->appendChild( $tocDocument->createTextNode( ' ' . $linkname ) );

			$count++;
		}
		$tocWrapper->appendChild( $tocList );
		$tocDocument->appendChild( $tocWrapper );

		return $tocDocument;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionButtonDetails() {
		return [
			'title' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-subpages-title' ),
			'text' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-subpages-text' ),
			'iconClass' => 'icon-file-pdf'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getMainModule() {
		return $this->pdfModule;
	}
}

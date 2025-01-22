<?php

namespace BlueSpice\UEModulePDF\ExportSubaction;

use BlueSpice\UEModulePDF\ExportSubaction\Subpages as PDFSubpages;
use BlueSpice\UniversalExport\ExportSpecification;
use BsPDFPageProvider;
use MediaWiki\Message\Message;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;

class Recursive extends PDFSubpages {
	/** @var array */
	protected $contents;

	/**
	 * @inheritDoc
	 */
	public function applies( ExportSpecification $specification ) {
		return (bool)$specification->getParam( 'recursive', 0 );
	}

	/**
	 * @inheritDoc
	 */
	public function apply( &$template, &$contents, $specification ) {
		$this->contents = $contents;

		return parent::apply( $template, $contents, $specification );
	}

	/**
	 * @return string
	 */
	public function getPermission() {
		return 'uemodulepdfrecursive-export';
	}

	/**
	 * @return mixed
	 */
	protected function getPageProvider() {
		return new BsPDFPageProvider();
	}

	/**
	 * @inheritDoc
	 */
	public function getActionButtonDetails() {
		return [
			'title' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-recursive-title' ),
			'text' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-recursive-text' ),
			'iconClass' => 'icon-file-pdf'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getExportLink( WebRequest $request, $additional = [] ) {
		return $this->getMainModule()->getExportLink( $request, array_merge( $additional, [
			'ue[recursive]' => 1
		] ) );
	}

	/**
	 *
	 * @param ExportSpecification $specs
	 * @return array
	 */
	protected function findIncludedTitles( $specs ) {
		$linkdedTitles = [];
		$rootTitleDom = $this->contents['content'][0];

		$links = $rootTitleDom->getElementsByTagName( 'a' );

		foreach ( $links as $link ) {
			$class = $link->getAttribute( 'class' );
			$classes = explode( ' ', $class );
			$excludeClasses = [ 'new', 'external', 'media' ];

			// HINT: http://stackoverflow.com/questions/7542694/in-array-multiple-values
			if ( count( array_intersect( $classes, $excludeClasses ) ) > 0 ) {
				continue;
			}

			$linkTitle = $link->getAttribute( 'data-bs-title' );
			if ( empty( $linkTitle ) || empty( $link->nodeValue ) ) {
				continue;
			}

			$title = Title::newFromText( $linkTitle );
			if ( $title == null || !$title->canExist() ) {
				continue;
			}

			// Avoid double export
			if ( in_array( $title->getPrefixedText(), $linkdedTitles ) ) {
				continue;
			}

			$pm = $this->services->getPermissionManager();
			$userCan = $pm->userCan( 'read', $specs->getUser(), $title );
			if ( !$userCan ) {
				continue;
			}

			$pageProvider = $this->getPageProvider();
			$pageContent = $pageProvider->getPage( [
				'article-id' => $title->getArticleID(),
				'title' => $title->getFullText()
			] );

			if ( !isset( $pageContent['dom'] ) ) {
				continue;
			}

			$linkdedTitles = array_merge(
				$linkdedTitles,
				[
					$title->getPrefixedText() => $pageContent
				]
			);
		}

		ksort( $linkdedTitles );

		return $linkdedTitles;
	}
}

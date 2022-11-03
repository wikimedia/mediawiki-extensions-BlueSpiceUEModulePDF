<?php
/**
 * BsExportModulePDF.
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    BlueSpiceUEModulePDF
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

use BlueSpice\UEModulePDF\ExportSubaction\Recursive;
use BlueSpice\UEModulePDF\ExportSubaction\Subpages;
use BlueSpice\UEModulePDF\PDFServletHookRunner;
use BlueSpice\UniversalExport\ExportModule;
use BlueSpice\UniversalExport\ExportSpecification;

/**
 * UniversalExport BsExportModulePDF class.
 * @package BlueSpiceUEModulePDF
 */
class BsExportModulePDF extends ExportModule {

	/**
	 * @inheritDoc
	 */
	protected function setParams( &$specification ) {
		$wikiPage = $this->services->getWikiPageFactory()
			->newFromTitle( $specification->getTitle() );
		$redirectTarget = $this->services->getRedirectLookup()->getRedirectTarget( $wikiPage );
		if ( $redirectTarget instanceof Title ) {
			$aPageParams['title'] = $redirectTarget->getPrefixedText();
			$aPageParams['article-id'] = $redirectTarget->getArticleID();
		}

		if ( $this->config->get( 'UEModulePDFSuppressNS' ) ) {
			// Replace display-title only if it equals page title. If it doesn't display-title is set
			// If it is not equal display-title is set by author using {{DISPLAYTITLE:...}}
			if ( $aPageParams['display-title'] === $specification->getTitle()->getFullText() ) {
				$aPageParams['display-title'] = $specification->getTitle()->getText();
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getExportPermission() {
		return 'uemodulepdf-export';
	}

	/**
	 * @inheritDoc
	 */
	protected function setExportConnectionParams( ExportSpecification &$specs ) {
		parent::setExportConnectionParams( $specs );
		$specs->setParam( 'soap-service-url', $this->config->get( 'UEModulePDFPdfServiceURL' ) );
		// Duplicate to replace 'soap-service-url' in future
		$specs->setParam( 'backend-url', $this->config->get( 'UEModulePDFPdfServiceURL' ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function getTemplateParams( $specification, $page ) {
		$params = parent::getTemplateParams( $specification, $page );

		return array_merge(
			[
				'path'     => $this->config->get( 'UEModulePDFTemplatePath' ),
				'template' => $this->config->get( 'UEModulePDFDefaultTemplate' ),
			],
			$params
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getTemplate( $params ) {
		return BsPDFTemplateProvider::getTemplate( $params );
	}

	/**
	 * @inheritDoc
	 */
	protected function getPage( ExportSpecification $specification ) {
		return BsPDFPageProvider::getPage( $specification->getParams() );
	}

	/**
	 * @inheritDoc
	 */
	protected function decorateTemplate( &$template, &$contents, &$page, $specification ) {
		// Add the bookmarks
		$template['bookmarks-element']->appendChild(
			$template['dom']->importNode( $page['bookmark-element'], true )
		);
		$template['title-element']->nodeValue = $specification->getTitle()->getPrefixedText();

		$this->services->getHookContainer()->run(
			'BSUEModulePDFBeforeAddingContent',
			[
				&$template,
				&$contents,
				$specification,
				&$page
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyTemplateAfterContents( &$template, $page, $specification ) {
		$this->services->getHookContainer()->run(
			'BSUEModulePDFBeforeCreatePDF',
			[
				$this,
				$template['dom'],
				$specification
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getResponseParams() {
		return array_merge(
			parent::getResponseParams(),
			[
				'mime-type' => 'application/pdf',
				'filename'  => '%s.pdf',
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExportedContent( $specs, &$template ) {
		$params = $specs->getParams();
		$hookContainer = $this->services->getHookContainer();
		$hookRunner = new PDFServletHookRunner( $hookContainer );
		$backend = new BsPDFServlet( $params, $hookRunner );
		return $backend->createPDF( $template['dom'] );
	}

	/**
	 * Implementation of BsUniversalExportModule interface. Creates an overview
	 * over the PdfExportModule
	 * @return ViewExportModuleOverview
	 */
	public function getOverview() {
		$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );
		$oModuleOverviewView = new ViewExportModuleOverview();

		$oModuleOverviewView->setOption( 'module-title', wfMessage( 'bs-uemodulepdf-overview-title' )->plain() );
		$oModuleOverviewView->setOption( 'module-description', wfMessage( 'bs-uemodulepdf-overview-desc' )->plain() );
		$oModuleOverviewView->setOption( 'module-bodycontent', '' );

		$oWebserviceStateView = new ViewBaseElement();
		$oWebserviceStateView->setTemplate(
			'{LABEL}: <span style="font-weight: bold; color:{COLOR}">{STATE}</span>'
			);

		$sWebServiceUrl = $config->get( 'UEModulePDFPdfServiceURL' );
		$sWebserviceState = wfMessage( 'bs-uemodulepdf-overview-webservice-state-not-ok' )->plain();
		$sColor = 'red';
		if ( BsConnectionHelper::testUrlForTimeout( $sWebServiceUrl ) ) {
			$sColor = 'green';
			$sWebserviceState = wfMessage( 'bs-uemodulepdf-overview-webservice-state-ok' )->plain();

			$oWebserviceUrlView = new ViewBaseElement();
			$oWebserviceUrlView->setTemplate(
				'{LABEL}: <a href="{URL}" target="_blank">{URL}</a><br/>'
			);
			$oWebserviceUrlView->addData( [
				'LABEL' => wfMessage( 'bs-uemodulepdf-overview-webservice-webadmin' )->plain(),
				'URL' => $sWebServiceUrl,
			] );
			$oModuleOverviewView->addItem( $oWebserviceUrlView );
		}

		$oWebserviceStateView->addData( [
			'LABEL' => wfMessage( 'bs-uemodulepdf-overview-webservice-state' )->plain(),
			'COLOR' => $sColor,
			'STATE' => $sWebserviceState
		] );

		$oModuleOverviewView->addItem( $oWebserviceStateView );

		return $oModuleOverviewView;
	}

	/**
	 * @return array
	 */
	public function getSubactionHandlers() {
		return [
			'subpages' => Subpages::factory( $this->services, $this ),
			'recursive' => Recursive::factory( $this->services, $this ),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getActionButtonDetails() {
		return [
			'title' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-single-no-attachments-title' ),
			'text' => Message::newFromKey( 'bs-uemodulepdf-widgetlink-single-no-attachments-text' ),
			'iconClass' => 'icon-file-pdf'
		];
	}
}

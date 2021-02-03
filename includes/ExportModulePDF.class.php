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
use BlueSpice\UniversalExport\ExportModule;
use MediaWiki\MediaWikiServices;

/**
 * UniversalExport BsExportModulePDF class.
 * @package BlueSpiceUEModulePDF
 */
class BsExportModulePDF extends ExportModule {

	/**
	 * @inheritDoc
	 */
	protected function setParams( &$caller ) {
		parent::setParams( $caller );

		$redirectTarget = WikiPage::factory( $caller->oRequestedTitle )->getRedirectTarget();
		if ( $redirectTarget instanceof Title ) {
			$aPageParams['title'] = $redirectTarget->getPrefixedText();
			$aPageParams['article-id'] = $redirectTarget->getArticleID();
		}

		if ( $this->config->get( 'UEModulePDFSuppressNS' ) ) {
			$aPageParams['display-title'] = $caller->oRequestedTitle->getText();
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
	protected function setExportConnectionParams( &$caller ) {
		parent::setExportConnectionParams( $caller );
		$caller->aParams['soap-service-url'] = $this->config->get( 'UEModulePDFPdfServiceURL' );
		// Duplicate to replace 'soap-service-url' in future
		$caller->aParams['backend-url'] = $this->config->get( 'UEModulePDFPdfServiceURL' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getTemplateParams( $caller, $page ) {
		$params = parent::getTemplateParams( $caller, $page );

		return array_merge( $params, [
			'path'     => $this->config->get( 'UEModulePDFTemplatePath' ),
			'template' => $this->config->get( 'UEModulePDFDefaultTemplate' ),
		] );
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
	protected function getPage( $params ) {
		return BsPDFPageProvider::getPage( $params );
	}

	/**
	 * @inheritDoc
	 */
	protected function decorateTemplate( &$template, &$contents, &$page, $caller ) {
		// Add the bookmarks
		$template['bookmarks-element']->appendChild(
			$template['dom']->importNode( $page['bookmark-element'], true )
		);
		$template['title-element']->nodeValue = $caller->oRequestedTitle->getPrefixedText();

		$this->services->getHookContainer()->run(
			'BSUEModulePDFBeforeAddingContent',
			[
				&$template,
				&$contents,
				$caller,
				&$page
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyTemplateAfterContents( &$template, $page, $caller ) {
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSUEModulePDFBeforeCreatePDF',
			[
				$this,
				$template['dom'],
				$caller
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
	protected function getExportedContent( $caller, &$template ) {
		$backend = new BsPDFServlet( $caller->aParams );
		return $backend->createPDF( $template['dom'] );
	}

	/**
	 * Implementation of BsUniversalExportModule interface. Creates an overview
	 * over the PdfExportModule
	 * @return ViewExportModuleOverview
	 */
	public function getOverview() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'bsg' );
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

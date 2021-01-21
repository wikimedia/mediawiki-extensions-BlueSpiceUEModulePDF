<?php

namespace BlueSpice\UEModulePDF\Hook\SkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\SkinTemplateOutputPageBeforeExec;
use BlueSpice\SkinData;

class AddUEModulePDF extends SkinTemplateOutputPageBeforeExec {
	protected function skipProcessing() {
		if ( $this->skin->getTitle()->isSpecialPage() ) {
			return true;
		}

		if ( !$this->skin->getTitle()->userCan( 'uemodulepdf-export' ) ) {
			return true;
		}

		return false;
	}

	protected function doProcess() {
		$this->mergeSkinDataArray(
				SkinData::EXPORT_MENU,
				[
					20 => $this->buildContentAction()
				]
		);

		return true;
	}

	/**
	 * Builds the ContentAction Array fort the current page
	 * @return array The ContentAction Array
	 */
	private function buildContentAction() {
		/** @var \BlueSpice\UniversalExport\Util $util */
		$util = \MediaWiki\MediaWikiServices::getInstance()->getService(
			'BSUniversalExportUtils'
		);

		return [
			'id' => 'bs-ta-uemodulepdf',
			'href' => $util->getExportLink( $this->skin->getRequest(), 'pdf' ),
			'title' => wfMessage( 'bs-uemodulepdf-widgetlink-single-no-attachments-title' )->text(),
			'text' => wfMessage( 'bs-uemodulepdf-widgetlink-single-no-attachments-text' )->text(),
			'class' => 'bs-ue-export-link',
			'iconClass' => 'icon-file-pdf bs-ue-export-link'
		];
	}
}

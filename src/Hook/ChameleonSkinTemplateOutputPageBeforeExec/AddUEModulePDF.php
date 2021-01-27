<?php

namespace BlueSpice\UEModulePDF\Hook\ChameleonSkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\ChameleonSkinTemplateOutputPageBeforeExec;
use BlueSpice\SkinData;
use BlueSpice\UniversalExport\ModuleFactory;

class AddUEModulePDF extends ChameleonSkinTemplateOutputPageBeforeExec {
	protected function skipProcessing() {
		if ( $this->skin->getTitle()->isSpecialPage() ) {
			return true;
		}

		return !$this->getServices()->getPermissionManager()->userCan(
			'uemodulepdf-export',
			$this->skin->getUser(),
			$this->skin->getTitle()
		);
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
		/** @var ModuleFactory $moduleFactory */
		$moduleFactory = $this->getServices()->getService(
			'BSUniversalExportModuleFactory'
		);
		$module = $moduleFactory->newFromName( 'pdf' );

		return [
			'id' => 'bs-ta-uemodulepdf',
			'href' => $module->getExportLink( $this->skin->getRequest() ),
			'title' => wfMessage( 'bs-uemodulepdf-widgetlink-single-no-attachments-title' )->text(),
			'text' => wfMessage( 'bs-uemodulepdf-widgetlink-single-no-attachments-text' )->text(),
			'class' => 'bs-ue-export-link',
			'iconClass' => 'icon-file-pdf bs-ue-export-link'
		];
	}
}

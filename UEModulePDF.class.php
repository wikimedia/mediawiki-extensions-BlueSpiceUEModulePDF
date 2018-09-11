<?php
/**
 * UniversalExport PDF Module extension for BlueSpice
 *
 * Enables MediaWiki to export pages into PDF format.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    BlueSpiceUEModulePDF
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

/**
 * Base class for UniversalExport PDF Module extension
 * @package BlueSpiceUEModulePDF
 */
class UEModulePDF extends BsExtensionMW {
	/**
	 * Initialization of UEModulePDF extension
	 */
	protected function initExt() {
		$this->setHook('BSUniversalExportSpecialPageExecute');
	}

	/**
	 * extension.json callback
	 */
	public static function onRegistration() {
		/**
		 * Allows modification for CURL request. E.g. setting an CA file for
		 * HTTPS
		 */
		$GLOBALS['bsgUEModulePDFCURLOptions'] = array();

		/**
		 * This value is considered when asseta are being uploaded to the PDF
		 * service
		 */
		$GLOBALS['bsgUEModulePDFUploadThreshold'] = 50 * 1024 * 1024;

		// Remove if minimal system requirements of MW changes to PHP <= 5.5
		if( !defined( 'CURLOPT_SAFE_UPLOAD' ) ) {
			define( 'CURLOPT_SAFE_UPLOAD', -1 );
		}
	}

	/**
	 * Sets up requires directories
	 * @param DatabaseUpdater $updater Provided by MediaWikis update.php
	 * @return boolean Always true to keep the hook running
	 */
	public static function getSchemaUpdates( $updater ) {
		//TODO: Create abstraction in Core/Adapter
		$sTmpDir = BS_DATA_DIR . '/UEModulePDF';
		if( !file_exists( $sTmpDir ) ) {
			echo 'Directory "'.$sTmpDir.'" not found. Creating.'."\n";
			wfMkdirParents( $sTmpDir );
		} else {
			echo 'Directory "'.$sTmpDir.'" found.'."\n";
		}

		$sDefaultTemplateDir = BS_DATA_DIR . '/PDFTemplates';
		if( !file_exists( $sDefaultTemplateDir ) ) {
			echo 'Default template directory "'.$sDefaultTemplateDir.'" not found. Copying.'."\n";
			BsFileSystemHelper::copyRecursive( __DIR__ . '/data/PDFTemplates', $sDefaultTemplateDir );
		}

		return true;
	}

	/**
	 *
	 * @param SpecialUniversalExport $oSpecialPage
	 * @param string $sParam
	 * @param array $aModules
	 * @return true
	 */
	public function onBSUniversalExportSpecialPageExecute( $oSpecialPage, $sParam, &$aModules ) {
		$aModules['pdf'] = new BsExportModulePDF();
		return true;
	}

	/**
	 * Event-Handler method for the 'BSUniversalExportCreateWidget' event.
	 * Registers the PDF Module with the UniversalExport Extension.
	 * @param BsEvent $oEvent
	 * @param array $aModules
	 * @return array
	 * @deprecated in 1.1.1
	 */
	public function onUniversalExportSpecialPageExecute( $oCurrentTitle, $oSpecialPage, $aCurrentQueryParams, $aModules) {
		$aModules['pdf'] = new BsExportModulePDF();
		return $aModules;
	}

}

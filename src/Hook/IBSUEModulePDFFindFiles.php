<?php

namespace BlueSpice\UEModulePDF\Hook;

use BsPDFServlet;
use DOMElement;

interface IBSUEModulePDFFindFiles {

	/**
	 *
	 * @param BsPDFServlet $sender
	 * @param DOMElement $imgEl
	 * @param string &$absFSpath
	 * @param string &$fileName
	 * @param string $type
	 * @return bool
	 */
	public function onBSUEModulePDFFindFiles(
		$sender,
		$imgEl,
		&$absFSpath,
		&$fileName,
		$type ): bool;

}

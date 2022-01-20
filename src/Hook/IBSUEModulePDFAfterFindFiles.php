<?php

namespace BlueSpice\UEModulePDF\Hook;

use BsPDFServlet;
use DOMDocument;
use DOMXPath;

interface IBSUEModulePDFAfterFindFiles {

	/**
	 *
	 * @param BsPDFServlet $sender
	 * @param DOMDocument $html
	 * @param array &$files
	 * @param array $params
	 * @param DOMXPath $xPath
	 * @return bool
	 */
	public function onBSUEModulePDFAfterFindFiles(
		$sender,
		$html,
		&$files,
		$params,
		$xPath ): bool;
}

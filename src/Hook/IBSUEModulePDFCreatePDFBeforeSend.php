<?php

namespace BlueSpice\UEModulePDF\Hook;

use BsPDFServlet;
use DOMDocument;

interface IBSUEModulePDFCreatePDFBeforeSend {

	/**
	 *
	 * @param BsPDFServlet $sender
	 * @param array &$options
	 * @param DOMDocument $htmlDOM
	 * @return bool
	 */
	public function onBSUEModulePDFCreatePDFBeforeSend( $sender, &$options, $htmlDOM ): bool;
}

<?php

namespace BlueSpice\UEModulePDF\Hook;

use BsPDFServlet;

interface IBSUEModulePDFUploadFilesBeforeSend {

	/**
	 *
	 * @param BsPDFServlet $sender
	 * @param array &$postData
	 * @param string $type
	 * @return bool
	 */
	public function onBSUEModulePDFUploadFilesBeforeSend( $sender, &$postData, $type ): bool;
}

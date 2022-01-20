<?php

namespace BlueSpice\UEModulePDF\HookHandler;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFUploadFilesBeforeSend;

class BSUEModulePDFUploadFilesBeforeSendStub implements IBSUEModulePDFUploadFilesBeforeSend {

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFUploadFilesBeforeSend( $sender, &$postData, $type ): bool {
		return true;
	}
}

<?php

namespace BlueSpice\UEModulePDF\HookHandler;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFCreatePDFBeforeSend;

class BSUEModulePDFCreatePDFBeforeSendStub implements IBSUEModulePDFCreatePDFBeforeSend {

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFCreatePDFBeforeSend( $sender, &$options, $htmlDOM ): bool {
		return true;
	}
}

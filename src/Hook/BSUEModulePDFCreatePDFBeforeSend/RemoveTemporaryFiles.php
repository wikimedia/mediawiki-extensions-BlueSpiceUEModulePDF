<?php

namespace BlueSpice\UEModulePDF\Hook\BSUEModulePDFCreatePDFBeforeSend;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFCreatePDFBeforeSend;

class RemoveTemporaryFiles implements IBSUEModulePDFCreatePDFBeforeSend {
	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFCreatePDFBeforeSend( $sender, &$options, $htmlDOM ): bool {
		global $wgUploadDirectory;

		rmdir( "$wgUploadDirectory/cache/pdf_files" );

		return true;
	}
}

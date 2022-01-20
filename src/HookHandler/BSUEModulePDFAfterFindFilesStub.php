<?php

namespace BlueSpice\UEModulePDF\HookHandler;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFAfterFindFiles;

class BSUEModulePDFAfterFindFilesStub implements IBSUEModulePDFAfterFindFiles {
	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFAfterFindFiles(
		$sender,
		$html,
		&$files,
		$params,
		$xPath ): bool {
		return true;
	}
}

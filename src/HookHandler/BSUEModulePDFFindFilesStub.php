<?php

namespace BlueSpice\UEModulePDF\HookHandler;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFFindFiles;

class BSUEModulePDFFindFilesStub implements IBSUEModulePDFFindFiles {

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFFindFiles(
		$sender,
		$imgEl,
		&$absFSpath,
		&$fileName,
		$type ): bool {
		return true;
	}
}

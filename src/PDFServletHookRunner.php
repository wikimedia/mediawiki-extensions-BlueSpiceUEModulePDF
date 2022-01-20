<?php

namespace BlueSpice\UEModulePDF;

use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFAfterFindFiles;
use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFCreatePDFBeforeSend;
use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFFindFiles;
use BlueSpice\UEModulePDF\Hook\IBSUEModulePDFUploadFilesBeforeSend;
use MediaWiki\HookContainer\HookContainer;

class PDFServletHookRunner implements
	IBSUEModulePDFAfterFindFiles,
	IBSUEModulePDFCreatePDFBeforeSend,
	IBSUEModulePDFFindFiles,
	IBSUEModulePDFUploadFilesBeforeSend
{

	/**
	 *
	 * @var HookContainer
	 */
	private $container = null;

	/**
	 * @param HookContainer $container
	 */
	public function __construct( HookContainer $container ) {
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFAfterFindFiles(
		$sender,
		$html,
		&$files,
		$params,
		$xPath ): bool {
		return $this->container->run(
			'BSUEModulePDFAfterFindFiles',
			[
				$this,
				$html,
				&$files,
				$params,
				$xPath
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFCreatePDFBeforeSend( $sender, &$options, $htmlDOM ): bool {
		return $this->container->run(
			'BSUEModulePDFCreatePDFBeforeSend',
			[
				$sender,
				&$options,
				$htmlDOM
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFFindFiles(
		$sender,
		$imgEl,
		&$absFSpath,
		&$fileName,
		$type ): bool {
		return $this->container->run(
			'BSUEModulePDFFindFiles',
			[
				$sender,
				$imgEl,
				&$absFSpath,
				&$fileName,
				$type
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onBSUEModulePDFUploadFilesBeforeSend( $sender, &$postData, $type ): bool {
		return $this->container->run(
			'BSUEModulePDFUploadFilesBeforeSend',
			[
				$sender,
				&$postData,
				$type
			]
		);
	}

}

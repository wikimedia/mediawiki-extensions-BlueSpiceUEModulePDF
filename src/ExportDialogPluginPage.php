<?php

namespace BlueSpice\UEModulePDF;

use BlueSpice\UniversalExport\IExportDialogPlugin;
use IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MWException;

class ExportDialogPluginPage implements IExportDialogPlugin {

	/** @var PermissionManager */
	private $permissionManager = null;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->permissionManager = $services->getPermissionManager();
	}

	/**
	 * @return void
	 */
	public static function factory() {
		return new static();
	}

	/**
	 *
	 * @return array
	 */
	public function getRLModules(): array {
		return [ "ext.bluespice.ueModulePDF.ue-export-dialog-plugin.pdf" ];
	}

	/**
	 *
	 * @return array
	 */
	public function getJsConfigVars(): array {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$defaultTemplate = $config->get( 'UEModulePDFDefaultTemplate' );
		$defaultTemplatePath = $config->get( 'UEModulePDFTemplatePath' );
		$excludeList = $config->get( 'UEModulePDFExportDialogExcludeTemplates' );

		$availableTemplates = [];
		$dir = opendir( $defaultTemplatePath );
		if ( $dir === false ) {
			throw new MWException(
				'Could not read default PDF template path. Check `$bsgUEModulePDFTemplatePath`'
			);
		}
		$subDir = readdir( $dir );
		while ( $subDir !== false ) {
			if ( in_array( $subDir, [ '.', '..', 'common' ] ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( !is_dir( "{$defaultTemplatePath}/{$subDir}" ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( in_array( $subDir, $excludeList ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( file_exists( "{$defaultTemplatePath}/{$subDir}/template.php" ) ) {
				$availableTemplates[] = $subDir;
			}

			$subDir = readdir( $dir );
		}

		$jsConfigVars = [];
		if ( empty( $availableTemplates ) ) {
			$defaultTemplate = '';
		} else {
			if ( !in_array( $defaultTemplate, $availableTemplates ) ) {
				$defaultTemplate = $availableTemplates[0];
			}
		}

		$jsConfigVars = [
			'bsUEModulePDFDefaultTemplate' => $defaultTemplate,
			'bsUEModulePDFAvailableTemplates' => $availableTemplates,
			'UEModulePDFAllowSubpages' => false,
			'UEModulePDFAllowRecursive' => false
		];

		$context = RequestContext::getMain();
		if ( $this->permissionManager->userCan( 'uemodulepdfsubpages-export', $context->getUser(), $context->getTitle() ) ) {
			$jsConfigVars['UEModulePDFAllowSubpages'] = true;
		}
		if ( $this->permissionManager->userCan( 'uemodulepdfrecursive-export', $context->getUser(), $context->getTitle() ) ) {
			$jsConfigVars['UEModulePDFAllowRecursive'] = true;
		}

		return $jsConfigVars;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function skip( IContextSource $context ): bool {
		$title = $context->getSkin()->getRelevantTitle();

		if ( $title instanceof Title === false ) {
			return true;
		}

		if ( $title->getContentModel() !== CONTENT_MODEL_WIKITEXT ) {
			return true;
		}

		if ( !$this->permissionManager->userCan( 'uemodulepdf-export', $context->getUser(), $title ) ) {
			return true;
		}

		return false;
	}
}

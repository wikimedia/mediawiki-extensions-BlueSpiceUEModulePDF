<?php

namespace BlueSpice\UEModulePDF;

use BlueSpice\UniversalExport\IExportDialogPlugin;
use IContextSource;
use MediaWiki\MediaWikiServices;
use MWException;

class ExportDialogPluginPage implements IExportDialogPlugin {

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
			'bsUEModulePDFAvailableTemplates' => $availableTemplates
		];

		return $jsConfigVars;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function skip( IContextSource $context ): bool {
		return false;
	}
}

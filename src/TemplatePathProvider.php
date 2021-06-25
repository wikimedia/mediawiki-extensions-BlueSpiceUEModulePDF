<?php

namespace BlueSpice\UEModulePDF;

use Exception;

class TemplatePathProvider {

	/**
	 *
	 * @var string
	 */
	private $IP = '';

	/**
	 * @param string $IP MediaWiki installation path
	 */
	public function __construct( $IP ) {
		$this->IP = $IP;
	}

	/**
	 *
	 * @return TemplatePathProvider
	 */
	public static function newFromGlobals() {
		return new static( $GLOBALS['IP'] );
	}

	/**
	 *
	 * @param string $configuredPath
	 * @param string $templateName
	 * @throws Exception
	 * @return string
	 */
	public function getPath( $configuredPath, $templateName ) {
		$path = realpath( "$configuredPath/$templateName" );
		if ( is_string( $path ) && file_exists( $path ) ) {
			return $path;
		}

		$pathToTest = $this->IP . '/' . str_replace( $this->IP . '/', '', $configuredPath );
		$pathToTest = $pathToTest . '/' . $templateName;
		$path = realpath( $pathToTest );
		if ( !is_string( $path ) || !file_exists( $path ) ) {
			throw new Exception( 'Requested template not found! Path:' . $pathToTest );
		}

		return $path;
	}

}

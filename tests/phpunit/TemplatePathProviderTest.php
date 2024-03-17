<?php

namespace BlueSpice\UEModuleBookPDF\Test;

use BlueSpice\UEModulePDF\TemplatePathProvider;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlueSpice\UEModulePDF\TemplatePathProvider
 */
class TemplatePathProviderTest extends TestCase {

	/**
	 * @covers \BlueSpice\UEModulePDF\TemplatePathProvider::getPath
	 * @dataProvider provideTestGetPathData
	 *
	 * @param string $IP
	 * @param string $configuredPath
	 * @param string $templateName
	 * @param string $expectedResult
	 * @return void
	 */
	public function testGetPath( $IP, $configuredPath, $templateName, $expectedResult ) {
		$pathProvider = new TemplatePathProvider( $IP );
		$path = $pathProvider->getPath( $configuredPath, $templateName );
		$this->assertEquals( $expectedResult, $path );
	}

	/**
	 *
	 * @return array
	 */
	public function provideTestGetPathData() {
		$thisDir = __DIR__;
		$notThisDir = sys_get_temp_dir();
		return [
			'configured-relative-path' =>
				[
					$thisDir,
					'data/test-template-path/',
					'TestTemplate',
					"$thisDir/data/test-template-path/TestTemplate"
				],
			'configured-absolute-path' =>
				[
					$thisDir,
					"$thisDir/data/test-template-path/",
					'TestTemplate',
					"$thisDir/data/test-template-path/TestTemplate"
				],
			'configured-absolute-path-outside-installation-path' =>
				[
					$notThisDir,
					"$thisDir/data/test-template-path/",
					'TestTemplate',
					"$thisDir/data/test-template-path/TestTemplate"
				],
		];
	}

	/**
	 * @covers \BlueSpice\UEModulePDF\TemplatePathProvider::getPath
	 */
	public function testGetPathException() {
		$this->expectException( Exception::class );
		$pathProvider = new TemplatePathProvider( __DIR__ );
		$pathProvider->getPath( 'data/test-template-path/', 'NonExitingTemplate' );
	}
}

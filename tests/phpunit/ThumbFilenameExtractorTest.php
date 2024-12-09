<?php

use BlueSpice\UEModulePDF\ThumbFilenameExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @group medium
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @covers ThumbFilenameExtractor
 */
class ThumbFilenameExtractorTest extends TestCase {

	/**
	 * @covers ThumbFilenameExtractor::isThumb
	 */
	public function testIsThumb() {
		$extractor = new ThumbFilenameExtractor();

		$thumbPath = '/thumb/7/77/Test.pdf/300px-Test.pdf.jpg';
		$isThumb = $extractor->isThumb( $thumbPath );
		$this->assertTrue( $isThumb );

		$thumbPath = '/images/thumb/7/77/Test.pdf/300px-Test.pdf.jpg';
		$isThumb = $extractor->isThumb( $thumbPath );
		$this->assertTrue( $isThumb );

		$thumbPath = '/7/77/Test.png';
		$isThumb = $extractor->isThumb( $thumbPath );
		$this->assertFalse( $isThumb );
	}

	/**
	 * @covers ThumbFilenameExtractor::extractFilename
	 */
	public function testExtractFilename() {
		$extractor = new ThumbFilenameExtractor();

		$thumbPath = '/thumb/7/77/Test.pdf/300px-Test.pdf.jpg';
		$filename = $extractor->extractFilename( $thumbPath );
		$this->assertEquals( '300px-Test.pdf.jpg', $filename );

		$thumbPath = '/images/thumb/7/77/Test.pdf/300px-Test.pdf.jpg';
		$filename = $extractor->extractFilename( $thumbPath );
		$this->assertEquals( '300px-Test.pdf.jpg', $filename );

		$thumbPath = '/thumb/7/77/Test.png/300px-Test.png';
		$filename = $extractor->extractFilename( $thumbPath );
		$this->assertEquals( 'Test.png', $filename );

		$thumbPath = '/images/thumb/7/77/Test.png/300px-Test.png';
		$filename = $extractor->extractFilename( $thumbPath );
		$this->assertEquals( 'Test.png', $filename );
	}
}

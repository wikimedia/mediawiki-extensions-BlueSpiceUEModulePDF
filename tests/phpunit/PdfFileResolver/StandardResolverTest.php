<?php

namespace BlueSpice\UEModulePDF\Tests\PdfFileResolver;

use Config;
use DOMDocument;
use DOMElement;
use File;
use FileRepo;
use FSFile;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\User;
use PDFFileResolver;
use PHPUnit\Framework\TestCase;
use RepoGroup;

/**
 * @covers PDFFileResolver
 */
class StandardResolverTest extends TestCase {

	/**
	 * @var DOMDocument
	 */
	private $dom;

	/**
	 * @var string
	 */
	private $webrootFileSystemPath = '/var/www/bs4';

	/**
	 * @var string
	 */
	private $sourceAttribute = 'src';

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->dom = new DOMDocument();
	}

	public function provideData() {
		return [
			'Regular case' => [
				[
					'File:random_1_%^&_name.jpg',
					'/wiki/nsfr_img_auth.php/3/3b/random_1_%25%5E%26_name.jpg',
					'1000'
				],
				[
					'random_1_%^&_name.jpg',
					'/var/www/wiki/images/3/3b/random_1_%^&_name.jpg',
					'mwstore://local-backend/local-public/3/3b/random_1_%^&_name.jpg'
				],
				true,
				'650',
				'random_1_%^&_name.jpg',
				'/var/www/wiki/images/3/3b/random_1_%^&_name.jpg'
			]
		];
	}

	/**
	 * @dataProvider provideData
	 */
	public function testFileResolver(
		$imageInfo,
		$fileInfo,
		$isLocalFile,
		$expectedWidth,
		$expectedFileName,
		$expectedAbsoluteFileSystemPath
	) {
		[ $imageTitle, $imageUrl, $imageWidth ] = $imageInfo;

		if ( $isLocalFile ) {
			[ $fileName, $fileLocalPath, $fileBackendPath ] = $fileInfo;
		}

		$permissionManagerMock = $this->createMock( PermissionManager::class );
		$permissionManagerMock->method( 'userCan' )->willReturn( true );

		$userMock = $this->createMock( User::class );

		$imgNode = $this->addImageNode( $imageTitle, $imageUrl, $imageWidth );

		$repoGroupMock = $this->createMock( RepoGroup::class );
		if ( $isLocalFile ) {
			$fileObjMock = $this->makeFileMockObject( $fileName, $fileLocalPath,
				$fileBackendPath, $imageWidth );

			$repoGroupMock->method( 'findFile' )->willReturn( $fileObjMock );
		} else {
			$repoGroupMock->method( 'findFile' )->willReturn( false );
		}

		$mainConfigMock = $this->createMock( Config::class );
		$mainConfigMock->method( 'get' )->willReturnMap( [
			[ 'Server', 'http://some_server' ],
			[ 'ThumbnailScriptPath', false ],
			[ 'UploadPath', '/wiki/nsfr_img_auth.php' ],
			[ 'ScriptPath', '/wiki' ],
			[ 'UploadDirectory', '/var/www/bs4/images' ],
			[ 'ExtensionDirectory', '/var/www/bs4/extensions' ],
		] );

		$pdfFileResolver = new PDFFileResolver(
			$imgNode,
			$this->webrootFileSystemPath,
			$this->sourceAttribute,
			$permissionManagerMock,
			$userMock,
			$repoGroupMock,
			$mainConfigMock
		);

		$actualFileName = $pdfFileResolver->getFileName();
		$actualAbsoluteFileSystemPath = $pdfFileResolver->getAbsoluteFilesystemPath();

		$this->assertSame( $expectedFileName, $actualFileName );
		$this->assertSame( $expectedAbsoluteFileSystemPath, $actualAbsoluteFileSystemPath );

		$this->assertSame( $expectedWidth, $imgNode->getAttribute( 'width' ) );
	}

	/**
	 * Test case when user does not have enough permissions to view specified images
	 */
	public function testNotAllowed() {
		$permissionManagerMock = $this->createMock( PermissionManager::class );
		$permissionManagerMock->method( 'userCan' )->willReturn( false );

		$userMock = $this->createMock( User::class );

		$imgNode = $this->addImageNode(
			'File:Forbitten_image',
			'/wiki/nsfr_img_auth.php/c/c5/forbitten_image.png',
			'1000'
		);

		$extensionDirectory = '/var/www/bs4/extensions';

		$mainConfigMock = $this->createMock( Config::class );
		$mainConfigMock->method( 'get' )->willReturnMap( [
			[ 'Server', 'http://some_server' ],
			[ 'ThumbnailScriptPath', false ],
			[ 'UploadPath', '/wiki/nsfr_img_auth.php' ],
			[ 'ScriptPath', '/wiki' ],
			[ 'UploadDirectory', '/var/www/bs4/images' ],
			[ 'ExtensionDirectory', $extensionDirectory ],
		] );

		$pdfFileResolver = new PDFFileResolver(
			$imgNode,
			$this->webrootFileSystemPath,
			$this->sourceAttribute,
			$permissionManagerMock,
			$userMock,
			null,
			$mainConfigMock
		);

		$actualFileName = $pdfFileResolver->getFileName();
		$actualAbsoluteFileSystemPath = $pdfFileResolver->getAbsoluteFilesystemPath();

		$expectedFileName = 'bs_ue_module_pdf_access_denied.png';
		$expectedAbsoluteFileSystemPath = $extensionDirectory
			. "/BlueSpiceFoundation/resources/assets/ue-module-pdf/access_denied.png";

		$this->assertSame( '100px', $imgNode->getAttribute( 'width' ) );
		$this->assertSame( $expectedFileName, $actualFileName );
		$this->assertSame( $expectedAbsoluteFileSystemPath, $actualAbsoluteFileSystemPath );
	}

	/**
	 * @param string $imageTitle
	 * @param string $imageUrl
	 * @param string $width
	 *
	 * @return DOMElement
	 */
	private function addImageNode( string $imageTitle, string $imageUrl, string $width ): DOMElement {
		$anchorNode = $this->dom->createElement( 'a' );
		$anchorNode->setAttribute( 'data-bs-title', $imageTitle );

		$imgNode = $this->dom->createElement( 'img' );
		$imgNode->setAttribute( 'src', $imageUrl );
		$imgNode->setAttribute( 'width', $width );

		$anchorNode->appendChild( $imgNode );
		$this->dom->appendChild( $anchorNode );

		return $imgNode;
	}

	/**
	 * @param string $name
	 * @param string $localPath
	 * @param string $backendPath
	 * @param string $width
	 *
	 * @return File
	 */
	private function makeFileMockObject(
		string $name,
		string $localPath,
		string $backendPath,
		string $width
	): File {
		$repoMock = $this->createMock( FileRepo::class );

		$localReferenceMock = $this->createMock( FSFile::class );
		$localReferenceMock->method( 'getPath' )->willReturn( $localPath );

		$repoMock->method( 'getLocalReference' )->willReturn( $localReferenceMock );

		$fileObjMock = $this->createMock( File::class );
		$fileObjMock->method( 'exists' )->willReturn( true );
		$fileObjMock->method( 'getName' )->willReturn( $name );
		$fileObjMock->method( 'getWidth' )->willReturn( $width );
		$fileObjMock->method( 'getPath' )->willReturn( $backendPath );
		$fileObjMock->method( 'getRepo' )->willReturn( $repoMock );

		return $fileObjMock;
	}
}

<?php

use BlueSpice\UEModulePDF\ThumbFilenameExtractor;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

class PDFFileResolver {

	/**
	 * @var DOMElement
	 */
	protected $oImgNode = null;

	/**
	 * @var string
	 */
	protected $sWebrootFileSystemPath = '';

	/**
	 * @var string
	 */
	protected $sSourceAttribute;

	/**
	 * @var string
	 */
	protected $sFileName = '';

	/**
	 * @var string
	 */
	protected $sSourceFileName = '';

	/**
	 * @var string
	 */
	protected $sSourceFilePath = '';

	/**
	 * @var Title
	 */
	protected $oFileTitle = null;

	/**
	 * @var File
	 */
	protected $oFileObject = null;

	/**
	 * @var string
	 */
	protected $sAbsoluteFilesystemPath = '';

	/**
	 *
	 * @var bool
	 */
	protected $isAllowed = false;

	/**
	 *
	 * @var PermissionManager
	 */
	protected $permissionManager = null;

	/**
	 *
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var RepoGroup
	 */
	protected $repoGroup = null;

	/**
	 * @var Config
	 */
	protected $mainConfig = null;

	/**
	 *
	 * @param DOMElement $oImgEl
	 * @param string $sWebrootFileSystemPath
	 * @param string $sSourceAttribute
	 * @param PermissionManager|null $permissionManager
	 * @param User|null $user
	 * @param RepoGroup|null $repoGroup
	 * @param Config|null $mainConfig
	 */
	public function __construct( $oImgEl, $sWebrootFileSystemPath, $sSourceAttribute = 'src',
		$permissionManager = null, $user = null, $repoGroup = null, $mainConfig = null ) {
		$this->oImgNode = $oImgEl;
		$this->sWebrootFileSystemPath = $sWebrootFileSystemPath;
		$this->sSourceAttribute = $sSourceAttribute;
		$this->permissionManager = $permissionManager;
		if ( $this->permissionManager === null ) {
			$this->permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		}
		$this->user = $user;
		if ( $this->user === null ) {
			$this->user = RequestContext::getMain()->getUser();
		}
		$this->repoGroup = $repoGroup;
		if ( $this->repoGroup === null ) {
			$this->repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
		}
		$this->mainConfig = $mainConfig;
		if ( $this->mainConfig === null ) {
			$this->mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		}

		$this->init();
	}

	/**
	 * Factory method for file resolvers.
	 *
	 * @param DOMElement $oImgEl
	 * @param string $sWebrootFileSystemPath
	 * @param string $sSourceAttribute
	 * @param PermissionManager|null $permissionManager
	 * @param User|null $user
	 * @param RepoGroup|null $repoGroup
	 * @param Config|null $mainConfig
	 *
	 * @return PDFFileResolver
	 * @throws Exception
	 */
	public static function factory( $oImgEl, $sWebrootFileSystemPath, $sSourceAttribute = 'src',
		$permissionManager = null, $user = null, $repoGroup = null, $mainConfig = null ) {
		$attrName = 'BlueSpiceUEModulePDFFileResolverRegistry';
		$fileResolverRegistry = ExtensionRegistry::getInstance()->getAttribute( $attrName );

		$bsConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );

		$fileResolverKey = $bsConfig->get( 'UEModulePDFFileResolver' );

		if ( empty( $fileResolverRegistry[$fileResolverKey] ) ) {
			throw new Exception( 'Specified file resolver does not exist!' );
		}

		$class = $fileResolverRegistry[$fileResolverKey];

		$resolver = new $class(
			$oImgEl,
			$sWebrootFileSystemPath,
			$sSourceAttribute,
			$permissionManager,
			$user,
			$repoGroup,
			$mainConfig
		);

		return $resolver;
	}

	protected function init() {
		$this->extractSourceFilename();
		$this->setFileTitle();
		$this->checkPermission();
		$this->setFileObject();
		$this->setWidthAttribute();
		$this->setAbsoluteFilesystemPath();
		$this->setFileName();
		$this->setSourceAttributes();
	}

	protected function extractSourceFilename() {
		$aForPreg = [
			$this->mainConfig->get( 'Server' ),
			$this->mainConfig->get( 'ThumbnailScriptPath' ) . "?f=",
			$this->mainConfig->get( 'UploadPath' ),
			$this->mainConfig->get( 'ScriptPath' )
		];

		$sOrigUrl = $this->oImgNode->getAttribute( $this->sSourceAttribute );
		if ( strpos( $sOrigUrl, '?' ) ) {
			$sOrigUrl = substr( $sOrigUrl, 0, strpos( $sOrigUrl, '?' ) );
		}
		$sSrcUrl = urldecode( $sOrigUrl );

		// Extracting the filename
		foreach ( $aForPreg as $sForPreg ) {
			$sSrcUrl = preg_replace( "#" . preg_quote( $sForPreg, "#" ) . "#", '', $sSrcUrl );
			$sSrcUrl = preg_replace( '/(&.*)/', '', $sSrcUrl );
		}

		$this->sSourceFilePath = $sSrcUrl;

		$sSrcFilename = wfBaseName( $sSrcUrl );
		$oAnchor = BsDOMHelper::getParentDOMElement( $this->oImgNode, [ 'a' ] );
		if ( $oAnchor instanceof DOMElement && $oAnchor->getAttribute( 'data-bs-title' ) !== '' ) {
			$sSrcFilename = $oAnchor->getAttribute( 'data-bs-title' );
		}

		$thumbFilenameExtractor = new ThumbFilenameExtractor();
		$bIsThumb = $thumbFilenameExtractor->isThumb( $sSrcUrl );
		$sTmpFilename = $sSrcFilename;
		if ( $bIsThumb ) {
			// HINT: Thumbname-to-filename-conversion taken from includes/Upload/UploadBase.php
			// Check for filenames like 50px- or 180px-, these are mostly thumbnails
			$sTmpFilename = $thumbFilenameExtractor->extractFilename( $sSrcUrl );
		}

		$this->sSourceFileName = $sTmpFilename;
	}

	protected function setFileTitle() {
		$this->oFileTitle = Title::newFromText( $this->sSourceFileName, NS_FILE );
	}

	protected function setFileObject() {
		if ( !$this->isAllowed ) {
			return;
		}

		$sTimestamp = '';
		$oAnchor = BsDOMHelper::getParentDOMElement( $this->oImgNode, [ 'a' ] );
		if ( $oAnchor instanceof DOMElement ) {
			$sTimestamp = $oAnchor->getAttribute( 'data-bs-filetimestamp' );
		}

		$aOptions = [
			'time' => $sTimestamp,
			'latest' => true
		];

		$this->oFileObject = $this->repoGroup->findFile(
			$this->oFileTitle,
			$aOptions
		);
	}

	protected function setWidthAttribute() {
		$iWidth = $this->oImgNode->getAttribute( 'width' );
		if ( !$this->isAllowed && $iWidth > 100 ) {
			$this->oImgNode->setAttribute( 'width', '100px' );
			$this->oImgNode->removeAttribute( 'height' );
			return;
		}
		if ( empty( $iWidth ) && $this->oFileObject instanceof File && $this->oFileObject->exists() ) {
			$iWidth = $this->oFileObject->getWidth();
			$this->oImgNode->setAttribute( 'width', $iWidth );
		}
		if ( $iWidth > 650 ) {
			$this->oImgNode->setAttribute( 'width', 650 );
			$this->oImgNode->removeAttribute( 'height' );

			$sClasses = $this->oImgNode->getAttribute( 'class' );
			// TODO: remove class maxwidth
			$this->oImgNode->setAttribute( 'class', $sClasses . 'bs-correct-image-width maxwidth' );
		}
	}

	protected function setAbsoluteFilesystemPath() {
		if ( !$this->isAllowed ) {
			$this->sAbsoluteFilesystemPath = $this->accessDeniedImage();
			return;
		}
		if ( $this->oFileObject instanceof File && $this->oFileObject->exists() ) {
			$oFileRepoLocalRef = $this->oFileObject->getRepo()->getLocalReference( $this->oFileObject->getPath() );
			if ( $oFileRepoLocalRef !== null ) {
				$this->sAbsoluteFilesystemPath = $oFileRepoLocalRef->getPath();
			}
			$this->sSourceFileName = $this->oFileObject->getName();

			$width = $this->oFileObject->getWidth();
			if ( $this->oFileObject->isVectorized() && $width !== false ) {
				$transform = $this->oFileObject->transform(
					[
						'width' => $width
					],
					File::RENDER_NOW
				);
				$storagePath = $transform->getStoragePath();
				// Main file that this is thumb of
				$file = $transform->getFile();

				$backend = $file->getRepo()->getBackend();
				$fsFile = $backend->getLocalReference( [ 'src' => $storagePath ] );
				if ( $fsFile ) {
					$this->sAbsoluteFilesystemPath = $fsFile->getPath();
				} else {
					$this->sAbsoluteFilesystemPath = $transform->getLocalCopyPath();
				}

				$this->sSourceFileName = wfBaseName( $this->sAbsoluteFilesystemPath );
			}
		} else {
			$uploadDirectory = $this->mainConfig->get( 'UploadDirectory' );
			$this->sAbsoluteFilesystemPath = $this->getFileSystemPath( $uploadDirectory . $this->sSourceFilePath );
		}
	}

	protected function setFileName() {
		if ( !$this->isAllowed ) {
			$this->sFileName = 'bs_ue_module_pdf_access_denied.png';
			return;
		}
		$this->sFileName = $this->sSourceFileName;
		if ( !empty( $this->sAbsoluteFilesystemPath ) && $this->oFileObject instanceof File ) {
			$this->sFileName = $this->oFileObject->getName();
		}
	}

	protected function setSourceAttributes() {
		$this->oImgNode->setAttribute( 'data-orig-src', $this->oImgNode->getAttribute( 'src' ) );
		$this->oImgNode->setAttribute( 'src', 'images/' . urlencode( $this->sSourceFileName ) );
	}

	public function getAbsoluteFilesystemPath() {
		return $this->sAbsoluteFilesystemPath;
	}

	public function getFileName() {
		return $this->sFileName;
	}

	/**
	 * This helper method resolves the local file system path of a found file
	 * @param string $sUrl
	 * @return string The local file system path
	 */
	protected function getFileSystemPath( $sUrl ) {
		if ( substr( $sUrl, 0, 1 ) !== '/' || strpos( $sUrl, $this->sWebrootFileSystemPath ) === 0 ) {
			return $sUrl; // not relative to webroot or absolute filesystempath
		}

		$sScriptUrlDir = dirname( $_SERVER['SCRIPT_NAME'] );
		$sScriptFSDir  = str_replace( '\\', '/', dirname( $_SERVER['SCRIPT_FILENAME'] ) );
		if ( strpos( $sScriptFSDir, $sScriptUrlDir ) == 0 ) { // detect virtual path (webserver setting)
			$sUrl = '/' . substr( $sUrl, strlen( $sScriptUrlDir ) );
		}

		$sNewUrl = $this->sWebrootFileSystemPath . $sUrl; // TODO RBV (08.02.11 15:56): What about $wgUploadDirectory?
		return $sNewUrl;
	}

	private function checkPermission() {
		if ( !$this->oFileTitle ) {
			return false;
		}
		$this->isAllowed =
			$this->permissionManager->userCan( 'read', $this->user, $this->oFileTitle );
	}

	/**
	 * @return string
	 */
	private function accessDeniedImage() {
		return $this->mainConfig->get( 'ExtensionDirectory' )
			. "/BlueSpiceFoundation/resources/assets/ue-module-pdf/access_denied.png";
	}

}

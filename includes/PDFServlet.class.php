<?php
/**
 * BsPDFServlet.
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 *
 * @package    BlueSpiceUEModulePDF
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

use BlueSpice\UEModulePDF\PDFServletHookRunner;
use GuzzleHttp\Client;
use MediaWiki\MediaWikiServices;

/**
 * UniversalExport BsPDFServlet class.
 * @package BlueSpiceUEModulePDF
 */
class BsPDFServlet {

	/**
	 * Gets a DOMDocument, searches it for files, uploads files and markus to webservice and generated PDF.
	 * @param DOMDocument &$oHtmlDOM The source markup
	 * @return string The resulting PDF as bytes
	 */
	public function createPDF( &$oHtmlDOM ) {
		$this->moveStyleElementsToHead( $oHtmlDOM );
		$this->findFiles( $oHtmlDOM );
		$this->uploadFiles();

		// HINT: http://www.php.net/manual/en/class.domdocument.php#96055
		// But: Formated Output is evil because is will destroy formatting in <pre> Tags!
		$sHtmlDOM = $oHtmlDOM->saveXML( $oHtmlDOM->documentElement );

		// Save temporary
		$status = \BsFileSystemHelper::ensureCacheDirectory( 'UEModulePDF' );
		if ( !$status->isOK() ) {
			throw new \MWException( $status->getMessage() );
		}
		$sTmpHtmlFile = "{$status->getValue()}/{$this->aParams['document-token']}.html";
		$sTmpPDFFile  = "{$status->getValue()}/{$this->aParams['document-token']}.pdf";
		file_put_contents( $sTmpHtmlFile, $sHtmlDOM );

		$postData = [
			'fileType' => '', // Need to stay empty so UploadAsset servlet saves file to document root directory
			'documentToken' => $this->aParams['document-token'],
			'sourceHtmlFile_name' => basename( $sTmpHtmlFile ),
			'sourceHtmlFile' => $sTmpHtmlFile,
			'wikiId' => WikiMap::getCurrentWikiId(),
		];

		$config = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'bsg' );

		if ( $config->get( 'TestMode' ) ) {
			$postData['debug'] = "true";
		}

		$requestOptions = $GLOBALS['bsgUEModulePDFRequestOptions'] ?? [];
		$options = array_merge(
			[
				'postData' => $postData
			],
			$requestOptions
		);
		$this->hookRunner->onBSUEModulePDFCreatePDFBeforeSend(
			$this,
			$options,
			$oHtmlDOM
		);

		$multipartPostData = $this->convertToMultipart( $postData );
		unset( $postData['sourceHtmlFile'] );
		unset( $postData['fileType'] );
		// We do not want the request to be multipart/form-data because that's more difficult to handle on Servlet-side
		$renderData = wfArrayToCgi( $postData );

		// Upload HTML source
		$this->request(
			$this->aParams['soap-service-url'] . '/UploadAsset', $multipartPostData
		);

		// Render PDF
		$vPdfByteArray = $this->request(
			$this->aParams['soap-service-url'] . '/RenderPDF', [ 'body' => $renderData ], [
				'Content-Type' => 'application/x-www-form-urlencoded'
			]
		);

		if ( !$vPdfByteArray ) {
			wfDebugLog(
				'BS::UEModulePDF',
				'BsPDFServlet::createPDF: Failed creating "' . $this->aParams['document-token'] . '"'
			);
		}

		file_put_contents( $sTmpPDFFile, $vPdfByteArray );

		// Remove temporary file
		if ( !$config->get( 'TestMode' ) ) {
			unlink( $sTmpHtmlFile );
			unlink( $sTmpPDFFile );
		}

		return $vPdfByteArray;
	}

	/**
	 * Uploads all files found in the markup by the "findFiles" method.
	 */
	protected function uploadFiles() {
		global $bsgUEModulePDFUploadThreshold;

		foreach ( $this->aFiles as $sType => $aFiles ) {
			// Backwards compatibility to old inconistent PDFTemplates (having
			// "STYLESHEET" as type but linking to "stylesheets")
			// TODO: Make conditional?
			if ( $sType == 'IMAGE' ) {
				$sType = 'images';
			}
			if ( $sType == 'STYLESHEET' ) {
				$sType = 'stylesheets';
			}

			$postData = [
				'multipart' => [
					[
						'name' => 'fileType',
						'contents' => $sType,
					],
					[
						'name' => 'documentToken',
						'contents' => $this->aParams['document-token'],
					],
					[
						'name' => 'wikiId',
						'contents' => WikiMap::getCurrentWikiId(),
					],
				],
			];

			$aErrors = [];
			$iCurrentUploadSize = 0;
			foreach ( $aFiles as $sFileName => $sFilePath ) {
				if ( !file_exists( $sFilePath ) ) {
					$aErrors[] = $sFilePath;
					continue;
				}

				$iFileSize = filesize( $sFilePath );
				$iCurrentUploadSize += $iFileSize;
				if ( $iCurrentUploadSize >= $bsgUEModulePDFUploadThreshold ) {
					$this->doFilesUpload( $postData, $aErrors );

					// Reset all loop variables
					$aErrors = [];
					$iCurrentUploadSize = $iFileSize;
				}

				$fieldname = md5( $sFileName );

				// 'myfile.css' => {file_contents}
				// 'myfile.css_name' => 'myfile.css'
				$postData['multipart'][] = [
					'name' => $fieldname,
					'contents' => file_get_contents( $sFilePath ),
					'filename' => $sFileName
				];
				$postData['multipart'][] = [
					'name' => "{$fieldname}_name",
					'contents' => $sFileName
				];
			}
			$this->doFilesUpload( $postData, $aErrors ); // For last iteration contents
		}
	}

	/**
	 *
	 * @var array
	 */
	protected $aParams = [];

	/**
	 *
	 * @var array
	 */
	protected $aFiles = [];

	/**
	 *
	 * @var PDFServletHookRunner
	 */
	protected $hookRunner = null;

	/**
	 * The contructor method forthis class.
	 * @param array &$aParams The params have to contain the key
	 * 'soap-service-url', with a valid URL to the webservice. They can
	 * contain a key 'soap-connection-options' for the SoapClient constructor
	 * and a key 'resources' with al list of files to upload.
	 * @param PDFServletHookRunner $hookRunner
	 * @throws UnexpectedValueException If 'soap-service-url' is not set or the Webservice is not available.
	 */
	public function __construct( &$aParams, PDFServletHookRunner $hookRunner ) {
		$this->aParams = $aParams;
		$this->aFiles = $aParams['resources'];
		$this->hookRunner = $hookRunner;

		if ( empty( $this->aParams['soap-service-url'] ) ) {
			throw new UnexpectedValueException( 'soap-service-url-not-set' );
		}

		if ( !BsConnectionHelper::urlExists( $this->aParams['soap-service-url'] ) ) {
			throw new UnexpectedValueException( 'soap-service-url-not-valid' );
		}

		// If a slash is last char, remove it.
		if ( substr( $this->aParams['soap-service-url'], -1 ) == '/' ) {
			$this->aParams['soap-service-url'] = substr( $this->aParams['soap-service-url'], 0, -1 );
		}
	}

	/**
	 * @param array $postData
	 * @return array|null
	 */
	public function uploadExternal( $postData ) {
		$postData = array_merge( [
			'documentToken' => $this->aParams['document-token'],
			'wikiId'        => WikiMap::getCurrentWikiId()
		], $postData );

		return $this->doFilesUpload( $postData );
	}

	/**
	 * Searches the DOM for <img>-Tags and <a> Tags with class 'internal',
	 * resolves the local filesystem path and adds it to $aFiles array.
	 * @param DOMDocument &$oHtml The markup to be searched.
	 * @return bool Well, always true.
	 */
	protected function findFiles( &$oHtml ) {
		// Find all images
		$oImageElements = $oHtml->getElementsByTagName( 'img' );
		foreach ( $oImageElements as $oImageElement ) {
			$oFileResolver = PDFFileResolver::factory( $oImageElement, $this->aParams['webroot-filesystempath'] );

			$sFileName = $oFileResolver->getFileName();
			$sAbsoluteFileSystemPath = $oFileResolver->getAbsoluteFilesystemPath();
			$this->hookRunner->onBSUEModulePDFFindFiles(
				$this,
				$oImageElement,
				$sAbsoluteFileSystemPath,
				$sFileName,
				'images'
			);
			$this->aFiles['images'][$sFileName] = $sAbsoluteFileSystemPath;
		}

		$oDOMXPath = new DOMXPath( $oHtml );

		$this->hookRunner->onBSUEModulePDFAfterFindFiles(
			$this,
			$oHtml,
			$this->aFiles,
			$this->aParams,
			$oDOMXPath
		);
		return true;
	}

	/**
	 * @param array $aPostData
	 * @param array|null $aErrors
	 * @return array|null
	 */
	protected function doFilesUpload( $aPostData, $aErrors = [] ) {
		$sType = null;
		foreach ( $aPostData['multipart'] as $aFile ) {
			if ( $aFile['name'] === 'fileType' ) {
				$sType = $aFile['contents'];
				break;
			}
		}

		if ( !empty( $aErrors ) ) {
			wfDebugLog(
				'BS::UEModulePDF',
				'BsPDFServlet::uploadFiles: Error trying to fetch files:' . "\n" . var_export( $aErrors, true )
			);
		}

		$this->hookRunner->onBSUEModulePDFUploadFilesBeforeSend(
			$this,
			$aPostData,
			$sType
		);

		$response = $this->request(
			$this->aParams['soap-service-url'] . '/UploadAsset', $aPostData
		);

		if ( $response !== null ) {
			wfDebugLog(
				'BS::UEModulePDF',
				'BsPDFServlet::uploadFiles: Successfully added "' . $sType . '"'
			);
			$decoded = FormatJson::decode( $response, 1 );
			wfDebugLog( 'BS::UEModulePDF', FormatJson::encode( $decoded, 1 ) );

			return $decoded;
		} else {
			wfDebugLog(
				'BS::UEModulePDF',
				'BsPDFServlet::uploadFiles: Failed adding "' . $sType . '"'
			);
		}

		return null;
	}

	/**
	 * The extensions "TemplateStyles" adds `<style>` elements directly into the `#content` of a
	 * page. The PDF renderer needs them to be in the `<head>`
	 * @param DOMDocument $oHtmlDOM
	 */
	private function moveStyleElementsToHead( $oHtmlDOM ) {
		$headEl = $oHtmlDOM->getElementsByTagName( 'head' )->item( 0 );
		$bodyEl = $oHtmlDOM->getElementsByTagName( 'body' )->item( 0 );
		$styleElsInBody = $bodyEl->getElementsByTagName( 'style' );
		$nonLiveListOfStyleElsInBody = [];
		foreach ( $styleElsInBody as $styleEl ) {
			$nonLiveListOfStyleElsInBody[] = $styleEl;
		}
		foreach ( $nonLiveListOfStyleElsInBody as $styleEl ) {
			$headEl->appendChild( $styleEl );
		}
	}

	/**
	 * @param array $postData in form_params format
	 *
	 * @return array
	 */
	private function convertToMultipart( $postData ): array {
		$multipart = [];
		foreach ( $postData as $postItemKey => $postItem ) {
			if ( $postItemKey === 'multipart' ) {
				return $postData;
			}
			if ( $postItemKey === 'sourceHtmlFile' ) {
				$multipart[] = [
					'name' => $postItemKey,
					'filename' => basename( $postItem ),
					'contents' => file_get_contents( $postItem ),
				];
				continue;
			}
			$multipart[] = [ 'name' => $postItemKey, 'contents' => $postItem ];
		}
		return [ 'multipart' => $multipart ];
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @param array|null $headers
	 *
	 * @return string
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function request( string $url, array $options, ?array $headers = [] ): string {
		$config = array_merge( [
			'headers' => $headers,
			'timeout' => 120
		], $GLOBALS['bsgUEModulePDFRequestOptions'] );
		$config['headers']['User-Agent'] = MediaWikiServices::getInstance()->getHttpRequestFactory()->getUserAgent();

		// Create client manually, since calling `createGuzzleClient` on httpFactory will throw a fatal
		// complaining `$this->options` is NULL. Which should not happen, but I cannot find why it happens
		$client = new Client( $config );
		$response = $client->request( 'POST', $url, $options );
		return $response->getBody()->getContents();
	}
}

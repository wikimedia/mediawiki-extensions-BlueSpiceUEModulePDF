<?php

namespace BlueSpice\UEModulePDF\PdfFileResolver;

use PDFFileResolver;

class StripSpecialCharsResolver extends PDFFileResolver {

	protected $substitutionMap = [
		'ä' => 'ae',
		'ö' => 'oe',
		'ü' => 'ue',
		'Ä' => 'Ae',
		'Ö' => 'Oe',
		'Ü' => 'Ue',
		'ß' => 'ss',
		'á' => 'a',
		'ć' => 'c',
		'é' => 'e',
		'í' => 'i',
		'ó' => 'o',
		'ú' => 'u',
		'Á' => 'A',
		'Ć' => 'C',
		'É' => 'E',
		'Í' => 'I',
		'Ú' => 'U',
		'Ó' => 'O',
		'Ź' => 'Z',
		'ẃ' => 'w',
		'ŕ' => 'r',
		'ź' => 'z',
		'ṕ' => 'p',
		'ś' => 's',
		'ǵ' => 'g',
		'ĺ' => 'l',
		'ý' => 'y',
		'ǘ' => 'ue',
		'ń' => 'n',
		'ḿ' => 'm',
		'Ẃ' => 'W',
		'Ŕ' => 'R',
		'Ṕ' => 'P',
		'Ś' => 'S',
		'Ǵ' => 'G',
		'Ḱ' => 'K',
		'Ĺ' => 'L',
		'Ý' => 'Y',
		'Ǘ' => 'Ue',
		'Ń' => 'N',
		'Ḿ' => 'M'
	];

	/**
	 * Does what standard PDF file resolver does, but with stripping special chars
	 */
	protected function setAbsoluteFilesystemPath() {
		parent::setAbsoluteFilesystemPath();

		$isUmlaut = false;

		foreach ( $this->substitutionMap as $nonAsciiChar => $asciiCharReplacement ) {
			if ( strpos( $this->sSourceFileName, $nonAsciiChar ) !== false ) {
				$isUmlaut = true;
				break;
			}
		}

		if ( $isUmlaut ) {
			$newFilename = $this->sSourceFileName;
			foreach ( $this->substitutionMap as $nonAsciiChar => $asciiCharReplacement ) {
				$newFilename = str_replace(
					$nonAsciiChar,
					$asciiCharReplacement,
					$newFilename
				);
			}

			$uploadDirectory = $this->mainConfig->get( 'UploadDirectory' );

			// We can use dedicated directory for "pdf files"
			// Then we will be able to do a clean-up after uploading files
			$newPathDir = "$uploadDirectory/cache/pdf_files";
			wfMkdirParents( $newPathDir );

			$newPath = "$newPathDir/$newFilename";

			copy( $this->sAbsoluteFilesystemPath, $newPath );

			$this->sSourceFileName = $newFilename;
			$this->sAbsoluteFilesystemPath = $newPath;
		}
	}
}

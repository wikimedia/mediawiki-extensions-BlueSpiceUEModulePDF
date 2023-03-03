<?php

namespace BlueSpice\UEModulePDF\Hook;

use DOMXPath;
use Title;

interface BSUEModulePDFgetPageHook {
	/**
	 *
	 * @param Title $title
	 * @param array &$page
	 * @param array &$params
	 * @param DOMXPath $DOMXPath
	 *
	 * @return void
	 */
	public function onBSUEModulePDFgetPage( Title $title, array &$page, array &$params, DOMXPath $DOMXPath ): void;
}

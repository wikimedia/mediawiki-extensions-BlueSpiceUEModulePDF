<?php

namespace BlueSpice\UEModulePDF\Hook;

interface BSUEModulePDFbeforeGetPageHook {
	/**
	 *
	 * @param array &$params
	 *
	 * @return void
	 */
	public function onBSUEModulePDFbeforeGetPage( &$params ): void;
}

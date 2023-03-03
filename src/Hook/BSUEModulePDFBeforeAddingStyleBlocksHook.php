<?php

namespace BlueSpice\UEModulePDF\Hook;

interface BSUEModulePDFBeforeAddingStyleBlocksHook {
	/**
	 *
	 * @param array &$template
	 * @param array &$styleBlocks
	 *
	 * @return void
	 */
	public function onBSUEModulePDFBeforeAddingStyleBlocks( array &$template, array &$styleBlocks ): void;
}

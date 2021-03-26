<?php
/**
 * Hook handler base class for BlueSpice hook BSUEModulePDFBeforeAddingContent
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://bluespice.com
 *
 * @author     Patric Wirth
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2020 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */
namespace BlueSpice\UEModulePDF\Hook;

use BlueSpice\Hook;
use BlueSpice\UniversalExport\IExportModule;
use Config;
use IContextSource;

abstract class BSUEModulePDFBeforeAddingContent extends Hook {

	/**
	 *
	 * @var array
	 */
	protected $template = null;

	/**
	 *
	 * @var array
	 */
	protected $contents = null;

	/**
	 *
	 * @var IExportModule
	 */
	protected $caller = null;

	/**
	 *
	 * @var array
	 */
	protected $page = null;

	/**
	 *
	 * @param array &$template
	 * @param array &$contents
	 * @param IExportModule $caller
	 * @param array &$page
	 * @return bool
	 */
	public static function callback( &$template, &$contents, $caller, &$page ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$template,
			$contents,
			$caller,
			$page
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array &$template
	 * @param array &$contents
	 * @param IExportModule $caller
	 * @param array &$page
	 */
	public function __construct( $context, $config, &$template, &$contents, $caller, &$page ) {
		parent::__construct( $context, $config );

		$this->template =& $template;
		$this->contents =& $contents;
		$this->caller = $caller;
		$this->page =& $page;
	}
}
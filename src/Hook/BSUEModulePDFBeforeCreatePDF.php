<?php

/**
 * Hook handler base class for BlueSpice hook BSUEModulePDFBeforeCreatePDF
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
 * For further information visit http://bluespice.com
 *
 * @author     Patric Wirth
 * @package    BlueSpiceUEModulePDF
 * @copyright  Copyright (C) 2020 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */
namespace BlueSpice\UEModulePDF\Hook;

use BlueSpice\Hook;
use BlueSpice\UniversalExport\ExportSpecification;
use BsUniversalExportModule;
use DOMDocument;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;

/**
 * Description of BSUEModulePDFBeforeCreatePDF
 */
abstract class BSUEModulePDFBeforeCreatePDF extends Hook {
	/**
	 *
	 * @var BsUniversalExportModule
	 */
	protected $module = null;

	/**
	 *
	 * @var DOMDocument
	 */
	protected $DOM = null;

	/**
	 *
	 * @var ExportSpecification
	 */
	protected $specs = null;

	/**
	 *
	 * @param BsUniversalExportModule $module
	 * @param array $DOM
	 * @param ExportSpecification $specs
	 * @return bool
	 */
	public static function callback( $module, $DOM, $specs ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$module,
			$DOM,
			$specs
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param IContextSource $context
	 * @param Config $config
	 * @param BsUniversalExportModule $module
	 * @param array $DOM
	 * @param ExportSpecification $specs
	 */
	public function __construct( $context, $config, $module, $DOM, $specs ) {
		parent::__construct( $context, $config );

		$this->module = $module;
		$this->DOM = $DOM;
		$this->specs = $specs;
	}
}

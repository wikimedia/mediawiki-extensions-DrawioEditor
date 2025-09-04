<?php

namespace MediaWiki\Extension\DrawioEditor\ConfigDefinition;

use BlueSpice\ConfigDefinition\ArraySetting;
use BlueSpice\ConfigDefinition\IOverwriteGlobal;
use HTMLMultiSelectPlusAdd;

class CustomShapeLibraries extends ArraySetting implements IOverwriteGlobal {

	/** @inheritDoc */
	public function getPaths() {
		$feature = static::FEATURE_EDITOR;
		$ext = 'DrawioEditor';
		$package = static::PACKAGE_PRO;
		return [
			static::MAIN_PATH_FEATURE . "/$feature/$ext",
			static::MAIN_PATH_EXTENSION . "/$ext/$feature",
			static::MAIN_PATH_PACKAGE . "/$package/$ext",
		];
	}

	/**
	 * @return HTMLMultiSelectPlusAdd
	 */
	public function getHtmlFormField() {
		return new HTMLMultiSelectPlusAdd( $this->makeFormFieldParams() );
	}

	/** @inheritDoc */
	public function getGlobalName() {
		return "wgDrawioEditorCustomShapeLibraries";
	}

	/** @inheritDoc */
	public function getLabelMessageKey() {
		return 'drawioeditor-config-customshapelibraries';
	}

	/** @inheritDoc */
	public function getHelpMessageKey() {
		return 'drawioeditor-config-customshapelibraries-help';
	}

}

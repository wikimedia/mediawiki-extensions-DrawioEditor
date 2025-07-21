<?php

namespace MediaWiki\Extension\DrawioEditor\ConfigDefinition;

use BlueSpice\ConfigDefinition\IOverwriteGlobal;
use BlueSpice\ConfigDefinition\StringSetting;

class BackendUrl extends StringSetting implements IOverwriteGlobal {

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

	/** @inheritDoc */
	public function getGlobalName() {
		return "wgDrawioEditorBackendUrl";
	}

	/** @inheritDoc */
	public function getLabelMessageKey() {
		return 'drawioeditor-config-backendurl';
	}

	/** @inheritDoc */
	public function getHelpMessageKey() {
		return 'drawioeditor-config-backendurl-help';
	}

}

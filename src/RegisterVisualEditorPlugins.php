<?php

namespace MediaWiki\Extension\DrawioEditor;

use MediaWiki\Registration\ExtensionRegistry;

class RegisterVisualEditorPlugins {
	public static function execute() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'VisualEditorPlus' ) ) {
			$GLOBALS['wgVisualEditorPluginModules'][] = 'ext.drawio.tag.definition';
		}
	}
}

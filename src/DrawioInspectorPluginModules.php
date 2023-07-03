<?php

namespace MediaWiki\Extension\DrawioEditor;

use MWStake\MediaWiki\Component\ManifestRegistry\ManifestAttributeBasedRegistry;

class DrawioInspectorPluginModules {

	/**
	 * @return array
	 */
	public static function getPluginModules() {
		$registry = new ManifestAttributeBasedRegistry(
			'DrawioEditorInspectorPluginModules'
		);

		$pluginModules = [];
		foreach ( $registry->getAllKeys() as $key ) {
			$moduleName = $registry->getValue( $key );
			$pluginModules[] = $moduleName;
		}

		return $pluginModules;
	}
}

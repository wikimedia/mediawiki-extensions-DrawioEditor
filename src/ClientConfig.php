<?php

namespace MediaWiki\Extension\DrawioEditor;

use MediaWiki\MediaWikiServices;

class ClientConfig {

	/**
	 * Prepare the clibs query parameter.
	 * URLs must be rawurlencoded, prefixed with 'U', separated by ';'.
	 *
	 * @return array
	 */
	public static function getCustomShapeLibraries() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$customShapeLibraries = $config->get( 'DrawioEditorCustomShapeLibraries' );

		$encoded = array_map(
			static function ( $url ) {
				return 'U' . rawurlencode( $url );
			},
			$customShapeLibraries
		);

		return [
			'customShapeLibraries' => implode( ';', $encoded )
		];
	}
}

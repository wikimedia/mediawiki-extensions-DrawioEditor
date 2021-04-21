<?php

use MediaWiki\Extension\DrawioEditor\DrawioEditor as DrawioEditorNew;

/**
 * For B/C
 */
class DrawioEditor {

	/**
	 * @inheritDoc
	 */
	public static function parse( &$parser, $name = null, $opts = [] ) {
		$newHandler = new DrawioEditorNew();
		return $newHandler->parse( $parser, $name, $opts );
	}
}

<?php

namespace MediaWiki\Extension\DrawioEditor\Hook\ParserFirstCallInit;

use MediaWiki\Extension\DrawioEditor\DrawioEditor;
use MWException;
use Parser;

class SetFunctionHook {

	/**
	 * @param Parser &$parser
	 * @return bool
	 * @throws MWException
	 */
	public static function callback( &$parser ) {
		$drawioEditor = new DrawioEditor();
		$parser->setFunctionHook( 'drawio', [ $drawioEditor, 'parse' ] );

		return true;
	}
}

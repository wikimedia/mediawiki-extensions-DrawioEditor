<?php

namespace MediaWiki\Extension\DrawioEditor\Hook\ParserFirstCallInit;

use Exception;
use MediaWiki\Extension\DrawioEditor\DrawioEditor;
use MediaWiki\Parser\Parser;

class SetFunctionHook {

	/**
	 * @param Parser &$parser
	 * @return bool
	 * @throws Exception
	 */
	public static function callback( &$parser ) {
		$drawioEditor = new DrawioEditor();

		// Add hook for Legacy Parser Function {{#drawio:filename|param=...}}
		$parser->setFunctionHook( 'drawio', [ $drawioEditor, 'parseLegacyParserFunc' ] );

		// Add hook for Tag Extension; <drawio filename=filename param=..../>
		$parser->setHook( 'drawio', [ $drawioEditor, 'parseExtension' ] );

		return true;
	}
}

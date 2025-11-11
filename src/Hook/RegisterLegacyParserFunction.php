<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use MediaWiki\Extension\DrawioEditor\DrawioEditor;
use MediaWiki\Hook\ParserFirstCallInitHook;

class RegisterLegacyParserFunction implements ParserFirstCallInitHook {

	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ) {
		$drawioEditor = new DrawioEditor();
		// Add hook for Legacy Parser Function {{#drawio:filename|param=...}}
		$parser->setFunctionHook( 'drawio', [ $drawioEditor, 'parseLegacyParserFunc' ] );
	}
}

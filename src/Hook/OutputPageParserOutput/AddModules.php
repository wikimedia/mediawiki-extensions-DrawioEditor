<?php

namespace MediaWiki\Extension\DrawioEditor\Hook\OutputPageParserOutput;

use OutputPage;
use ParserOutput;

class AddModules {

	/**
	 * @param OutputPage &$outputPage
	 * @param ParserOutput $parseroutput
	 * @return bool
	 */
	public static function callback( &$outputPage, $parseroutput ) {
		$outputPage->addModules( 'ext.drawioeditor' );

		return true;
	}
}

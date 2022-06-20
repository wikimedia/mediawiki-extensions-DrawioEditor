<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use Html;
use MediaWiki\Extension\DrawioEditor\DrawioEditor;
use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;
use PPFrame;

class Tag implements ParserFirstCallInitHook {
	public const NAME = 'drawio';
	public const BS_NAME = 'bs:drawio';

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( static::NAME, [ $this, 'onDrawIo' ] );
		$parser->setHook( static::BS_NAME, [ $this, 'onDrawIo' ] );
	}

	/**
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return void
	 */
	public function onDrawIo( ?string $input, array $args, Parser $parser,
		PPFrame $frame ) {
		$drawioEditor = new DrawioEditor();
		$magicWordData = $drawioEditor->parse( $parser, $args[ 'filename' ], $args );
		$parser->getOutput()->setProperty( 'drawio-image', $args[ 'filename' ] );
		$parser->getOutput()->addModules( [ 'ext.drawioconnector.init' ] );

		$out = Html::element( 'div', [
			'class' => 'drawio'
		] );
		$out .= $magicWordData[0];
		$out .= Html::closeElement( 'div' );
		return $out;
	}

}

<?php

namespace MediaWiki\Extension\DrawioEditor\Tag;

use MediaWiki\Extension\DrawioEditor\DrawioEditor;
use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

class DrawioTagHandler implements ITagHandler {

	public function getRenderedContent( string $input, array $params, Parser $parser, PPFrame $frame ): string {
		$drawioEditor = new DrawioEditor();
		$magicWordData = $drawioEditor->parse( $parser, $params[ 'filename' ], $params );
		$parser->getOutput()->setPageProperty( 'drawio-image', $params[ 'filename' ] );

		$out = Html::element( 'div', [
			'class' => 'drawio'
		] );
		$out .= $magicWordData[0];
		$out .= Html::closeElement( 'div' );
		return $out;
	}
}

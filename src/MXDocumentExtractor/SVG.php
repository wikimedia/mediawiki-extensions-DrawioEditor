<?php

namespace MediaWiki\Extension\DrawioEditor\MXDocumentExtractor;

use DOMDocument;

class SVG extends Base {
	/**
	 * @inheritDoc
	 */
	protected function getPlainMXFileString(): string {
		$svgDOM = new DOMDocument();
		$svgDOM->loadXML( $this->imageContent );
		$mxfileXML = $svgDOM->documentElement->getAttribute( 'content' );
		return $mxfileXML;
	}
}

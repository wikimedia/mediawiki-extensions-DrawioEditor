<?php

namespace MediaWiki\Extension\DrawioEditor\MXDocumentExtractor;

class PNG extends Base {

	/**
	 * @inheritDoc
	 */
	protected function getPlainMXFileString(): string {
		$encodedXML = preg_replace(
			'#^.*?tEXt(.*?)IDAT.*?$#s',
			'$1',
			$this->imageContent
		);
		$partiallyDecodedXML = urldecode( $encodedXML );
		$matches = [];
		preg_match( '#<mxfile.*?>(.*?)</mxfile>#s', $partiallyDecodedXML, $matches );
		$strippedXML = $matches[0];
		return trim( $strippedXML );
	}
}

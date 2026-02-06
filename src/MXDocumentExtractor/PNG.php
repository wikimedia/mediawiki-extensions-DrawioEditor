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
		$encodedXML = preg_replace( '/[[:^print:]]/', '', $encodedXML );
		$partiallyDecodedXML = urldecode( $encodedXML );
		$matches = [];
		preg_match( '#<mxfile.*?>(.*?)</mxfile>#s', $partiallyDecodedXML, $matches );
		if ( empty( $matches ) ) {
			// Extract from zTXt instead of tEXt.
			$type = 'zTXt';
			// The 2 zero bytes are for null-termination of comment (one byte) and encoding type (another byte).
			// See https://w3c.github.io/PNG-spec/#11zTXt
			$comment = 'mxGraphModel' . "\x0\x0";
			$position = strpos( $this->imageContent, $type . $comment );
			if ( $position !== false ) {
				// 4 bytes before chunk type give chunck length.
				// See https://w3c.github.io/PNG-spec/#5Chunk-layout
				$unpacked = unpack( "N", substr( $this->imageContent, $position - 4, 4 ) );
				$chunckLength = $unpacked["1"];
				$data = substr(
					$this->imageContent,
					$position + strlen( $type . $comment ),
					$chunckLength - strlen( $comment )
				);
				// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
				$data = @gzinflate( $data );
				if ( !is_string( $data ) ) {
					$data = '';
				}
				$strippedXML = urldecode( $data );
			} else {
				$strippedXML = '';
			}
		} else {
			$strippedXML = $matches[0];
		}
		return trim( $strippedXML );
	}
}

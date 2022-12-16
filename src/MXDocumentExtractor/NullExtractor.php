<?php

namespace MediaWiki\Extension\DrawioEditor\MXDocumentExtractor;

use DOMDocument;
use MediaWiki\Extension\DrawioEditor\IMXDocumentExtractor;

class NullExtractor implements IMXDocumentExtractor {

	/**
	 * @inheritDoc
	 */
	public function extractMXDocument( $image ): DOMDocument {
		return new DOMDocument();
	}
}

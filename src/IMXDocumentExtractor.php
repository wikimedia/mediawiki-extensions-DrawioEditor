<?php

namespace MediaWiki\Extension\DrawioEditor;

use DOMDocument;
use File;

interface IMXDocumentExtractor {

	/**
	 * @param File $image The image to extract the MX document from.
	 * @return DOMDocument The embedded MX document.
	 */
	public function extractMXDocument( $image ): DOMDocument;
}

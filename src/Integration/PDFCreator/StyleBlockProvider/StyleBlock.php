<?php

namespace MediaWiki\Extension\DrawioEditor\Integration\PDFCreator\StyleBlockProvider;

use MediaWiki\Extension\PDFCreator\IStyleBlocksProvider;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;

class StyleBlock implements IStyleBlocksProvider {

	/**
	 * @param string $module
	 * @param ExportContext $context
	 * @return array
	 */
	public function execute( string $module, ExportContext $context ): array {
		$css = [
			'.mw-editdrawio { display: none; }',
			'[id*="drawio-img-"] { padding-top: 10px; }',
			'img[id^="drawio-img-"] { height: auto; }'
		];

		return [
			'DrawioEditor'  => implode( ' ', $css )
		];
	}
}

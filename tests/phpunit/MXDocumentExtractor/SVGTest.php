<?php

namespace MediaWiki\Extension\DrawioEditor\Tests\MXDocumentExtractor;

use File;
use FileBackend;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\SVG;
use PHPUnit\Framework\TestCase;

class SVGTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\SVG::extractMXDocument
	 */
	public function testExtractMXDocument() {
		$imageContent = file_get_contents( __DIR__ . '/../data/test-1.svg' );
		$fileBackend = $this->createMock( FileBackend::class );
		$fileBackend
			->method( 'getFileContentsMulti' )
			->willReturn( [ '/dummy/file.svg' => $imageContent ] );
		$image = $this->createMock( File::class );
		$image
			->method( 'getPath' )
			->willReturn( '/dummy/file.svg' );

		$generator = new SVG( $fileBackend );
		$actualImageMap = $generator->extractMXDocument( $image );
		$expectedImageMap = file_get_contents( __DIR__ . '/../data/test-1-dxdocument.xml' );
		$this->assertXmlStringEqualsXmlString(
			$expectedImageMap,
			$actualImageMap
		);
	}
}

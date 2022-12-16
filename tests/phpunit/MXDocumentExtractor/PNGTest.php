<?php

namespace MediaWiki\Extension\DrawioEditor\Tests\MXDocumentExtractor;

use File;
use FileBackend;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\PNG;
use PHPUnit\Framework\TestCase;

class PNGTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\PNG::extractMXDocument
	 */
	public function testExtractMXDocument() {
		$imageContent = file_get_contents( __DIR__ . '/../data/test-1.png' );
		$fileBackend = $this->createMock( FileBackend::class );
		$fileBackend
			->method( 'getFileContentsMulti' )
			->willReturn( [ '/dummy/file.png' => $imageContent ] );
		$image = $this->createMock( File::class );
		$image
			->method( 'getPath' )
			->willReturn( '/dummy/file.png' );

		$generator = new PNG( $fileBackend );
		$actualImageMap = $generator->extractMXDocument( $image );
		$expectedImageMap = file_get_contents( __DIR__ . '/../data/test-1-dxdocument.xml' );
		$this->assertXmlStringEqualsXmlString(
			$expectedImageMap,
			$actualImageMap
		);
	}
}

<?php

namespace MediaWiki\Extension\DrawioEditor\Tests\MXDocumentExtractor;

use File;
use FileBackend;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\PNG;
use PHPUnit\Framework\TestCase;

class PNGTest extends TestCase {

	/**
	 * @param string $inputFilename
	 * @param string $expectedFilename
	 * @covers \MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\PNG::extractMXDocument
	 * @dataProvider providerExtractMXDocumentData
	 */
	public function testExtractMXDocument( $inputFilename, $expectedFilename ) {
		$imageContent = file_get_contents( __DIR__ . '/../data/' . $inputFilename );
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
		$expectedImageMap = file_get_contents( __DIR__ . '/../data/' . $expectedFilename );
		$this->assertXmlStringEqualsXmlString(
			$expectedImageMap,
			$actualImageMap->saveXML()
		);
	}

	public static function providerExtractMXDocumentData() {
		return [
			'legacy-compressed-mxdocument' => [
				'file' => 'test-1.png',
				'expected' => 'test-1-dxdocument.xml',
			],
			'new-uncompressed-mxdocument' => [
				'file' => 'test-3.png',
				'expected' => 'test-3-dxdocument.xml',
			],
			'new-uncompressed-mxdocument-with-special-char' => [
				'file' => 'test-4.png',
				'expected' => 'test-4-dxdocument.xml',
			]
		];
	}
}

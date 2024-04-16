<?php

namespace MediaWiki\Extension\DrawioEditor\Tests;

use DOMDocument;
use MediaWiki\Extension\DrawioEditor\ImageMapGenerator;
use PHPUnit\Framework\TestCase;

class ImageMapGeneratorTest extends TestCase {

	/**
	 * @param string $inputXMLFile
	 * @param string $expectedOutputXMLFile
	 * @return void
	 * @covers \MediaWiki\Extension\DrawioEditor\ImageMapGenerator::generateImageMap
	 * @dataProvider provideTestGenerateImageMapData
	 */
	public function testGenerateImageMap( $inputXMLFile, $expectedOutputXMLFile ) {
		$imageMapGenerator = new ImageMapGenerator();
		$inputDOM = new DOMDocument();
		$inputDOM->load( $inputXMLFile );
		$actualImageMap = $imageMapGenerator->generateImageMap( $inputDOM, 'test' );
		$expectedImageMap = file_get_contents( $expectedOutputXMLFile );
		$this->assertXmlStringEqualsXmlString(
			$expectedImageMap,
			$actualImageMap
		);
	}

	/**
	 * @return array
	 */
	public function provideTestGenerateImageMapData() {
		return [
			[
				__DIR__ . '/data/test-1-dxdocument.xml',
				__DIR__ . '/data/test-1.html',
			],
			[
				__DIR__ . '/data/test-2-dxdocument.xml',
				__DIR__ . '/data/test-2.html',
			],
			[
				__DIR__ . '/data/test-3-dxdocument.xml',
				__DIR__ . '/data/test-3.html',
			],
			[
				__DIR__ . '/data/test-4-dxdocument.xml',
				__DIR__ . '/data/test-4.html',
			],
			'Case with standalone arrow' => [
				// That is a case when there are few arbitrary geometric figures with links
				// And also there is a standalone arrow, on the very left of the diagram
				// In that case offset should be calculated considering that arrow
				__DIR__ . '/data/test-5-dxdocument-arrow.xml',
				__DIR__ . '/data/test-5.html',
			],
			'Case with container and nested geometry' => [
				// That is a case when there are few arbitrary geometric figures with links
				// And one of them is nested in the container
				// In that case X and Y position of nested geometry will be relative to the container
				// So this case needs considering parent container's coordinates
				__DIR__ . '/data/test-6-dxdocument-container.xml',
				__DIR__ . '/data/test-6.html',
			],
			'Case with rotated geometry (rectangle)' => [
				__DIR__ . '/data/test-7-dxdocument-rotated-rectangle.xml',
				__DIR__ . '/data/test-7.html',
			]
		];
	}

	/**
	 * @return void
	 * @covers \MediaWiki\Extension\DrawioEditor\ImageMapGenerator::generateImageMap
	 * @dataProvider provideTestGenerateImageMapData
	 */
	public function testGenerateImageMapWithEmptyMXDocument() {
		$imageMapGenerator = new ImageMapGenerator();
		$inputDOM = new DOMDocument();
		$actualImageMap = $imageMapGenerator->generateImageMap( $inputDOM, 'test' );
		$this->assertSame( '', $actualImageMap );
	}
}

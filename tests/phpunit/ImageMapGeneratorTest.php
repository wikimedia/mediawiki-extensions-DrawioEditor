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
	 * @covers \MediaWiki\Extension\DrawioEditor\ImageMapGenerator\ImageMapGenerator::generateImageMap
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
		];
	}

	/**
	 * @return void
	 * @covers \MediaWiki\Extension\DrawioEditor\ImageMapGenerator\ImageMapGenerator::generateImageMap
	 * @dataProvider provideTestGenerateImageMapData
	 */
	public function testGenerateImageMapWithEmptyMXDocument() {
		$imageMapGenerator = new ImageMapGenerator();
		$inputDOM = new DOMDocument();
		$actualImageMap = $imageMapGenerator->generateImageMap( $inputDOM, 'test' );
		$this->assertEmpty( $actualImageMap );
	}
}

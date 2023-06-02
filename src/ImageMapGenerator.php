<?php

namespace MediaWiki\Extension\DrawioEditor;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ImageMapGenerator {

	/** @var DOMDocument */
	private $imageMap = null;

	/** @var int */
	private $offsetX = 0;

	/** @var int */
	private $offsetY = 0;

	/**
	 * @param string $shape
	 * @param string $coords
	 * @param string $href
	 * @param string $targetVal
	 */
	private function addArea( $shape, $coords, $href, $targetVal ) {
		$area = $this->imageMap->createElement( 'area' );
		$area->setAttribute( 'shape', $shape );
		$area->setAttribute( 'coords', $coords );
		$area->setAttribute( 'href', $href );
		if ( $targetVal !== '' ) {
			$area->setAttribute( 'target', $targetVal );
		}
		$this->imageMap->documentElement->appendChild( $area );
	}

	/**
	 * @param DOMDocument $diagramDOM
	 * @param string $name
	 * @return string
	 */
	public function generateImageMap( $diagramDOM, $name ): string {
		$this->imageMap = new DOMDocument();
		$this->imageMap->loadXML( '<map name="' . $name . '"></map>' );

		$xpath = new DOMXPath( $diagramDOM );
		$this->calculateOffsets( $xpath );
		$linkEls = $xpath->query( '//*[@link]' );
		foreach ( $linkEls as $linkEl ) {
			$this->processLinkElement( $linkEl );
		}
		if ( $this->imageMap->documentElement->childNodes->length === 0 ) {
			return '';
		}

		$html = $this->imageMap->saveXML( $this->imageMap->documentElement );
		return $html;
	}

	/**
	 * DrawIO will create an image file without any <padding>
	 * But internaly it stores absolute coordinates in the mxFile.
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function calculateOffsets( $xpath ) {
		$allGeometries = $xpath->query( '//mxGeometry' );
		$xCoords = [];
		$yCoords = [];

		foreach ( $allGeometries as $geometry ) {
			$xCoords[] = $geometry->getAttribute( 'x' );
			$yCoords[] = $geometry->getAttribute( 'y' );
		}
		if ( !empty( $xCoords ) ) {
			$this->offsetX = min( $xCoords );
		}
		if ( !empty( $yCoords ) ) {
			$this->offsetY = min( $yCoords );
		}
	}

	/**
	 * Examples of `$linkEl`:
	 *
	 * <UserObject label="" link="#Main_Page" id="2">
	 *		<mxCell style="rounded=0;whiteSpace=wrap;html=1;" vertex="1" parent="1">
	 *			<mxGeometry x="70" y="300" width="180" height="60" as="geometry"/>
	 *		</mxCell>
	 *	</UserObject>
	 *	<UserObject label="" link="#Special:Version" id="3">
	 *		<mxCell style="ellipse;whiteSpace=wrap;html=1;aspect=fixed;" vertex="1" parent="1">
	 *			<mxGeometry x="170" y="380" width="80" height="80" as="geometry"/>
	 *		</mxCell>
	 *	</UserObject>
	 *	<UserObject label="" link="https://wiki.company.local" id="4">
	 *		<mxCell style="shape=dataStorage;whiteSpace=wrap;html=1;fixedSize=1;" vertex="1" parent="1">
	 *			<mxGeometry x="70" y="380" width="100" height="80" as="geometry"/>
	 *		</mxCell>
	 *	</UserObject>
	 *
	 * @param DOMElement $linkEl
	 * @return void
	 */
	private function processLinkElement( $linkEl ) {
		// TODO: Proper handling of internal and external links
		$linkTarget = $linkEl->getAttribute( 'link' );

		// TODO: This must be more flexible!
		$cellEl = $linkEl->getElementsByTagName( 'mxCell' )->item( 0 );
		if ( $cellEl === null ) {
			return;
		}
		$geometryEl = $cellEl->getElementsByTagName( 'mxGeometry' )->item( 0 );
		if ( $geometryEl === null ) {
			return;
		}

		$x = intval( $geometryEl->getAttribute( 'x' ) ) - intval( $this->offsetX );
		$y = intval( $geometryEl->getAttribute( 'y' ) ) - intval( $this->offsetY );

		$width = $geometryEl->getAttribute( 'width' ) + $x;
		$height = $geometryEl->getAttribute( 'height' ) + $y;

		$href = $linkTarget;
		$shape = 'rect';
		$coords = "$x,$y,$width,$height";

		// We only support `target="_blank"` for now as we don't know about
		// the actual values that can be inside of the `UserObject` element.
		$target = $linkEl->getAttribute( 'linkTarget' );
		$targetVal = '';
		if ( $target === '_blank' ) {
			$targetVal = '_blank';
		}

		$this->addArea( $shape, $coords, $href, $targetVal );
	}
}

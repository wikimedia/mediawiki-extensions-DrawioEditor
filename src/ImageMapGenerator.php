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
	 * Key is "cellId" of cell which can be used as container.
	 * Value is "X" coordinate.
	 *
	 * Used
	 *
	 * @var array
	 */
	private $cellXCoords = [];

	/**
	 * Key is "cellId" of cell which can be used as container.
	 * Value is "Y" coordinate.
	 *
	 * @var array
	 */
	private $cellYCoords = [];

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
	 * But internally it stores absolute coordinates in the mxFile.
	 * @param DOMXPath $xpath
	 * @return void
	 */
	private function calculateOffsets( $xpath ) {
		$allCells = $xpath->query( '//mxCell' );

		$pointXCoords = [];
		$pointYCoords = [];

		foreach ( $allCells as $cellEl ) {
			/** @var DOMElement $cellEl */

			$cellId = $cellEl->getAttribute( 'id' );

			list( $parentX, $parentY ) = $this->getParentContainerCoords( $cellEl );

			// Also consider that element may be rotated
			// Currently we take in account only rotation by 90 degrees
			$isRotated = $this->isRotated( $cellEl );

			// There should be only one geometry in one cell
			/** @var DOMElement $geometry */
			$geometry = $cellEl->getElementsByTagName( 'mxGeometry' )->item( 0 );
			if ( $geometry === null ) {
				continue;
			}

			if ( $isRotated ) {
				// Get geometry dimensions, it is needed for further calculations
				$width = $geometry->getAttribute( 'width' );
				$height = $geometry->getAttribute( 'height' );

				// Get initial geometry's top-left coordinates
				$x1 = $geometry->getAttribute( 'x' );
				$y1 = $geometry->getAttribute( 'y' );

				// At first, we need to find coordinates of geometry center
				// To correctly "rotate" its coordinates
				$x0 = (int)$x1 + ( (int)$width / 2 );
				$y0 = (int)$y1 + ( (int)$height / 2 );

				// Now, assuming coordinates of figure center,
				// calculate figure top-left coordinates after rotation
				$x2 = $x0 - ( (int)$height / 2 );
				$y2 = $y0 - ( (int)$width / 2 );

				// Update coordinates
				$geometry->setAttribute( 'x', $x2 );
				$geometry->setAttribute( 'y', $y2 );

				// If figure is rotated by 90 degrees - we need to swap width and height
				$geometry->setAttribute( 'width', $height );
				$geometry->setAttribute( 'height', $width );
			}

			$x = $geometry->getAttribute( 'x' );
			$y = $geometry->getAttribute( 'y' );

			// If "<mxGeometry>" does not have coordinates - probably that's an arrow or curve
			// In case with arrow it should have two nested "<mxPoint>" elements with coordinates
			// In case with curve there will be just more nested points, but algorithm of processing is the same
			if ( !$x && !$y ) {
				// Probably that's an arrow

				// If that's a curve - then processing is the same.
				// Still, in perfect case points on curve should be calculated in different way.
				// Thing is that points which we get from XML - are "anchor" points for calculating "bezier curve".
				// These "anchor" points are located outside the curve itself,
				// but they can be used to calculate curve coords.
				// But such advanced calculations solve only one edge case -
				// when curve is located on the top right of diagram.
				// So using "anchor" points will break offset calculation
				// (because image is cropped considering curve itself).

				// So, summarizing - currently we do not process curves in other way
				$points = $geometry->getElementsByTagName( 'mxPoint' );
				foreach ( $points as $pointEl ) {
					$pointXCoords[] = intval( $parentX ) + intval( $pointEl->getAttribute( 'x' ) );
					$pointYCoords[] = intval( $parentY ) + intval( $pointEl->getAttribute( 'y' ) );
				}

				continue;
			}

			// If there is "cellId" - current cell potentially could be a container.
			if ( $cellId ) {
				$this->cellXCoords[$cellId] = $parentX + intval( $x );
				$this->cellYCoords[$cellId] = $parentY + intval( $y );
			} else {
				// If there is no "cellId" - then this cell cannot be used as a container.
				// That is the case when cell has a link. Such cells cannot be a container for other cells.

				// Then we can just remember its coordinates as a single point
				$pointXCoords[] = $parentX + intval( $x );
				$pointYCoords[] = $parentY + intval( $y );
			}
		}
		$xCoords = array_merge(
			array_values( $this->cellXCoords ),
			$pointXCoords
		);

		$yCoords = array_merge(
			array_values( $this->cellYCoords ),
			$pointYCoords
		);

		if ( !empty( $xCoords ) ) {
			$this->offsetX = min( $xCoords );
		}
		if ( !empty( $yCoords ) ) {
			$this->offsetY = min( $yCoords );
		}
	}

	/**
	 * Gets coordinates of parent container for specified diagram cell.
	 *
	 * @param DOMElement $cellEl Cell element for which we need parent container coordinates
	 * @return array List with two values, "X" and "Y" parent container coordinates accordingly
	 * 		If there is no parent container (or it's the main diagram container) "X" and "Y" will be <val>0</val>
	 */
	private function getParentContainerCoords( DOMElement $cellEl ): array {
		$parentId = $cellEl->getAttribute( 'parent' );

		// We should also consider case with nested geometry
		// When some geometry is nested into container - its coordinates are relative to container
		// So if we need to calculate absolute coordinates - we need to consider parent cell coordinates
		$parentX = 0;
		$parentY = 0;

		// '1' and '0' are "cellId"-s of root cells, their coords are "0;0" actually
		// But in other case there is some cell used as container, so its coordinates should be considered
		if ( $parentId !== '' && $parentId !== '0' && $parentId !== '1' ) {
			$parentX = $this->cellXCoords[$parentId] ?? 0;
			$parentY = $this->cellYCoords[$parentId] ?? 0;
		}

		return [ $parentX, $parentY ];
	}

	/**
	 * @param DOMElement $cellEl
	 * @return bool
	 */
	private function isRotated( DOMElement $cellEl ): bool {
		$stylesRaw = $cellEl->getAttribute( 'style' );
		if ( $stylesRaw ) {
			$stylesArr = explode( ';', $stylesRaw );
			foreach ( $stylesArr as $style ) {
				if ( strpos( $style, 'rotation' ) === 0 ) {
					$rotationDegrees = explode( '=', $style )[1];

					if ( $rotationDegrees == 90 || $rotationDegrees == 270 ) {
						return true;
					}
				}
			}
		}

		return false;
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
		/** @var DOMElement $cellEl */
		$cellEl = $linkEl->getElementsByTagName( 'mxCell' )->item( 0 );
		if ( $cellEl === null ) {
			return;
		}
		/** @var DOMElement $geometryEl */
		$geometryEl = $cellEl->getElementsByTagName( 'mxGeometry' )->item( 0 );
		if ( $geometryEl === null ) {
			return;
		}

		list( $parentX, $parentY ) = $this->getParentContainerCoords( $cellEl );

		$x = ( $parentX + intval( $geometryEl->getAttribute( 'x' ) ) ) - $this->offsetX;
		$y = ( $parentY + intval( $geometryEl->getAttribute( 'y' ) ) ) - $this->offsetY;

		$width = intval( $geometryEl->getAttribute( 'width' ) ) + $x;
		$height = intval( $geometryEl->getAttribute( 'height' ) ) + $y;

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

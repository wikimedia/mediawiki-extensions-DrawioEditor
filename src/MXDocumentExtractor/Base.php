<?php

namespace MediaWiki\Extension\DrawioEditor\MXDocumentExtractor;

use DOMDocument;
use MediaWiki\Extension\DrawioEditor\IMXDocumentExtractor;

abstract class Base implements IMXDocumentExtractor {

	/** @var string */
	protected $imageContent = '';

	/** @var FileBackend */
	private $fileBackend = null;

	/**
	 * @param FileBackend $fileBackend
	 */
	public function __construct( $fileBackend ) {
		$this->fileBackend = $fileBackend;
	}

	/**
	 * @inheritDoc
	 */
	public function extractMXDocument( $image ): DOMDocument {
		$this->imageContent = $this->fileBackend->getFileContents( [
			'src' => $image->getPath()
		] );

		$mxFileString = $this->getPlainMXFileString();
		$mxFileDOM = $this->getMXFileDOM( $mxFileString );
		$diagramDOM = $this->getDiagramDOM( $mxFileDOM );
		if ( $diagramDOM === null ) {
			return new DOMDocument();
		}
		return $diagramDOM;
	}

	/**
	 * @return string
	 */
	abstract protected function getPlainMXFileString(): string;

	/**
	 * @param string $mxfileXML
	 * @return DOMDocument
	 */
	protected function getMXFileDOM( $mxfileXML ) {
		$mxfileXMLDOM = new DOMDocument();
		$mxfileXMLDOM->loadXML( $mxfileXML );

		return $mxfileXMLDOM;
	}

	/**
	 * Example of `$diagramXmlString`:
	 *
	 * <mxGraphModel dx="422" dy="661" grid="1" gridSize="10" guides="1" tooltips="1" connect="1"
	 * arrows="1" fold="1" page="1" pageScale="1" pageWidth="850" pageHeight="1100" math="0" shadow="0">
	 *		<root>
	 *			<mxCell id="0"/>
	 *			<mxCell id="1" parent="0"/>
	 *			<UserObject label="" link="#Main_Page" id="2">
	 *				<mxCell style="rounded=0;whiteSpace=wrap;html=1;" vertex="1" parent="1">
	 *					<mxGeometry x="70" y="300" width="180" height="60" as="geometry"/>
	 *				</mxCell>
	 *			</UserObject>
	 *			<UserObject label="" link="#Special:Version" id="3">
	 *				<mxCell style="ellipse;whiteSpace=wrap;html=1;aspect=fixed;" vertex="1" parent="1">
	 *					<mxGeometry x="170" y="380" width="80" height="80" as="geometry"/>
	 *				</mxCell>
	 *			</UserObject>
	 *			<UserObject label="" link="https://wiki.company.local" id="4">
	 *				<mxCell style="shape=dataStorage;whiteSpace=wrap;html=1;fixedSize=1;" vertex="1" parent="1">
	 *					<mxGeometry x="70" y="380" width="100" height="80" as="geometry"/>
	 *				</mxCell>
	 *			</UserObject>
	 *		</root>
	 *	</mxGraphModel>
	 *
	 * See also https://drawio-app.com/extracting-the-xml-from-mxfiles/
	 *
	 * @param DOMDocument $mxfileXMLDOM
	 * @return DOMDocument|null
	 */
	protected function getDiagramDOM( $mxfileXMLDOM ) {
		$diagramEl = $mxfileXMLDOM->getElementsByTagName( 'diagram' )->item( 0 );
		if ( $diagramEl === null ) {
			// TODO: Proper handling of invalid XML
			return null;
		}
		// This is a Base64-encoded, gzipped, URL-encoded XML string.
		$b64DiagramXML = $diagramEl->nodeValue;
		$inflatedDiagramXML = base64_decode( $b64DiagramXML );
		$urlencodedDiagramXML = gzinflate( $inflatedDiagramXML );
		$diagramXmlString = urldecode( $urlencodedDiagramXML );
		$documentXML = new DOMDocument();
		$documentXML->loadXML( $diagramXmlString );

		return $documentXML;
	}
}

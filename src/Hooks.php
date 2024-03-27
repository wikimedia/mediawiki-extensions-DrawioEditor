<?php

namespace MediaWiki\Extension\DrawioEditor;

use Html;
use MediaWiki\MediaWikiServices;
use Title;

class Hooks {
	/**
	 *
	 * @param mixed $oPDFServlet
	 * @param mixed $oImageElement
	 * @param string &$sAbsoluteFileSystemPath
	 * @param string &$sFileName
	 * @param string $sDirectory
	 * @return void
	 */
	public static function onBSUEModulePDFFindFiles(
		$oPDFServlet,
		$oImageElement,
		&$sAbsoluteFileSystemPath,
		&$sFileName,
		$sDirectory
	) {
		if ( $sDirectory !== 'images' ) {
			return true;
		}
		if ( strpos( $oImageElement->getAttribute( 'id' ), "drawio-img-" ) !== false ) {
			$style = $oImageElement->getAttribute( 'style' );
			$matches = [];
			preg_match( '#max-width: (\d*?)px;#', $style, $matches );
			if ( $matches[1] > 690 ) {
				$oImageElement->setAttribute( 'style', 'width: 99%' );
			} else {
				$oImageElement->setAttribute( 'style', 'width: ' . $matches[1] . 'px' );
			}
		}
		return true;
	}

	/**
	 * Embeds CSS into pdf export
	 *
	 * @param array &$aTemplate
	 * @param array &$aStyleBlocks
	 * @return bool Always true to keep hook running
	 */
	public static function onBSUEModulePDFBeforeAddingStyleBlocks( &$aTemplate, &$aStyleBlocks ) {
		$css = [
			".bs-page-content .mw-editdrawio { display: none; } ",
			'[id^="drawio-img-"] { padding-top: 10px; }',
			'img[id^="drawio-img-"] { height: auto; }'
		];

		$aStyleBlocks['Drawio'] = implode( ' ', $css );

		return true;
	}

	/**
	 *
	 * @param mixed $oImagePage
	 * @param string &$sHtml
	 * @return void
	 */
	public static function onImagePageAfterImageLinks( $oImagePage, &$sHtml ) {
		$oTitle = $oImagePage->getTitle();
		$sFileName = $oTitle->getText();
		if ( strpos( $sFileName, '.drawio.' ) === false ) {
			return true;
		}
		// $sFileName = str_replace( '.drawio.' . $wgDrawioEditorImageType, '', $sFileName );
		$sFileName = str_replace( ' ', '_', $sFileName );
		$aConds = [
			"old_text LIKE '%{{#drawio:" . $sFileName . "}}%'",
			"old_text LIKE '%{{#drawio: " . $sFileName . "}}%'",
			"old_text LIKE '%{{#drawio:" . $sFileName . "|%'",
			"old_text LIKE '%{{#drawio: " . $sFileName . "|%'",
		];

		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$oRes = $dbr->select(
				[ 'page', 'revision', 'slots', 'text' ],
				[ 'page_namespace', 'rev_id', 'page_title' ],
				'(' . implode( ' OR ', $aConds ) .
				') AND page_id = rev_page AND rev_id = slot_revision_id AND old_id = slot_content_id',
				__METHOD__
		);

		$aLinks = [];
		$revisionLookup = $services->getRevisionLookup();
		$linkRenderer = $services->getLinkRenderer();
		foreach ( $oRes as $oRow ) {
			$oRevision = $revisionLookup->getRevisionById( $oRow->rev_id );
			if ( $oRevision->isCurrent() ) {
				$title = Title::makeTitle( $oRow->page_namespace, $oRow->page_title );
				$sLink = $linkRenderer->makeLink( $title );
				$oLi = Html::rawElement( 'li', [], $sLink ) . "\n";
				$aLinks[$title->getPrefixedDBkey()] = $oLi;
			}
		}

		$pagePropsRes = $dbr->select(
			'page_props',
			'pp_page',
			[
				'pp_propname' => 'drawio-image',
				'pp_value' => $sFileName
			],
			__METHOD__
		);
		foreach ( $pagePropsRes as $row ) {
			$title = Title::newFromID( $row->pp_page );
			$link = $linkRenderer->makeLink( $title );
			$liEl = Html::rawElement( 'li', [], $link );
			$aLinks[$title->getPrefixedDBkey()] = $liEl;
		}
		ksort( $aLinks );

		$sHtml .= Html::rawElement( 'h2', [], wfMessage( 'drawio-usage' )->plain() );
		$sHtml .= Html::openElement( 'ul' ) . "\n";
		if ( empty( $aLinks ) ) {
			$sHtml .= Html::rawElement( 'p', [], wfMessage( 'drawio-not-used' )->plain() );
		} else {
			$sHtml .= implode( "\n", $aLinks );
		}
		$sHtml .= Html::closeElement( 'ul' );

		return true;
	}
}

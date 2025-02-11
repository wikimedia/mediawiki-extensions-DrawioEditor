<?php

namespace MediaWiki\Extension\DrawioEditor;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\ImagePageAfterImageLinksHook;
use MediaWiki\Title\Title;

class Hooks implements ImagePageAfterImageLinksHook {
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
	 * @inheritDoc
	 */
	public function onImagePageAfterImageLinks( $imagePage, &$html ) {
		$fileName = $imagePage->getFile()->getTitle()->getDBkey();
		$services = MediaWikiServices::getInstance();
		$fileType = $services->getMainConfig()->get( 'DrawioEditorImageType' );
		if ( $fileType && !str_contains( $fileName, $fileType ) ) {
			return;
		}
		$fileName = str_replace( ".$fileType", '', $fileName );

		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->newSelectQueryBuilder()
			->table( 'page_props' )
			->field( 'pp_page' )
			->where( [
				'pp_propname' => 'drawio-image',
				'pp_value' => "[[$fileName]]"
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$links = [];
		$linkRenderer = $services->getLinkRenderer();
		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->pp_page );
			$link = $linkRenderer->makeLink( $title );
			$liEl = Html::rawElement( 'li', [], $link );
			$links[$title->getPrefixedDBkey()] = $liEl;
		}
		ksort( $links );

		if ( empty( $links ) ) {
			return;
		}

		$html .= Html::rawElement( 'h2', [], wfMessage( 'drawio-usage' )->escaped() );
		$html .= Html::openElement( 'ul' ) . "\n";
		$html .= implode( "\n", $links );
		$html .= Html::closeElement( 'ul' );
	}
}

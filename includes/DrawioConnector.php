<?php

use MediaWiki\MediaWikiServices;

class DrawioConnector {
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
			$oImageElement->removeAttribute( 'style' );
		}
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

		$oDBR = wfGetDB( DB_REPLICA );
		$oRes = $oDBR->select(
				[ 'page', 'revision', 'slots', 'text' ],
				[ 'page_namespace', 'rev_id', 'page_title' ],
				'(' . implode( ' OR ', $aConds ) .
				') AND page_id = rev_page AND rev_id = slot_revision_id AND old_id = slot_content_id',
				__METHOD__
		);

		$aLinks = [];
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		foreach ( $oRes as $oRow ) {
			$oRevision = Revision::newFromId( $oRow->rev_id );
			if ( $oRevision->isCurrent() ) {
				$title = Title::makeTitle( $oRow->page_namespace, $oRow->page_title );
				$sLink = $linkRenderer->makeLink( $title );
				$oLi = Html::rawElement( 'li', [], $sLink ) . "\n";
				$aLinks[$title->getPrefixedDBkey()] = $oLi;
			}
		}

		$pagePropsRes = $oDBR->select(
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

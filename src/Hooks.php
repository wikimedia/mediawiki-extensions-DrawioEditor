<?php

namespace MediaWiki\Extension\DrawioEditor;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\ImagePageAfterImageLinksHook;
use MediaWiki\Title\Title;

class Hooks implements ImagePageAfterImageLinksHook {

	/**
	 * @inheritDoc
	 */
	public function onImagePageAfterImageLinks( $imagePage, &$html ) {
		$fileName = $imagePage->getFile()->getTitle()->getDBkey();
		$services = MediaWikiServices::getInstance();
		$fileType = $services->getMainConfig()->get( 'DrawioEditorImageType' );
		if ( !str_contains( $fileName, $fileType ) ) {
			return;
		}
		$fileName = str_replace( ".$fileType", '', $fileName );

		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->newSelectQueryBuilder()
			->table( 'page_props' )
			->field( 'pp_page' )
			->where( [
				'pp_propname' => 'drawio-image',
				'pp_value' => $fileName
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

<?php

namespace MediaWiki\Extension\DrawioEditor;

use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\Hook\ImagePageAfterImageLinksHook;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;

class Hooks implements ImagePageAfterImageLinksHook {

	public function __construct(
		private readonly LinkRenderer $linkRenderer,
		private readonly ILoadBalancer $loadBalancer,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onImagePageAfterImageLinks( $imagePage, &$html ) {
		$fileName = $imagePage->getFile()->getTitle()->getDBkey();

		if ( str_ends_with( $fileName, '.svg' ) ) {
			$fileName = substr( $fileName, 0, -4 );
		} elseif ( str_ends_with( $fileName, '.png' ) ) {
			$fileName = substr( $fileName, 0, -4 );
		} else {
			return;
		}

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
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
		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->pp_page );
			$link = $this->linkRenderer->makeLink( $title );
			$liEl = Html::rawElement( 'li', [], $link );
			$links[$title->getPrefixedDBkey()] = $liEl;
		}
		ksort( $links );

		if ( empty( $links ) ) {
			return;
		}

		$html .= Html::rawElement( 'h2', [], wfMessage( 'drawioeditor-usage' )->escaped() );
		$html .= Html::openElement( 'ul' ) . "\n";
		$html .= implode( "\n", $links );
		$html .= Html::closeElement( 'ul' );
	}
}

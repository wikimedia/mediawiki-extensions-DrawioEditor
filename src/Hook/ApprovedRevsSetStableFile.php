<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use File;
use RepoGroup;
use Title;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

class ApprovedRevsSetStableFile implements DrawioGetFileHook {
	/** @var ILoadBalancer */
	private $lb;
	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( ILoadBalancer $loadBalancer, RepoGroup $repoGroup ) {
		$this->lb = $loadBalancer;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @inheritDoc
	 */
	public function onDrawioGetFile( File &$file, &$latestIsStable, User $user ) {
		if ( !class_exists( 'ApprovedRevs' ) ) {
			return true;
		}
		list( $approvedRevTimestamp, $approvedRevSha1 ) = $this->getApprovedFileInfo( $file->getTitle() );
		$img_url_ts = null;
		if ( ( !$approvedRevTimestamp ) || ( !$approvedRevSha1 ) ) {
			return true;
		} else {
			$title = $file->getTitle();
			$displayFile = $this->repoGroup->findFile(
				$title, [ 'time' => $approvedRevTimestamp ]
			);
			# If none found, try current
			if ( !$displayFile ) {
				wfDebug( __METHOD__ . ": {$title->getPrefixedDBkey()}: " .
					"$approvedRevTimestamp not found, using current\n" );
				return true;
			} else {
				wfDebug( __METHOD__ . ": {$title->getPrefixedDBkey()}: " .
					"using timestamp $approvedRevTimestamp\n" );
			}
			if ( $file->getTimestamp() !== $approvedRevTimestamp ) {
				$latestIsStable = false;
			}
			$file = $displayFile;
			return true;
		}
	}

	/**
	 * @param Title $fileTitle
	 * @return array $return
	 */
	private function getApprovedFileInfo( $fileTitle ) {
		$dbr = $this->lb->getConnection( DB_REPLICA );
		$row = $dbr->selectRow(
			'approved_revs_files',
			[ 'approved_timestamp', 'approved_sha1' ],
			[ 'file_title' => $fileTitle->getDBkey() ],
			__METHOD__
		);
		if ( $row ) {
			$return = [ $row->approved_timestamp, $row->approved_sha1 ];
		} else {
			$return = [ false, false ];
		}

		return $return;
	}
}

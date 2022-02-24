<?php

namespace MediaWiki\Extension\DrawioEditor\Hook\DrawioGetFile;

use File;
use MediaWiki\MediaWikiServices;
use RepoGroup;
use Title;
use User;

class ApprovedRevsSetStableFile {
	/**
	 * @param File &$file
	 * @param bool &$latestIsStable
	 * @param User $user
	 * @return bool
	 */
	public static function callback( File &$file, &$latestIsStable, User $user ) {
		if ( !class_exists( 'ApprovedRevs' ) ) {
			return true;
		}

		list( $approvedRevTimestamp, $approvedRevSha1 ) = static::getApprovedFileInfo(
			$file->getTitle()
		);
		$repoGroup = RepoGroup::singleton();
		$img_url_ts = null;
		if ( ( !$approvedRevTimestamp ) || ( !$approvedRevSha1 ) ) {
			return true;
		} else {
			$title = $file->getTitle();
			$displayFile = $repoGroup->findFile(
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
	private static function getApprovedFileInfo( $fileTitle ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
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

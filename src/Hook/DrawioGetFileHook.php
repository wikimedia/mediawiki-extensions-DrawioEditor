<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use File;
use User;

interface DrawioGetFileHook {

	/**
	 * @param File &$file
	 * @param bool &$latestIsStable
	 * @param User $user
	 * @param bool &$isNotApproved
	 * @param File|null &$displayFile
	 * @return mixed
	 */
	public function onDrawioGetFile( File &$file, &$latestIsStable, User $user, bool &$isNotApproved,
	File &$displayFile );
}

<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use File;
use User;

interface DrawioGetFileHook {

	/**
	 * @param File &$file
	 * @param bool &$latestIsStable
	 * @param User $user
	 * @return mixed
	 */
	public function onDrawioGetFile( File &$file, &$latestIsStable, User $user );
}

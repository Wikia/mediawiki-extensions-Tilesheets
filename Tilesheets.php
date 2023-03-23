<?php
/**
 * Tilesheets
 *
 * @file
 * @ingroup Extensions
 * @version 3.2.0
 * @author Jinbobo <paullee05149745@gmail.com>
 * @author Peter Atashian
 * @author Telshin <timmrysk@gmail.com>
 * @author Noahm <noah@manneschmidt.net>
 * @author Cameron Chunn <cchunn@curse.com>
 * @author Eli Foster <elifosterwy@gmail.com>
 * @license
 * @package    Tilesheets
 * @link    https://github.com/HydraWiki/Tilesheets
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Tilesheets' );
	wfWarn(
		'Deprecated PHP entry point used for Tilesheets extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);

	return;
} else {
	die( 'This version of the Tilesheets extension requires MediaWiki 1.25+' );
}

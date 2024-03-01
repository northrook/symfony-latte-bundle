<?php

/**
 * @internal
 *
 * @author Martin Nielsen <mn@northrook.com>
 *
 */
final class File
{

	public static function normalizePath( string $string ) : string {

		$string = strtr( $string, "\\", "/" );

		if ( str_contains( $string, '/' ) === false ) {
			return $string;
		}

		$path = [];

		foreach ( explode( '/', $string ) as $part ) {
			if ( $part === '..' && $path && end( $path ) !== '..' ) {
				array_pop( $path );
			}
			else {
				if ( $part !== '.' ) {
					$path[] = trim( $part );
				}
			}
		}

		return implode(
			       separator : DIRECTORY_SEPARATOR,
			       array     : $path,
		       ) . DIRECTORY_SEPARATOR;
	}

}
<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Support\UserAgent as Get;


/** Return Type for {@see GlobalParameters::getUserAgent()}
 *
 * @property bool $android
 * @property bool $apple
 * @property bool $linux
 * @property bool $windows
 *
 * @version 1.0 ✅
 * @author Martin Nielsen <mn@northrook.com>
 */
final class UserAgent
{

	public function __get( string $name ) {
		return match ( $name ) {
			'os' => Get::getOS()['os_family'] ?? null,
			'android' => Get::OS()->android ?? false,
			'apple' => Get::OS()->apple ?? false,
			'linux' => Get::OS()->linux ?? false,
			'windows' => Get::OS()->windows ?? false,
			'getAll' => Get::getAll(),
		};
	}
}
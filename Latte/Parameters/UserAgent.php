<?php

namespace Northrook\Symfony\Latte\Parameters;


use foroco\BrowserDetection;
use Northrook\Support\Attribute\Development;
use Psr\Log\LoggerInterface;


/** Return Type for {@see GlobalParameters::getUserAgent()}
 *
 * @property bool $isAndroid
 * @property bool $isApple
 * @property bool $isLinux
 * @property bool $isWindows
 * @property bool $isDesktop
 * @property bool $isMobile
 * @property ?string $os
 * @property ?string $osTitle
 * @property string $browser
 *
 * @version 1.0 ✅
 * @author Martin Nielsen <mn@northrook.com>
 */
final class UserAgent
{

	private readonly BrowserDetection $browserDetection;

	private ?array  $getAllCache     = null;
	private ?array  $osCache         = null;
	private ?array  $browserCache    = null;
	private ?string $deviceTypeCache = null;

	/**
	 * @param  string  $name
	 * @return array|bool|string
	 */
	public function __get( string $name ) {

		if ( false === class_exists( BrowserDetection::class ) ) {
			$this?->logger->warning( "The {service} service was requested, but not available.", [
				'service' => 'BrowserDetection',
				'class'   => BrowserDetection::class,
				'package' => 'foroco/php-browser-detection ^2.7',
				'method'  => __METHOD__,
			] );

			return false;
		}

		return match ( $name ) {
			'os'        => $this->getOs(),
			'osTitle'   => $this->getOsTitle(),
			'browser'   => $this->browserName(),
			'isMobile'  => $this->deviceType() !== 'desktop',
			'isDesktop' => $this->deviceType() === 'desktop',
			'isAndroid' => $this->isOs( 'android' ),
			'isApple'   => $this->isOs( 'apple' ),
			'isLinux'   => $this->isOs( 'linux' ),
			'isWindows' => $this->isOs( 'windows' ),
			'getAll'    => $this->getAll(),
		};
	}

	public function __construct(
		private readonly ?LoggerInterface $logger = null,
	) {}


	private function getAll() : array {
		return $this->getAllCache ??= $this->detect()->getAll( $_SERVER[ 'HTTP_USER_AGENT' ] );
	}

	private function deviceType() : string {
		return $this->deviceTypeCache ??= $this->detect()->getDevice(
			$_SERVER[ 'HTTP_USER_AGENT' ],
		)[ 'device_type' ] ?? 'unknown';
	}

	private function getOs() : string {
		return $this->detectOS()[ 'os_family' ] ?? 'unknown';
	}


	private function getOsTitle() : string {
		return $this->detectOS()[ 'os_title' ] ?? 'unknown';
	}

	/**
	 * Get the OS family of the current user agent
	 *
	 *  Pass a string to $match the current OS family.
	 *  If no $match is passed, an object containing all the OS families is returned
	 *   * Each key is the OS family, and the value is true if the OS family matches
	 *
	 * @param  string  $family
	 * @return bool
	 */
	private function isOs( string $family ) : bool {
		return $this->detectOS()[ $family ] ?? false;
	}

	private function browserName() : string {
		return $this->detectBrowser()[ 'browser_name' ] ?? 'unknown';
	}

	private function detectBrowser() : array {
		return $this->browserCache ??= $this->detect()->getBrowser( $_SERVER[ 'HTTP_USER_AGENT' ] );
	}

	private function detectOS() : array {

		if ( $this->osCache ) {
			return $this->osCache;
		}

		$os = $this->detect()?->getOS( $_SERVER[ 'HTTP_USER_AGENT' ] );

		$this->osCache = array_merge(
			[
				'apple'   => $os[ 'os_family' ] === 'macintosh',
				'linux'   => $os[ 'os_family' ] === 'linux',
				'windows' => $os[ 'os_family' ] === 'windows',
				'android' => $os[ 'os_family' ] === 'android',
			],
			$os,
		);

		return $this->osCache;
	}

	/**
	 * Get the browser detection class
	 *
	 * @return BrowserDetection
	 */
	#[Development] // TODO Remove the logger->debug call after 1.0
	private function detect() : BrowserDetection {
		$this->browserDetection ??= new BrowserDetection();

		$this?->logger->debug( "The {service} service has been called and cached,", [
			'service'  => 'BrowserDetection',
			'class'    => BrowserDetection::class,
			'package'  => 'foroco/php-browser-detection ^2.7',
			'method'   => __METHOD__,
			'instance' => $this,
			'getEnv'   => getenv(),
		] );

		return $this->browserDetection;
	}
}
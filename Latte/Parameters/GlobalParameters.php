<?php

namespace Northrook\Symfony\Latte\Parameters;

use Northrook\Support\Arr;
use Psr\Log\LoggerInterface;
use Northrook\Symfony\Core\Services\EnvironmentService;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;


/** Parameters available to all templates.
 *
 * Equivalent to Twig's `app` global variable.
 *
 * * Access via `{$get}` by default
 * * Parameters are accessed by `$get->arrow->function()` syntax
 * * Full support for PHP autocompletion, see {@link https://example.com documentation} for details
 * * Exposes the same Symfony parameters and services as Twig's `app.variable`
 * * Intended to be extended with custom parameters and services
 *
 * @version 1.0 ✅
 *
 * @property object $env // Potential
 * @property bool $debug
 * @property string $locale
 * @property array $enabledLocales
 * @property array $flashes
 *
 *
 * @property string $sitename
 * @property string $language
 * @property string $theme
 * @property ?Request $request
 * @property ?UserInterface $user
 * @property ?SessionInterface $session
 *  */
class GlobalParameters
{
	private Request $requestCache;

	public function __get( string $name ) {
		$name = "get" . ucfirst( $name );
		if ( method_exists( $this, $name ) ) {
			return $this->$name() ?? null;
		}

		return null;
	}

	public function __construct(
		private readonly string        $environment,
		private readonly bool          $debug,
		private RequestStack           $requestStack,
		private UrlGeneratorInterface  $urlGenerator,
		private ?TokenStorageInterface $tokenStorage = null,
		private ?LocaleSwitcher        $localeSwitcher = null,
		private ?LoggerInterface       $logger = null,
	) {}


	/**
	 * @return TokenInterface|null
	 */
	private function getToken() : ?TokenInterface {

//		if (!isset($this->tokenStorage)) {
		$this?->logger->warning( "The {service} was requested on {route}, but was not available.", [
			'service'       => 'tokenStorage',
			'route'         => $this->getRequest()->getPathInfo(),
			'requestMethod' => $this->getRequest()->getMethod(),
			'method'        => __METHOD__,
		] );
//		}

		return $this->tokenStorage->getToken();
	}

	/**
	 * Returns the current user.
	 *
	 * @see TokenInterface::getUser()
	 */
	protected function getUser() : ?UserInterface {

//		if ( !isset( $this->tokenStorage ) ) {
		$this?->logger->warning( "The {service} was requested on {route}, but was not available.", [
			'service'       => 'tokenStorage',
			'route'         => $this->getRequest()->getPathInfo(),
			'requestMethod' => $this->getRequest()->getMethod(),
			'method'        => __METHOD__,
		] );
//		}

		return $this->tokenStorage->getToken()?->getUser();
	}

	/** Return the current request if it exists, else a new request.
	 *
	 * * Uses {@see RequestStack}
	 * * Cached
	 *
	 * @return Request The current request
	 * @version       1.1 ✅
	 * @uses            \Symfony\Component\HttpFoundation\RequestStack
	 */
	protected function getRequest() : Request {
		return ( isset( $this->requestCache ) ) ? $this->requestCache
			: $this->requestCache = $this->requestStack->getCurrentRequest() ?? new Request();
	}

	/** Returns the current session.
	 */
	protected function getSession() : ?SessionInterface {

		$request = $this->getRequest();

		return $request->hasSession() ? $request->getSession() : null;
	}


	protected function getEnv() : object {
		return (object) [
			'is'   => fn ( string $name ) : mixed => match ( $name ) {
				'debug'   => $this->debug,
				'authorized' => (bool) $this->getUser(),
				'locale'  => $this->localeSwitcher->getLocale(),
				'default' => $this->environment,
			},
			'type' => $this->environment,
		];
	}

	protected function getDebug() : bool {
		return $this->debug;
	}

	// TODO: Get language from user if logged in, else from settings, else 'en-GB'
	protected function getLanguage( ?string $fallback = 'en-GB' ) : string {
		return $fallback;
	}


	protected function getLocale() : string {
		if ( !isset( $this->localeSwitcher ) ) {
			throw new RuntimeException( 'The "app.locale" variable is not available.' );
		}

		return $this->localeSwitcher->getLocale();
	}

	protected function getEnabledLocales() : array {
		if ( !isset( $this->enabledLocales ) ) {
			throw new RuntimeException( 'The "app.enabled_locales" variable is not available.' );
		}

		return $this->enabledLocales;
	}


	/**
	 * Returns some or all the existing flash messages:
	 *  * getFlashes() returns all the flash messages
	 *  * getFlashes('notice') returns a simple array with flash messages of that type
	 *  * getFlashes(['notice', 'error']) returns a nested array of type => messages.
	 */
	protected function getFlashes( string | array | null $types = null ) : array {
		try {
			$session = $this->getSession();
		}
		catch ( RuntimeException ) {
			return [];
		}

		if ( !$session instanceof FlashBagAwareSessionInterface ) {
			return [];
		}

		if ( null === $types || '' === $types || [] === $types ) {
			return $session->getFlashBag()->all();
		}

		if ( is_string( $types ) ) {
			return $session->getFlashBag()->get( $types );
		}

		$result = [];
		foreach ( $types as $type ) {
			$result[ $type ] = $session->getFlashBag()->get( $type );
		}

		return $result;
	}

	public function getRoute() : ?string {
		return $this->getRequest()->attributes->get( '_route' );
	}




	//----------------------------------------------------------------------------


	/**
	 * Returns a formatted time string, based on {@see date()}.
	 *
	 * @param  string|null  $format
	 * @param  int|null  $timestamp
	 * @return string
	 *
	 * @see https://www.php.net/manual/en/function.date.php See docs for supported formats
	 */
	public function time( ?string $format = null, ?int $timestamp = null ) : string {

		// TODO: Add support for date and time formats
		// TODO: Add support for centralized date and time formats

		return date( $format ?? 'Y-m-d H:i:s', $timestamp );
	}

	// TODO: Update to support Url::type and Path::type
	public function url( string $route ) : string {

		return $this->urlGenerator->generate( $route );
	}

	// TODO: Update to support Url::type and Path::type

	/**
	 * {@see UrlGeneratorInterface::generate()}
	 *
	 * @param  string  $route
	 * @param  array  $parameters
	 * @param  bool  $absoluteUrl
	 * @return string
	 */
	public function path( string $route, array $parameters = [], bool $absoluteUrl = false ) : string {
		try {
			return $this->urlGenerator->generate(
				name          : $route,
				referenceType : $absoluteUrl
					                ? UrlGeneratorInterface::ABSOLUTE_URL
					                : UrlGeneratorInterface::ABSOLUTE_PATH,
			);
		}
		catch (
		InvalidParameterException |
		MissingMandatoryParametersException |
		RouteNotFoundException  $e
		) {
			$this->logger?->error(
				message : "Unable to resolve route {name}",
				context : [
					          'name'      => $route,
					          'backtrace' => debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 ),
					          'exception' => $e,
				          ],
			);
			return $route;
		}
	}

}
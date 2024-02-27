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
use Symfony\Component\Security\Core\User\UserInterface;
use function is_bool;

/** Parameters available to all templates.
 *
 * * Access via `{$get}`
 *
 * @version 1.0 ✅
 *
 * @property string $sitename
 * @property string $language
 * @property string $theme
 * @property ?Request $request
 * @property ?UserInterface $user
 * @property ?SessionInterface $session
 *  */
final class GlobalParameters
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
		private RequestStack           $requestStack,
		private UrlGeneratorInterface  $urlGenerator,
		private ?EnvironmentService    $env,
		private ?TokenStorageInterface $tokenStorage = null,
		private ?LoggerInterface       $logger = null,
	) {}


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

//	public function asset( string $path, ?bool $autoRoute = null ) : Asset {
//		$asset = new Asset( $path, $this->env->path( 'dir.public' ) );
//		// \dump( $asset );
//
//		return $asset;
//	}

	public function coreScripts() : void {
		$core = Arr::implode( [
			                      "\nconst env = {\n" . implode( ",\n", $this->environmentVariables() ) . "\n};",
		                      ] );
		echo "<script>\n$core\n</script>";
	}

	// TODO: Get sitename from settings
	protected function getSitename() : string {
		return 'Symfony Framework';
	}

	// TODO: Get language from user if logged in, else from settings, else 'en-GB'
	protected function getLanguage( ?string $fallback = 'en-GB' ) : string {
		return $fallback;
	}

	// TODO: Get theme from user if logged in, else from settings, else 'system'
	protected function getTheme() : string {
		return 'system';
	}

	public function getRoute() : ?string {
		return $this->getRequest()->attributes->get( '_route' );
	}

	/** Return the current request if it exists, else a new request.
	 *
	 * @return Request The current request
	 * @version       1.1 ✅
	 * @uses            \Symfony\Component\HttpFoundation\RequestStack
	 */
	protected function getRequest() : Request {
		return ( isset( $this->requestCache ) ) ? $this->requestCache
			: $this->requestCache = $this->requestStack->getCurrentRequest() ?? new Request();
	}

	/**
	 * Returns the current user.
	 *
	 * @see TokenInterface::getUser()
	 */
	protected function getUser() : ?UserInterface {
		if ( !isset( $this->tokenStorage ) ) {
			throw new RuntimeException( 'The "app.user" variable is not available.' );
		}

		return $this->tokenStorage->getToken()?->getUser();
	}

	/** Returns the current session.
	 */
	protected function getSession() : ?SessionInterface {
		if ( !isset( $this->requestStack ) ) {
			throw new RuntimeException( 'The "app.session" variable is not available.' );
		}

		$request = $this->getRequest();

		return $request->hasSession() ? $request->getSession() : null;
	}


	private function environmentVariables() : array {

		$env = array_filter( [
			                     'public'        => $this->env->is( Env::PRODUCTION ),
			                     'debug'         => $this->env->is( Env::DEBUG ),
			                     'authenticated' => (bool) $this->getUser(),
			                     'theme'         => $this->getTheme(),
			                     'route'         => $this->getRoute(),
		                     ] );

		foreach ( $env as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			else {
				$value = "'$value'";
			}
			$env[ $key ] = "\t$key : $value";
		}

		return $env;

	}
}
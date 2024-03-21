<?php

/**
 * @noinspection PhpUnused
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */


namespace Northrook\Symfony\Latte\Parameters;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Global parameters, accessed via {@see $get}
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
 * @property Env               $env
 * @property UserAgent         $userAgent
 * @property bool              $debug
 * @property string            $locale
 * @property array             $enabledLocales
 * @property array             $flashes
 *
 *
 * @property string            $sitename
 * @property string            $language
 * @property string            $theme
 * @property ?Request          $request
 * @property ?UserInterface    $user
 * @property ?SessionInterface $session
 *  */
class Application
{

    private Request   $requestCache;
    private Env       $envCache;
    private UserAgent $userAgentCache;

    private readonly string $languageCache;

    public function __construct(
        public readonly string                  $environment,
        public readonly bool                    $debug,
        public readonly Document                $document,
        private readonly RequestStack           $requestStack,
        private readonly UrlGeneratorInterface  $urlGenerator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?LocaleSwitcher        $localeSwitcher = null,
        private readonly ?LoggerInterface       $logger = null,
    ) {}

    public function __get( string $name ) {
        $name = "get" . ucfirst( $name );
        if ( method_exists( $this, $name ) ) {
            return $this->$name() ?? null;
        }

        return null;
    }

    /**
     * @return TokenInterface|null
     */
    protected function getToken() : ?TokenInterface {

        if ( !$this->tokenStorage ) {
            $this?->logger->warning(
                "The {service} was requested on {route}, but was not available.", [
                'service'       => 'tokenStorage',
                'route'         => $this->getRequest()->getPathInfo(),
                'requestMethod' => $this->getRequest()->getMethod(),
                'method'        => __METHOD__,
            ],
            );
        }

        return $this->tokenStorage->getToken();
    }

    /**
     * Returns the current user.
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser() : ?UserInterface {

        if ( !$this->tokenStorage ) {
            $this?->logger->warning(
                "The {service} was requested on {route}, but was not available.", [
                'service'       => 'tokenStorage',
                'route'         => $this->getRequest()->getPathInfo(),
                'requestMethod' => $this->getRequest()->getMethod(),
                'method'        => __METHOD__,
            ],
            );
        }

        return $this->tokenStorage->getToken()?->getUser();
    }

    /** Return the current request if it exists, else a new request.
     *
     * * Uses {@see RequestStack}
     * * Cached
     *
     * @return Request The current request
     * @version         1.1 ✅
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

    protected function getUserAgent() : UserAgent {
        return ( isset( $this->userAgentCache ) ) ? $this->userAgentCache
            : $this->userAgentCache = new UserAgent( $this->logger );
    }


    protected function getEnv() : Env {
        return ( isset( $this->envCache ) )
            ? $this->envCache
            : $this->envCache = new Env(
                $this->debug,
                (bool) $this->getUser(),
                $this->environment === 'prod',
                $this->environment === 'staging',
                $this->environment === 'dev',
            );

    }

    public function language( ?string $fallback = null ) : string {
        return $this->getLocale( $fallback );
    }

    protected function getLanguage() : string {
        return $this->getLocale();
    }

    protected function getLocale( ?string $fallback = null ) : string {

        $fallback ??= 'en';

        if ( !$this->localeSwitcher ) {
            $this?->logger->warning(
                "The {service} was requested on {route}, but was not available.", [
                'service'  => 'localeSwitcher',
                'fallback' => $fallback,
                'method'   => __METHOD__,
            ],
            );
        }

        if ( isset( $this->languageCache ) ) {
            return $this->languageCache;
        }

        return $this->languageCache = $this->localeSwitcher?->getLocale() ?? $fallback;
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

    protected function getRoute() : ?string {
        return $this->getRequest()->attributes->get( '_route' );
    }




    //----------------------------------------------------------------------------


    /**
     * Returns a formatted time string, based on {@see date()}.
     *
     * @param string|null  $format
     * @param int|null     $timestamp
     *
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
     * @param string  $route
     * @param array   $parameters
     * @param bool    $absoluteUrl
     *
     * @return string
     */
    public function path( string $route, array $parameters = [], bool $absoluteUrl = false ) : string {
        try {
            return $this->urlGenerator->generate(
                name          : $route,
                parameters    : $parameters,
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
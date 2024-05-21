<?php

namespace Northrook\Symfony\Latte;

use Northrook\Symfony\Latte\Properties\Env;
use Northrook\Symfony\Latte\variables\ProjectEnvironment;
use Northrook\Symfony\Latte\Variables\UserAgent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/** Feature parity with Symfony Twig integration.
 *
 * @link https://symfony.com/doc/current/reference/twig_reference.html Symfony Twig docs
 *
 * - TODO :
 *
 */


/**
 * Exposes some Symfony parameters and services as a global variable in every Latte template loaded by the bundled {@see Loader}.
 *
 * - The default variable name is `get`.
 * - This can be changed by setting the `latte.global_variable.key` parameter.
 *
 * Available properties:
 * @property string             $language
 * @property string             $routeInfo
 * @property bool               $debug
 * @property ProjectEnvironment $env
 * @property Request            $request
 * @property SessionInterface   $session
 * @property UserAgent          $useragent
 * @property ?UserInterface     $user
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class GlobalVariable
{

    private const SERVICES = [
        'request_stack'   => RequestStack::class,
        'router'          => RouterInterface::class,
        'token_storage'   => TokenStorageInterface::class,
        'locale_switcher' => LocaleSwitcher::class,
        'token_manager'   => CsrfTokenManagerInterface::class,
        'logger'          => LoggerInterface::class,
    ];

    private ParameterBagInterface $parameterBag;
    private ProjectEnvironment    $projectEnvironment;
    private UserAgent             $userAgent;

    public function __construct(
        private string         $environment,
        private bool           $debug,
        private ServiceLocator $serviceLocator,
    ) {}


    /**
     * Check if the requested variable is available.
     */
    public function __isset( string $name ) : bool {
        return method_exists( $this, $this->method( $name ) );
    }

    /**
     * The {@see GlobalVariable} does not support assigning dynamic properties.
     */
    public function __set( string $name, $value ) : void {}

    /**
     * @param string  $name
     *
     * @return mixed All invalid variables will return null.
     */
    public function __get( string $name ) : mixed {

        if ( !method_exists( $this, $this->method( $name ) ) ) {
            return null;
        }

        return match ( strtolower( $name ) ) {
            'debug'     => $this->debug,
            'env'       => $this->getEnv(),
            'request'   => $this->getRequest(),
            'session'   => $this->getSession(),
            'useragent' => $this->getUserAgent(),
            'user'      => $this->getUser(),
            'routeinfo' => $this->getRequest()?->getPathInfo(),
            'language'  => $this->getLocale(),
            default     => null,
        };
    }

    /**
     * @return ProjectEnvironment
     */
    private function getEnv() : ProjectEnvironment {
        return $this->projectEnvironment ??= new ProjectEnvironment(
            debug      : $this->debug,
            production : $this->environment === 'prod',
            staging    : $this->environment === 'staging',
            dev        : $this->environment === 'dev',
        );
    }

    /**
     * @return Request
     */
    private function getRequest() : Request {
        return $this->serviceLocator->get( RequestStack::class )->getCurrentRequest();
    }

    /**
     * @return ?SessionInterface
     */
    private function getSession() : ?SessionInterface {
        return $this->getRequest()?->hasSession() ? $this->getRequest()?->getSession() : null;
    }


// authorized : (bool) $this->get( TokenStorageInterface::class )?->getToken(),


    /**
     * @return UserAgent
     */
    private function getUserAgent() : UserAgent {
        return $this->userAgent ??= new UserAgent( $this->get( LoggerInterface::class ) );
    }

    private function getUser() : ?UserInterface {
        return $this->get( TokenStorageInterface::class )?->getToken()?->getUser();
    }

    private function getLocale() : string {
        return $this->get( LocaleSwitcher::class )->getLocale();
    }

    /**
     * @param string  $name
     *
     * @return mixed
     */
    public function parameter( string $name ) : mixed {

        $this->parameterBag ??= $this->get( ParameterBagInterface::class );

        if ( !$this->parameterBag->has( $name ) ) {
            return null;
        }

        return $this->parameterBag->get( $name );
    }

    // -- Support Methods ------------------------------------------------------

    /**
     * @template Service
     *
     * @param class-string<Service>  $className
     *
     * @return Service
     */
    private function get(
        string $className,
    ) : mixed {
        return $this->serviceLocator->get( $className );
    }

    private function method( string $name ) : string {
        return 'get' . ucfirst( $name );
    }
}
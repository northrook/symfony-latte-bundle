<?php

namespace Northrook\Symfony\Latte;

use Latte;
use Northrook\Symfony\Core\Env;
use Northrook\Symfony\Core\Facade\Path;
use Northrook\Types\Interfaces\Printable;

class Render implements Printable
{

    protected readonly Latte\Engine $latte;

    private function __construct(
        public readonly string $template,
        private array          $parameters = [],
    ) {
        $this->latte = new Latte\Engine();
        $this->latte->setTempDirectory( Path::get( 'dir.latte.cache' ) );

        if ( Env::isDebug() ) {
            $this->latte->setAutoRefresh( true );
        }
    }

    final public static function template( string $template, array $parameters = [] ) : self {
        return new self( $template, $parameters );
    }

    final public static function string( string $template, array $parameters = [] ) : string {
        return ( new self( $template, $parameters ) )->print();
    }

    public function render( array $parameters = [] ) : string {
        return $this->latte->renderToString(
            name   : $this->getTemplateFile(),
            params : $this->templateParameters( $parameters ),
        );
    }

    public function print() : string {
        return 'Hello World';
    }

    private function getTemplateFile() : string {
        return $this->template;
    }

    private function templateParameters( array $parameters = [] ) : array {
        $parameters = array_merge( $this->parameters, $parameters );

        return Environment::parameters( $parameters );
    }
}
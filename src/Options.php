<?php

namespace Northrook\Symfony\Latte;

use Northrook\Logger\Log;
use Northrook\Types\Path;
use Northrook\Types\Type\Properties;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Options extends Properties
{
    public string $globalVariable   = 'get';
    public string $documentVariable = 'document';

    public readonly Path  $templateDirectory;
    public readonly Path  $cacheDirectory;
    public readonly ?Path $coreTemplateDirectory;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {

        try {
            $this->templateDirectory = new Path( $this->parameterBag->get( 'dir.latte.templates' ) );
        }
        catch ( ParameterNotFoundException $e ) {
            trigger_error( "The `templateDirectory` is required. " . $e->getMessage() );
        }

        try {
            $this->cacheDirectory = new Path( $this->parameterBag->get( 'dir.latte.cache' ) );
        }
        catch ( ParameterNotFoundException $e ) {
            trigger_error( "The `cacheDirectory` is required. " . $e->getMessage() );
        }

        try {
            $this->coreTemplateDirectory = new Path( $this->parameterBag->get( 'dir.core.latte.templates' ) );
        }
        catch ( ParameterNotFoundException $e ) {
            Log::Warning(
                "The {message} was not set. The current value is {value}.  Error: {message}",
                [
                    'dir'       => 'coreTemplateDirectory',
                    'value'     => null,
                    'message'   => $e->getMessage(),
                    'exception' => $e,
                ],
            );

            $this->coreTemplateDirectory = null;
        }
    }

}
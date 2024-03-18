<?php

namespace Northrook\Symfony\Latte;

use Northrook\Types\Type\Properties;

final class Options extends Properties
{
    public string $globalVariable   = 'get';
    public string $documentVariable = 'document';

}
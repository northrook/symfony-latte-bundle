<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Latte\Variables;

interface DocumentVariableInterface
{
    /**
     * Retrieve the Document variable object.
     *
     * - How you generate it is up to you.
     * - See the {@see Document} class for required variables.
     *
     * @return Document
     */
    public function getDocumentVariable() : Document;
}
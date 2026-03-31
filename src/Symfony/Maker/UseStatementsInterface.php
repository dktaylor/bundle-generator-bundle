<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

interface UseStatementsInterface
{
    /**
     * Returns the underlying object in the form expected by the MakerBundle
     * template renderer. Typed as mixed to avoid coupling to the internal
     * UseStatementGenerator class.
     */
    public function unwrap(): mixed;
}

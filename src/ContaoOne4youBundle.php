<?php

namespace C4Y\One4you;

use C4Y\One4you\DependencyInjection\Compiler\DisableOveleonStyleManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoOne4youBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DisableOveleonStyleManagerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

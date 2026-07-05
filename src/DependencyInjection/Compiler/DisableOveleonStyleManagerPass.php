<?php

declare(strict_types=1);

namespace C4Y\One4you\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DisableOveleonStyleManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if (!\is_string($class)) {
                continue;
            }

            if (str_starts_with($class, 'Oveleon\\ContaoComponentStyleManager\\')) {
                $definition->clearTag('contao.callback');
                $definition->clearTag('contao.hook');
                $definition->clearTag('kernel.event_listener');
            }

            if ($class === 'ContaoThemeManager\\Core\\EventListener\\AddStyleManagerPaletteEventListener') {
                $definition->clearTag('kernel.event_listener');
            }
        }
    }
}

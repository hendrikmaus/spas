<?php

namespace Hmaus\Spas\Formatter\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
class FormatterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'hmaus.spas.formatter.service';

        if (!$container->has($serviceId)) {
            return;
        }

        $definition = $container->findDefinition($serviceId);
        $taggedServices = $container->findTaggedServiceIds('hmaus.spas.tag.formatter');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addFormatter', [new Reference($id)]);
        }
    }
}

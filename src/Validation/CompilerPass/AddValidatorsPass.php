<?php

namespace Hmaus\Spas\Validation\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
class AddValidatorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'hmaus.spas.validator';

        if (!$container->has($serviceId)) {
            return;
        }

        $definition = $container->findDefinition($serviceId);
        $taggedServices = $container->findTaggedServiceIds('hmaus.spas.tag.validator');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addValidator', [new Reference($id)]);
        }
    }
}
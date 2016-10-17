<?php

namespace Hmaus\Spas\Request\Result\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
class ExceptionHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'hmaus.spas.request.result.exception_handler';

        if (!$container->has($serviceId)) {
            return;
        }

        $definition = $container->findDefinition($serviceId);
        $taggedServices = $container->findTaggedServiceIds('hmaus.spas.tag.result_printer');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addPrinter', [new Reference($id)]);
        }
    }
}

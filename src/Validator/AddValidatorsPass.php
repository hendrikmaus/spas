<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-14
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   spas
 */

namespace Hmaus\Spas\Validator;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddValidatorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('hmaus.spas.validator')) {
            return;
        }

        $definition = $container->findDefinition('hmaus.spas.validator');
        $taggedServices = $container->findTaggedServiceIds('hmaus.spas.tag.validator');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addValidator', [new Reference($id)]);
        }
    }
}
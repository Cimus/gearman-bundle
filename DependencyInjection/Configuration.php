<?php

namespace Cimus\GearmanBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 * 
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cimus_gearman');

        $rootNode
                ->children()
                    ->arrayNode('servers')
                        ->performNoDeepMerging()
                        ->defaultValue([
                            'localhost' =>  [
                                'host'  =>  '127.0.0.1',
                                'port'  =>  '4730',
                            ],
                        ])
                        ->prototype('array')
                            ->children()
                                ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                                ->integerNode('port')->defaultValue('4730')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}

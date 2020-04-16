<?php

declare(strict_types=1);

namespace Phpcq\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class PhpcqConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('phpcq');
        $root        = $treeBuilder->getRootNode();

        $root
            ->normalizeKeys(false)
            ->children()
                ->arrayNode('directories')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('artifact')
                    ->defaultValue('.phpcq/build')
                    ->info('Artifact directory for builds')
                ->end()
                ->arrayNode('repositories')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('tools')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->normalizeKeys(false)
                        ->children()
                            ->scalarNode('version')->end()
                            ->scalarNode('runner-plugin')->end()
                            ->booleanNode('signed')
                                ->defaultValue(true)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('trusted-keys')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

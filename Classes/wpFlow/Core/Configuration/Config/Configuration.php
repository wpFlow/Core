<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 27.06.15
 * Time: 00:35
 */

namespace wpFlow\Configuration\Config;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('blog');

        $rootNode
            ->children()
                ->scalarNode('title')
                    ->isRequired()
            ->end()
            ->scalarNode('description')
                ->defaultValue('')
            ->end()
                ->booleanNode('rss')
                    ->defaultValue(false)
            ->end()
                ->integerNode('posts_main_page')
                    ->min(1)
                    ->max(10)
                    ->defaultValue(5)
                ->end()
                ->arrayNode('social')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('url')->end()
                            ->scalarNode('icon')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
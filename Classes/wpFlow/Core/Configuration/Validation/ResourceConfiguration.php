<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 06.07.15
 * Time: 15:43
 */

namespace wpFlow\Configuration\Validation;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ResourceConfiguration implements ConfigurationInterface {

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('Resources');

        $rootNode
            ->children()
                ->arrayNode('Public')
                    ->prototype('array')
                        ->children()
                            ->enumNode('Type')->isRequired()->values(array('local','cdn', 'localCDN'))->end()
                            ->scalarNode('Filename')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('Ranking')->isRequired()->cannotBeEmpty()->end()
                            ->enumNode('Position')->isRequired()->values(array('header','footer'))->end()
                            ->booleanNode('Minify')->defaultTrue()->end()
                            ->booleanNode('Compile')->defaultFalse()->isRequired()->end()
                            ->arrayNode('Options')
                                ->children()
                                    ->scalarNode('Expression')->end()
                                    ->arrayNode('Arguments')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('CDN')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
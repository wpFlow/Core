<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 15.06.15
 * Time: 22:31
 */

namespace wpFlow\Configuration\Validation;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DataBaseConfiguration implements ConfigurationInterface {

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('Database');

        $rootNode
            ->children()
                ->scalarNode('dbname')
                    ->isRequired()
                ->end()
                ->scalarNode('dbuser')
                    ->isRequired()
                ->end()
                ->scalarNode('dbname')
                    ->isRequired()
                ->end()
                ->scalarNode('dbpassword')
                    ->isRequired()
                ->end()
                ->scalarNode('dbhost')
                    ->isRequired()
                ->end()
                ->scalarNode('dbcharset')
                    ->isRequired()
                ->end()
                ->scalarNode('tableprefix')
                    ->isRequired()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
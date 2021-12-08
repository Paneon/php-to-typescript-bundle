<?php declare(strict_types=1);

namespace Paneon\PhpToTypeScriptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('php_to_type_script');
        $rootNode = $treeBuilder->getRootNode();

        if (method_exists($treeBuilder, 'getRootNode')) {
            $root = $treeBuilder->getRootNode()->children();
        } else {
            $root = $treeBuilder->root('jms_serializer')->children();
        }

        $this->addGeneralConfiguration($root);
        $this->addFileConfiguration($root);
        $this->addDirectoryConfiguration($root);

        return $treeBuilder;
    }

    public function addGeneralConfiguration(NodeBuilder $builder)
    {
        $builder
            ->integerNode('indentation')->defaultValue(2)->end()
            ->scalarNode('inputDirectory')->defaultValue('src/')->end()
            ->scalarNode('outputDirectory')->defaultValue('assets/js/interfaces/')->end()
            ->scalarNode('prefix')->defaultValue('')->end()
            ->scalarNode('suffix')->defaultValue('')->end()
            ->booleanNode('nullable')->defaultValue(false)->end()
        ;
    }

    public function addFileConfiguration(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('interfaces')
                ->normalizeKeys(false)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->arrayPrototype()
                    ->normalizeKeys(false)
                    ->children()
                        ->scalarNode('output')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function addDirectoryConfiguration(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('directories')
                ->normalizeKeys(false)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->arrayPrototype()
                    ->normalizeKeys(false)
                    ->children()
                        ->scalarNode('output')->end()
                        ->booleanNode('requireAnnotation')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}

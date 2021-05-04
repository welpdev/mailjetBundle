<?php

namespace Mailjet\MailjetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('mailjet');
        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('mailjet');
        }

        $rootNode
            ->children()
                ->scalarNode('api_key')->isRequired()->info('Mailjet API key')->end()
                ->scalarNode('secret_key')->isRequired()->info('Mailjet API token')->end()
                ->booleanNode('call')->defaultTrue()->info('Performs the API call or not')->end()
                ->arrayNode('options')->info('Options for Mailjet Client (url, version, call, secured)')
                    ->children()
                        ->scalarNode('url')->end()
                        ->scalarNode('version')->end()
                        ->booleanNode('call')->end()
                        ->booleanNode('secured')->end()
                    ->end()
                ->end()
                ->arrayNode('transactionnal')->info('Options for Mailjet client for SwiftMailer integration')
                    ->children()
                        ->booleanNode('call')->defaultTrue()->info('Performs the API call or not')->end()
                        ->arrayNode('options')->info('Options for Mailjet Client (url, version, call, secured)')
                            ->children()
                                ->scalarNode('url')->end()
                                ->scalarNode('version')->end()
                                ->booleanNode('call')->end()
                                ->booleanNode('secured')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('event_endpoint_route')
                    ->defaultValue('mailjet_event_endpoint')
                    ->info('Route name of the event API endpoint')
                ->end()
                ->scalarNode('event_endpoint_token')
                    ->defaultValue('123456789')
                    ->info('Security token to validate endpoint request with')
                ->end()
                // lists
                ->arrayNode('lists')
                    ->useAttributeAsKey('listId')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('contact_provider')->end()
                        ->end()
                    ->end()
                ->end()
                // ContactMetadata/ContactProperties
                ->arrayNode('contact_metadata')
                    ->prototype('array')
                        ->children()
                           ->scalarNode('name')
                               ->isRequired()
                           ->end()
                           ->scalarNode('datatype')
                               ->isRequired()
                           ->end()
                       ->end()
                   ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

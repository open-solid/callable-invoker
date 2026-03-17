<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->children()
                ->arrayNode('decorate')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('controllers')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end()
        ->end()
    ;
};

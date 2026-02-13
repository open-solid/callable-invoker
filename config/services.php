<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container
        ->parameters()
            // ->set('open.param_name', 'param_value');
    ;
    $container
        ->services()
            // ->set('open.service_name', 'service_class')
    ;
};

<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    if (class_exists('FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle')) {
        $container->import('../../sylius/src/Sylius/Behat/Resources/config/services.xml');
    }
};

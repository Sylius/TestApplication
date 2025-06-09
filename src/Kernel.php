<?php

declare(strict_types=1);

namespace Sylius\TestApplication;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader as ContainerPhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $kernelClass = str_contains(static::class, "@anonymous\0") ? parent::class : static::class;

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', $kernelClass)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true)
                ;
            }

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');

            $container->addObjectResource($this);
            $container->fileExists($this->getBundlesPath());

            $configureContainer = new \ReflectionMethod($this, 'configureContainer');
            $configuratorClass = $configureContainer->getNumberOfParameters() > 0 && ($type = $configureContainer->getParameters()[0]->getType()) instanceof \ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            if ($configuratorClass && !is_a(ContainerConfigurator::class, $configuratorClass, true)) {
                $configureContainer->getClosure($this)($container, $loader);

                return;
            }

            $file = (new \ReflectionObject($this))->getFileName();
            /* @var ContainerPhpFileLoader $kernelLoader */
            $kernelLoader = $loader->getResolver()->resolve($file);
            $kernelLoader->setCurrentDir(\dirname($file));
            $instanceof = &\Closure::bind(fn &() => $this->instanceof, $kernelLoader, $kernelLoader)();

            $valuePreProcessor = AbstractConfigurator::$valuePreProcessor;
            AbstractConfigurator::$valuePreProcessor = fn ($value) => $this === $value ? new Reference('kernel') : $value;

            try {
                $configureContainer->getClosure($this)(new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file, $this->getEnvironment()), $loader, $container);
            } finally {
                $instanceof = [];
                $kernelLoader->registerAliasesForSinglyImplementedInterfaces();
                AbstractConfigurator::$valuePreProcessor = $valuePreProcessor;
            }

            $container->setAlias($kernelClass, 'kernel')->setPublic(true);

            if (isset($_SERVER['CONFIGS_TO_IMPORT'])) {
                foreach (explode(';', $_SERVER['CONFIGS_TO_IMPORT']) as $filePath) {
                    $kernelLoader->import($filePath);
                }
            }
        });

    }
    public function registerBundles(): iterable
    {
        if (!is_file($bundlesPath = $this->getBundlesPath())) {
            yield new FrameworkBundle();

            return;
        }

        $contents = require $bundlesPath;

        if (isset($_SERVER['BUNDLES_TO_ENABLE'])) {
            foreach (explode(';', $_SERVER['BUNDLES_TO_ENABLE']) as $bundleClass) {
                if (class_exists($bundleClass)) {
                    $contents[$bundleClass] = ['all' => true];
                }
            }
        }

        $additionalBundlesPath = $this->getAdditionalBundlesPath();
        if (is_file($additionalBundlesPath)) {
            $additionalBundles = require $additionalBundlesPath;
            if (is_array($additionalBundles)) {
                foreach ($additionalBundles as $bundleClass) {
                    if (class_exists($bundleClass)) {
                        $contents[$bundleClass] = ['all' => true];
                    }
                }
            }
        }

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));
        $collection = new RouteCollection();

        $configureRoutes = new \ReflectionMethod($this, 'configureRoutes');
        $routes = new RoutingConfigurator($collection, $kernelLoader, $file, $file, $this->getEnvironment());
        $configureRoutes->getClosure($this)($routes);

        if (isset($_SERVER['ROUTES_TO_IMPORT'])) {
            foreach (explode(';', $_SERVER['ROUTES_TO_IMPORT']) as $filePath) {
                $routes->import($filePath);
            }
        }

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            } elseif ($controller instanceof \Closure && $this === ($r = new \ReflectionFunction($controller))->getClosureThis() && !str_contains($r->name, '{closure')) {
                $route->setDefault('_controller', ['kernel', $r->name]);
            }
        }

        return $collection;
    }

    private function getAdditionalBundlesPath(): string
    {
        return \dirname($this->getProjectDir(), 3) . '/tests/TestApplication/config/bundles.php';
    }
}

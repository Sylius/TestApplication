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

            $configsToImport = $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] ?? $_SERVER['CONFIGS_TO_IMPORT'] ?? null;
            if (null !== $configsToImport) {
                foreach (explode(';', $configsToImport) as $filePath) {
                    $kernelLoader->import($filePath);
                }
            }
        });
    }

    public function registerBundles(): iterable
    {
        $env = $this->getEnvironment();

        $bundlesPath = $this->resolveBundlesPath();
        if (!is_file($bundlesPath)) {
            yield new FrameworkBundle();
            return;
        }

        $contents = $this->loadMainBundles($bundlesPath);
        $additionalBundlesLoaded = $this->loadAdditionalBundlesFromEnv($contents);

        if (!$additionalBundlesLoaded) {
            $this->loadBundlesToEnable($contents);
        }

        foreach ($contents as $class => $envs) {
            if ($envs[$env] ?? $envs['all'] ?? false) {
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

        $routesToImport = $_SERVER['SYLIUS_TEST_APP_ROUTES_TO_IMPORT'] ?? $_SERVER['ROUTES_TO_IMPORT'] ?? null;
        if (null !== $routesToImport) {
            foreach (explode(';', $routesToImport) as $filePath) {
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

    private function loadMainBundles(string $path): array
    {
        return require $path;
    }

    private function loadAdditionalBundlesFromEnv(array &$contents): bool
    {
        $bundlesPathEnv = $_SERVER['SYLIUS_TEST_APP_BUNDLES_PATH'] ?? $_SERVER['TEST_APP_BUNDLES_PATH'] ?? null;
        if (null === $bundlesPathEnv) {
            return false;
        }

        $absolutePath = \dirname($this->getProjectDir(), 3) . '/' . ltrim($bundlesPathEnv, '/');

        if (!is_file($absolutePath)) {
            return false;
        }

        $additionalBundles = require $absolutePath;
        if (!\is_array($additionalBundles)) {
            return false;
        }

        foreach ($additionalBundles as $bundleClass => $envs) {
            if (\class_exists($bundleClass)) {
                $contents[$bundleClass] = $envs;
            }
        }

        return true;
    }

    private function loadBundlesToEnable(array &$contents): void
    {
        $bundlesToEnable = $_SERVER['SYLIUS_TEST_APP_BUNDLES_TO_ENABLE'] ?? $_SERVER['BUNDLES_TO_ENABLE'] ?? null;
        if (null === $bundlesToEnable) {
            return;
        }

        foreach (explode(';', $bundlesToEnable) as $bundleClass) {
            if (\class_exists($bundleClass)) {
                $contents[$bundleClass] = ['all' => true];
            }
        }
    }

    private function resolveBundlesPath(): string
    {
        $relativePath = $_SERVER['SYLIUS_TEST_APP_BUNDLES_REPLACE_PATH'] ?? null;
        if (null !== $relativePath) {
            $absolutePath = \dirname($this->getProjectDir(), 3) . '/' . ltrim($relativePath, '/');

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return $this->getBundlesPath();
    }
}

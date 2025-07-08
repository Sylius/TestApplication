<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

TestApplication
===============

Developer tool that provides a ready-to-use Sylius-based application for testing and running Sylius plugins.

> ⚠️ While TestApplication is still evolving, it is already being used internally and in official plugins.
   We encourage you to adopt it in your plugins, provide feedback, and contribute to improve the developer experience 
   for the entire Sylius ecosystem.

## Documentation

For more information about the **Test Application**, please refer to the [Sylius documentation](https://docs.sylius.com/sylius-plugins/plugins-development-guide/testapplication).

## Purpose

Previously, each plugin had to maintain its own copy of a test application, leading to duplicated configuration, 
maintenance overhead, and version incompatibilities.

This package solves that problem by:

- Extracting a reusable, standalone test application
- Providing an official, centrally maintained solution by the Sylius team
- Simplifying the setup and execution of tests within plugins
- Creating versioned variants aligned with specific Sylius versions (e.g. `1.14`, `2.0`, etc.)

## Installation and configuration in a Plugin

1. Require the TestApplication as a development dependency:

    ```bash
    composer require sylius/test-application:2.0.x-dev --dev
    ```

1. Set environment variables in `tests/TestApplication/.env`:

    ```dotenv
    DATABASE_URL=mysql://root@127.0.0.1/test_application_%kernel.environment%

    SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@AcmePlugin/tests/TestApplication/config/config.yaml"
    SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@AcmePlugin/config/routes.yaml"
    SYLIUS_TEST_APP_BUNDLES_PATH="tests/TestApplication/config/bundles.php"
    # Optionally, replace the default bundles entirely
    SYLIUS_TEST_APP_BUNDLES_REPLACE_PATH="tests/TestApplication/config/bundles.php"
    # Optionally, use a semicolon-separated list to add needed bundles
    SYLIUS_TEST_APP_BUNDLES_TO_ENABLE="Acme\Plugin\AcmePlugin"
    ```

    > 💡 The values provided above are examples and should be adjusted for your plugin.

1. Optionally, return conditionally enabled bundles from `tests/TestApplication/bundles.php`:

    ```php
    <?php

    return [
        Acme\\Plugin\\AcmePlugin::class => ['all' => true],
    ];
    ```

1. If needed, place plugin-specific configuration files in the `tests/TestApplication/config` directory
   (e.g. `services.yaml`, `routes.yaml`) and load them by env variables.

1. If your plugin requires additional JavaScript dependencies, add them to `tests/TestApplication/package.json`.
   You can also remove existing dependencies from the default `package.json.dist` using the `removeDependencies` and `removeDevDependencies` keys:

    ```json
    {
        "dependencies": {
            "trix": "^2.0.0"
        },
        "removeDevDependencies": [
            "tom-select"
        ]
    }
    ```

   This file will be merged with the main TestApplication `package.json` and any packages listed 
   under `removeDependencies` or `removeDevDependencies` will be omitted.

1. If your plugin requires entity extensions, add them in `tests/TestApplication/src/Entity` and ensure:

    - Doctrine mappings are configured:

        ```
        doctrine:
            orm:
                entity_managers:
                    default:
                        mappings:
                            TestApplication:
                                is_bundle: false
                                type: attribute
                                dir: '%kernel.project_dir%/../../../tests/TestApplication/src/Entity'
                                prefix: Tests\Acme\Plugin\TestApplication
        ```
      
    - The namespace is registered properly in the autoloader, in `composer.json` file

        ```json
        {
            "autoload-dev": {
                "psr-4": {
                    "Tests\\Acme\\Plugin\\TestApplication\\": "tests/TestApplication/src/"
                }
            }
        }

1. Build the TestApplication in a Plugin:

    ```bash
    vendor/bin/console doctrine:database:create
    vendor/bin/console doctrine:migration:migrate -n
    vendor/bin/console sylius:fixtures:load -n
    
    (cd vendor/sylius/test-application && yarn install)
    (cd vendor/sylius/test-application && yarn build)
    vendor/bin/console assets:install
    ```

1. Run your server locally:

    ```bash
    symfony serve --dir=vendor/sylius/test-application/public
    ```

## Example usage

See an example implementation in [the pull request](https://github.com/Sylius/CmsPlugin/pull/53) to Sylius/CmsPlugin.

## Community

For online communication, we invite you to chat with us & other users on [Sylius Slack](https://sylius-devs.slack.com/).

## License

This package is completely free and released under the MIT License.

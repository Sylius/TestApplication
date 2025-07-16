<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

Test Application
================

The Test Application is a shared testing environment designed to simplify Sylius plugin development. Instead of setting up
a full application in every plugin, you now use a common, pre-configured application maintained by the Sylius team.

## Purpose

Previously, each plugin had to maintain its own copy of a test application, leading to duplicated configuration,
maintenance overhead, and version incompatibilities.

This package solves that problem by:

- Extracting a reusable, standalone test application
- Providing an official, centrally maintained solution by the Sylius team
- Simplifying the setup and execution of tests within plugins
- Creating versioned variants aligned with specific Sylius versions (e.g. `1.14`, `2.0`, etc.)

## Documentation

For more information about the **Test Application**, and on installation and configuration instructions,
please refer to the [Sylius documentation](https://docs.sylius.com/sylius-plugins/plugins-development-guide/test-application).

## Example usage

See an example implementation in [the pull request](https://github.com/Sylius/InvoicingPlugin/pull/373) to Sylius/InvoicingPlugin.

## Community

For online communication, we invite you to chat with us & other users on [Sylius Slack](https://sylius-devs.slack.com/).

## License

This package is completely free and released under the MIT License.

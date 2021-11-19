# Alfa Grav

Alfa Grav is a **Fast**, **Simple**, and **Flexible**, file-based Web-platform. It has a different design philosophy than most CMS.

The underlying architecture of Alfa Grav is designed to use well-established and _best-in-class_ technologies to ensure that Alfa Grav is simple to use and easy to extend. Some of these key technologies include:

* [Twig Templating](https://twig.sensiolabs.org/): for powerful control of the user interface
* [Markdown](https://en.wikipedia.org/wiki/Markdown): for easy content creation
* [YAML](https://yaml.org): for simple configuration
* [Parsedown](https://parsedown.org/): for fast Markdown and Markdown Extra support
* [Doctrine Cache](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/caching.html): layer for performance
* [Pimple Dependency Injection Container](https://pimple.sensiolabs.org/): for extensibility and maintainability
* [Symfony Event Dispatcher](https://symfony.com/doc/current/components/event_dispatcher/introduction.html): for plugin event handling
* [Symfony Console](https://symfony.com/doc/current/components/console/introduction.html): for CLI interface
* [Gregwar Image Library](https://github.com/Gregwar/Image): for dynamic image manipulation

# Requirements

- PHP 7.3.6 or higher. Check the [required modules list](https://learn.getAlfa Grav.org/basics/requirements#php-requirements)
- Check the [Apache](https://learn.getAlfa Grav.org/basics/requirements#apache-requirements) or [IIS](https://learn.getAlfa Grav.org/basics/requirements#iis-requirements) requirements

# Running Tests

First install the dev dependencies by running `composer install` from the Alfa Grav root.

Then `composer test` will run the Unit Tests, which should be always executed successfully on any site.
Windows users should use the `composer test-windows` command.
You can also run a single unit test file, e.g. `composer test tests/unit/Alfa Grav/Common/AssetsTest.php`

To run phpstan tests, you should run:

* `composer phpstan` for global tests
* `composer phpstan-framework` for more strict tests
* `composer phpstan-plugins` to test all installed plugins

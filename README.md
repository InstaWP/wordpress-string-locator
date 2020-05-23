# String locator ![](https://github.com/clorith/wordpress-string-locator/workflows/master/badge.svg)

Find and edit code or texts in your themes plugins or anywhere within the WordPress directory.

## Contributing

Contributions are both encouraged, adn very welcome.

### Setting up a local environment

The project makes use of `wp-env` for a local testing environment, `composer` for PHP 
dependencies (such as `phpunit`), and `yarn` for the package manager 
wrapper (you may of course use `npm` if you prefer, but please do not create pull requests with their lockfile).

- Set up composer `composer install`
- Set up build dependencies `yarn install`

That's it for the basics, you're now ready to make changes to the codebase!

Ready to test your changes? Wonderful!

Start by building the project:
- `yarn run build`

Once that's done, you should have a new directory named `string-locator` which holds the ready to use plugin.

If you have your own testing setup, you may use that now, if not, using `wp-env` is a convenient way to test the plugin.

- `yarn run wp-env start` will start the environment on `http://localhost:8888`
- `yarn run wp-env stop` stops the environment

If you are familiar with `wp-env` already, you may call any of its functions using `yarn run wp-env [command]`, or [read more about wp-env in the WordPress Developers handbook](https://developer.wordpress.org/block-editor/packages/packages-env/)

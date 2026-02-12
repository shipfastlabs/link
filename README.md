# Link

Link local packages for development by modifying `composer.json` and `composer.lock`.

> **Requires [PHP 8.2+](https://php.net/releases/)** and [Composer 2.6+](https://getcomposer.org)

## Installation

```bash
composer require shipfastlabs/link --dev
```

## Usage

### Link a local package

```bash
composer link ../packages/my-package
```

This will:
- Add a `path` repository entry to your `composer.json`
- Update the `require` constraint to `*` so Composer resolves from the local path
- Run `composer update` to update `composer.lock` and symlink the package

### Link multiple packages with wildcards

```bash
composer link ../packages/*
```

Only link packages that are already in your `composer.lock`:

```bash
composer link ../packages/* --only-installed
```

### List linked packages

```bash
composer linked
```

### Unlink a package

```bash
composer unlink ../packages/my-package
```

This restores the original version constraint in `composer.json` and runs `composer update` to install the released version from Packagist.

### Unlink all packages

```bash
composer unlink-all
```

## How it works

Unlike [composer-link](https://github.com/SanderSander/composer-link) which works in-memory, this plugin modifies your `composer.json` and `composer.lock` files directly:

1. **`composer link`** adds a [`path` repository](https://getcomposer.org/doc/05-repositories.md#path) to `composer.json` and runs `composer update`
2. **`composer unlink`** removes the path repository, restores the original constraint, and runs `composer update`
3. Original version constraints are tracked in `vendor/composer-link.json` so they can be restored on unlink
4. Both `require` and `require-dev` packages are supported

## Development

```bash
composer lint        # Format code with Pint
composer refactor    # Run Rector refactors
composer test:types  # Static analysis with PHPStan
composer test:unit   # Unit tests with Pest
composer test        # Run the entire test suite
```

## License

Link is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.

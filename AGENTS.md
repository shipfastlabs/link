# Repository Guidelines

## Project Structure

- `src/`: Composer plugin implementation (PSR-4 `ShipFastLabs\\Link\\`).
  - `src/Commands/`: Composer commands (`LinkCommand`, `UnlinkCommand`, `UnlinkAllCommand`, `LinkedCommand`).
  - Core logic and file ops live in `LinkManager`, `ComposerJsonManipulator`, `LinkStorage`, `PathHelper`.
- `tests/`: Pest test suite (PSR-4 `Tests\\`), primarily `tests/Unit/*Test.php`.
- Generated/ignored: `vendor/`, `composer.lock`, `.phpunit.cache/` (do not commit changes here).

## Build, Test, and Development Commands

```bash
composer install        # Install dev dependencies
composer lint           # Auto-format (Laravel Pint, PSR-12)
composer test           # Full suite: lint, type-coverage, typos, tests, phpstan, rector(dry-run)
composer test:unit      # Pest with coverage (must be exactly 100)
composer test:type-coverage # Pest type coverage (must be exactly 100)
composer test:types     # PHPStan (level: max, `src/` only)
composer test:typos     # Peck spelling/typos scan
composer refactor       # Apply Rector refactors
```

CI runs on PHP 8.4 across `prefer-lowest` and `prefer-stable` dependencies and multiple OSes; keep path handling cross-platform.

## Coding Style & Naming Conventions

- PHP: `declare(strict_types=1);`, PSR-12 formatting via Pint (`pint.json`).
- Indentation: 4 spaces (`.editorconfig`), YAML uses 2 spaces.
- Naming: classes `PascalCase` in `src/`, tests `*Test.php` under `tests/Unit/`.

## Testing Guidelines

- Framework: Pest (see `phpunit.xml.dist`).
- Prefer unit tests that use temp dirs (see existing tests using `sys_get_temp_dir()`), and clean up after themselves.
- Coverage gates are strict: `composer test:unit` and `composer test:type-coverage` require 100%.

## Commit & Pull Request Guidelines

- As of February 12, 2026, `main` has no git commits locally, so no commit-message convention can be inferred.
- Follow `CONTRIBUTING.md`: keep a coherent commit history, run `composer lint` + `composer test`, and follow SemVer/Keep a Changelog.
- PRs should include: what changed, why, how to test, and any platform-specific considerations (Windows paths, symlinks).

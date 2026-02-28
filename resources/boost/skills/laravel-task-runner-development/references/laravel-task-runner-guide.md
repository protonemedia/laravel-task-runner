# laravel-task-runner development guide

For full documentation, see the README: https://github.com/protonemedia/laravel-task-runner#readme

## At a glance
Write shell scripts like Blade components and run tasks locally or remotely, with background support and assertions (built on Laravel Process).

## Local setup
- Install dependencies: `composer install`
- Keep the dev loop package-focused (avoid adding app-only scaffolding).

## Testing
- Run: `composer test` (preferred) or the repository’s configured test runner.
- Add regression tests for bug fixes.

## Notes & conventions
- Execution safety matters: escaping/quoting and remote execution boundaries.
- Prefer tests that assert rendered command lines and process results.
- Keep the component/template API stable; it's the user-facing surface.

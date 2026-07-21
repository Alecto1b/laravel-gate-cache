# Laravel Gate Cache

![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)
![Packagist Version](https://img.shields.io/packagist/v/rickselby/laravel-gate-cache)

Laravel Gate Cache adds a caching layer to Laravel's Gate. Within one application lifecycle, an authorization callback is evaluated once for each unique ability and argument combination; later identical checks reuse the result, including denied results.

## Requirements

| Package | Laravel | PHP |
|---|---|---|
| 3.x (current) | 10.x–13.x | ^8.1 |

The compatibility suite covers Laravel 10 through 13 with their matching Orchestra Testbench versions. Laravel 13 is tested on PHP 8.3 and PHP 8.5.

## Installation

Install the package with Composer:

```bash
composer require rickselby/laravel-gate-cache
```

Laravel auto-discovers `GateCacheProvider`, which replaces the standard Gate contract binding with `GateCache`.

## Usage

Use Laravel authorization as usual; no package-specific API is required:

```php
use Illuminate\Support\Facades\Gate;

if (Gate::allows('update', $post)) {
    // The first identical check evaluates the ability; later checks reuse it.
}
```

This is particularly useful when a view repeats the same checks:

```blade
@foreach ($posts as $post)
    @can('add-posts') Add @endcan
    @can('edit-posts', $post) Edit @endcan
    @can('delete-posts', $post) Delete @endcan
@endforeach
```

Abilities and arguments remain separate cache dimensions: a different ability or different argument produces a separate authorization evaluation. Arguments should have stable JSON representations because the existing cache-key format uses `json_encode()`.

## Lifecycle limitations

The Gate binding is a singleton in Laravel's application container. In a traditional Laravel HTTP lifecycle, that makes the cache request-local because the application container is rebuilt for each request.

If an environment reuses the same application container across requests or jobs, the cache can live for that longer container lifecycle. This package does not currently provide a worker reset hook and does not claim Octane or other long-running-worker isolation; validate and manage that lifecycle before using it in such an environment.

## License

This package is declared as MIT-licensed in [`composer.json`](composer.json).

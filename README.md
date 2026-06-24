<h1 align="center">OpenSearch Scout Driver</h1>

<p align="center">An OpenSearch driver for Laravel Scout.</p>

<p align="center">
<a href="https://github.com/DirectoryTree/OpenSearchScoutDriver/actions"><img src="https://img.shields.io/github/actions/workflow/status/DirectoryTree/OpenSearchScoutDriver/run-tests.yml?branch=master&style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-scout-driver"><img src="https://img.shields.io/packagist/v/directorytree/opensearch-scout-driver.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-scout-driver"><img src="https://img.shields.io/packagist/dt/directorytree/opensearch-scout-driver.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/directorytree/opensearch-scout-driver"><img src="https://img.shields.io/packagist/l/directorytree/opensearch-scout-driver.svg?style=flat-square"></a>
</p>

---

## Installation

Install the package with Composer:

```bash
composer require directorytree/opensearch-scout-driver
```

Publish the Scout configuration file if your application has not already done so:

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

Set Scout to use OpenSearch:

```php
'driver' => env('SCOUT_DRIVER', 'opensearch'),
```

Publish the OpenSearch client configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchClient\OpenSearchClientServiceProvider"
```

Publish the OpenSearch Scout configuration:

```bash
php artisan vendor:publish --provider="DirectoryTree\OpenSearchScoutDriver\OpenSearchScoutServiceProvider"
```

## Configuration

Configure the OpenSearch client connection in `config/opensearch-client.php`:

```php
'default' => env('OPENSEARCH_CONNECTION', 'default'),

'connections' => [
    'default' => [
        'hosts' => [
            env('OPENSEARCH_HOST', 'localhost:9200'),
        ],
    ],
],
```

The Scout driver configuration is published to `config/opensearch-scout.php`:

```php
'refresh_documents' => env('OPENSEARCH_SCOUT_REFRESH_DOCUMENTS', false),
```

## Usage

Use Scout as usual:

```php
use App\Models\Post;

$posts = Post::search('laravel')->get();
```

The driver converts Scout builders into OpenSearch search requests and uses the configured OpenSearch client connection to index, delete, flush, and search models.

## Credits

This package builds on a lot of the foundation and prior work from [Ivan Babenko](https://github.com/babenkoivan) and his Elasticsearch Laravel ecosystem packages.

We're grateful for the work he has shared with the Laravel community. If this package helps your work, consider supporting Ivan through [GitHub Sponsors](https://github.com/sponsors/babenkoivan).

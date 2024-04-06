Cloudflare images filesystem for [Flysystem](https://flysystem.thephpleague.com/docs/).

![Code Climate coverage](https://img.shields.io/codeclimate/coverage/softavis/flysystem-cloudflare)
![Packagist Downloads](https://img.shields.io/packagist/dt/softavis/flysystem-cloudflare)
![GitHub License](https://img.shields.io/github/license/softavis/flysystem-cloudflare)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/softavis/flysystem-cloudflare)

This library implement cloudflare images API to work with flysystem abstraction.

**Cloudflare Images support only Image files, obviously**

## Installation

```bash
composer require softavis/flysystem-cloudflare
```

## Usage
```php
<?php

declare(strict_types=1);

use League\Flysystem\Config;
use Softavis\Flysystem\Cloudflare\Client;
use Softavis\Flysystem\Cloudflare\CloudflareAdapter;
use Symfony\Component\HttpClient\HttpClient;

require './vendor/autoload.php';

const CLOUDFLARE_API_KEY = 'your-cloudflare-access-token';
const CLOUDFLARE_ACCOUNT_ID = 'your-cloudflare-account-id';
const CLOUDFLARE_ACCOUNT_HASH = 'your-cloudflare-account-hash';
const CLOUDFLARE_VARIANT_NAME = 'your-cloudflare-images-variant';

const CLOUDFLARE_URL = 'https://api.cloudflare.com/client/v4/accounts/%s/images/';

$client = new Client(HttpClient::createForBaseUri(sprintf(CLOUDFLARE_URL, CLOUDFLARE_ACCOUNT_ID), [
    'auth_bearer' => CLOUDFLARE_API_KEY,
]));

$adapter = new CloudflareAdapter($client);

$flysystem = new League\Flysystem\Filesystem($adapter, [
    'accountHash' => CLOUDFLARE_ACCOUNT_HASH,
    'variantName' => CLOUDFLARE_VARIANT_NAME
]);

// see http://flysystem.thephpleague.com/api/ for full list of available functionality
```

### Usage with Symfony
In `config/packages/flysystem.yaml`, add:

```yaml
services:
    Softavis\Flysystem\Cloudflare\Client:
        arguments:
            - '@Symfony\Contracts\HttpClient\HttpClientInterface'
            - "%env(CLOUDFLARE_URL)%"
            - "%env(CLOUDFLARE_ACCOUNT_ID)%"
            - "%env(CLOUDFLARE_API_KEY)%"
    Softavis\Flysystem\Cloudflare\CloudflareAdapter:
        arguments:
            - '@Softavis\Flysystem\Cloudflare\Client'

flysystem:
    storages:
        default.storage.cloudflareimages:
            adapter: 'Softavis\Flysystem\Cloudflare\CloudflareAdapter'
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Contributions are **welcome** and will be fully **credited**. We accept contributions via Pull Requests on [Github](https://github.com/RoyVoetman/flysystem-gitlab-storage).

### Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).
- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

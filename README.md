Cloudflare images filesystem for [Flysystem](https://flysystem.thephpleague.com/docs/).

![Code Climate coverage](https://img.shields.io/codeclimate/coverage/softavis/flysystem-cloudflare)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/softavis/flysystem-cloudflare/total)
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
// Create a Gitlab Client to talk with the API
$client = new Client('project-id', 'branch', 'base-url', 'personal-access-token');
   
// Create the Adapter that implements Flysystems AdapterInterface
$adapter = new GitlabAdapter(
    // Gitlab API Client
    $client,
    // Optional path prefix
    'path/prefix',
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);

// see http://flysystem.thephpleague.com/api/ for full list of available functionality
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
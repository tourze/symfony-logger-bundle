# Symfony Request File Clean Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-request-file-clean-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-request-file-clean-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-request-file-clean-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-request-file-clean-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Coverage Status](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)
[![License](https://img.shields.io/github/license/tourze/symfony-request-file-clean-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-request-file-clean-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/symfony-request-file-clean-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-request-file-clean-bundle)

A Symfony bundle that automatically cleans up temporary uploaded files after request completion in 
special environments where the system doesn't automatically remove them.

## Features

- Automatically removes temporary uploaded files after request completion
- Works with both array-style file uploads and UploadedFile objects
- High priority event listener ensures cleanup happens after all other processes
- Zero configuration required

## Installation

Install the bundle using Composer:

```bash
composer require tourze/symfony-request-file-clean-bundle
```

## Quick Start

There's no configuration required. The bundle automatically registers the event subscriber that 
handles the file cleanup after request termination.

If you're using Symfony Flex, the bundle will be automatically registered. If not, you need to 
add it to your `config/bundles.php`:

```php
<?php

return [
    // ...
    Tourze\SymfonyLoggerBundle\SymfonyLoggerBundle::class => ['all' => true],
];
```

## Configuration

This bundle works out of the box without any configuration. However, if you need to understand 
how it works internally:

- **Event Priority**: The bundle listens to `KernelEvents::TERMINATE` with priority `-9999`
- **File Types**: Supports both array-style uploads and `UploadedFile` objects
- **Cleanup Strategy**: Files are removed using PHP's `unlink()` function

## Advanced Usage

### Custom File Handling

While the bundle handles standard file uploads automatically, you can extend its functionality:

```php
<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CustomFileCleanSubscriber implements EventSubscriberInterface
{
    public function onTerminate(TerminateEvent $event): void
    {
        // Your custom cleanup logic here
        // This will run before the default cleanup
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['onTerminate', -9998], // Higher priority
        ];
    }
}
```

### Debugging File Cleanup

If you need to debug the file cleanup process, you can create a custom logger:

```php
<?php

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class LoggingFileCleanSubscriber
{
    public function __construct(private LoggerInterface $logger) {}

    public function onTerminate(TerminateEvent $event): void
    {
        foreach ($event->getRequest()->files->all() as $file) {
            $this->logger->info('Cleaning uploaded file', [
                'file' => is_array($file) ? $file['tmp_name'] ?? 'unknown' : $file->getPathname()
            ]);
        }
    }
}
```

## How It Works

The bundle registers an event subscriber that listens to the `KernelEvents::TERMINATE` event with 
a very low priority (-9999). When the request terminates, it iterates through all uploaded files 
in the request and properly removes the temporary files from the filesystem.

This helps prevent file system bloat in environments where temporary files aren't automatically 
cleaned up by the system.

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher

## Use Cases

This bundle is particularly useful in:

- Docker containers where temporary directories might not be automatically cleaned
- Shared hosting environments with limited file system cleanup
- Development environments where temporary files accumulate over time
- Production environments with custom file upload handling

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This bundle is released under the MIT License. See the [License File](LICENSE) for more information.

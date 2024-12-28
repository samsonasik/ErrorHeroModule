ErrorHeroModule
===============

[![Latest Version](https://img.shields.io/github/release/samsonasik/ErrorHeroModule.svg?style=flat-square)](https://github.com/samsonasik/ErrorHeroModule/releases)
![ci build](https://github.com/samsonasik/ErrorHeroModule/workflows/ci%20build/badge.svg)
[![Code Coverage](https://codecov.io/gh/samsonasik/ErrorHeroModule/branch/master/graph/badge.svg)](https://codecov.io/gh/samsonasik/ErrorHeroModule)
[![PHPStan](https://img.shields.io/badge/style-level%20max-brightgreen.svg?style=flat-square&label=phpstan)](https://github.com/phpstan/phpstan)
[![Downloads](https://poser.pugx.org/samsonasik/error-hero-module/downloads)](https://packagist.org/packages/samsonasik/error-hero-module)

> This is README for version ^6.0 which only support Laminas Mvc version 3 and Mezzio version 3 with php ^8.2.

> For version ^5.0, you can read at [version 5 readme](https://github.com/samsonasik/ErrorHeroModule/tree/5.x.x) which only support Laminas Mvc version 3 and Mezzio version 3 with php ^8.1.

> For version ^4.0, you can read at [version 4 readme](https://github.com/samsonasik/ErrorHeroModule/tree/4.x.x) which only support Laminas Mvc version 3 and Mezzio version 3 with php ^8.0.

Introduction
------------

ErrorHeroModule is a module for Error Logging (DB and Mail) your Laminas Mvc 3 Application, and Mezzio 3 for Exceptions in 'dispatch.error' or 'render.error' or during request and response, and [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php).

Features
--------

- [x] Save to DB with Db Writer Adapter.
- [x] Log Exception (dispatch.error and render.error) and PHP Errors in all events process.
- [x] Support excludes [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php) (eg: exclude E_USER_DEPRECATED or specific E_USER_DEPRECATED with specific message) in config settings.
- [x] Support excludes [PHP Exception](http://php.net/manual/en/spl.exceptions.php) (eg: Exception class or classes that extends it or specific exception class with specific message) in config settings.
- [x] Handle only once log error for same error per configured time range.
- [x] Set default page (web access) or default message (console access) for error if configured 'display_errors' = 0.
- [x] Set default content when request is XMLHttpRequest via 'ajax' configuration.
- [x] Set default content when there is [no template service](https://github.com/mezzio/mezzio-template/blob/9b6c2e06f8c1d7e43750f72b64cc749552f2bdbe/src/TemplateRendererInterface.php) via 'no_template' configuration (Mezzio 3).
- [x] Provide request information ( http method, raw data, body data, query data, files data, cookie data, and ip address).
- [x] Send Mail
  - [x] many receivers to listed configured email
  - [x] with include $_FILES into attachments on upload error (configurable to be included or not).

Installation
------------

**1. Import the following SQL for Mysql**
```sql
DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` int(11) NOT NULL,
  `event` text NOT NULL,
  `url` varchar(2000) NOT NULL,
  `file` varchar(2000) NOT NULL,
  `line` int(11) NOT NULL,
  `error_type` varchar(255) NOT NULL,
  `trace` text NULL,
  `request_data` text NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
```
> If you use other RDBMS, you may follow the `log` table structure above.

**2. Setup your Laminas\Db\Adapter\AdapterInterface service or your Doctrine\ORM\EntityManager service config**

You can use 'db' (with _Laminas\Db_) config or 'doctrine' (with _DoctrineORMModule_) config that will be transformed to be usable with `Laminas\Log\Writer\Db`.

```php
<?php
// config/autoload/local.php
return [
    'db' => [
        'username' => 'mysqluser',
        'password' => 'mysqlpassword',
        'driver'   => 'pdo_mysql',
        'database' => 'mysqldbname',
        'host'     => 'mysqlhost',
        'driver_options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        ],
    ],
];
```

**OR**

```php
<?php
// config/autoload/local.php
return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => 'Doctrine\DBAL\Driver\PDO\MySql\Driver',
                'params' => [
                    'user'     => 'mysqluser',
                    'password' => 'mysqlpassword',
                    'dbname'   => 'mysqldbname',
                    'host'     => 'mysqlhost',
                    'port'     => '3306',
                    'driverOptions' => [
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                    ],
                ],
            ],
        ],
    ]
];
```

> If you use other RDBMS, you may configure your own `db` or `doctrine` config.

**3. Require this module uses [composer](https://getcomposer.org/).**

```sh
composer require samsonasik/error-hero-module
```

**4. Copy config**

***a. For [Laminas Mvc](https://docs.laminas.dev/tutorials/getting-started/overview/) application, copy `error-hero-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist | config/autoload/error-hero-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist config/autoload/error-hero-module.local.php
```

***b. For [Mezzio](https://docs.mezzio.dev/mezzio/) application, copy `mezzio-error-hero-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                                  | destination                                            |
|-----------------------------------------------------------------------------------------|--------------------------------------------------------|
|  vendor/samsonasik/error-hero-module/config/mezzio-error-hero-module.local.php.dist | config/autoload/mezzio-error-hero-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/error-hero-module/config/mezzio-error-hero-module.local.php.dist config/autoload/mezzio-error-hero-module.local.php
```

When done, you can modify logger service named `ErrorHeroModuleLogger` and `error-hero-module` config in your's local config:

```php
<?php
// config/autoload/error-hero-module.local.php or config/autoload/mezzio-error-hero-module.local.php

use Laminas\Db\Adapter\AdapterInterface;

return [

    'log' => [
        'ErrorHeroModuleLogger' => [
            'writers' => [

                [
                    'name' => 'db',
                    'options' => [
                        'db'     => AdapterInterface::class,
                        'table'  => 'log',
                        'column' => [
                            'timestamp' => 'date',
                            'priority'  => 'type',
                            'message'   => 'event',
                            'extra'     => [
                                'url'          => 'url',
                                'file'         => 'file',
                                'line'         => 'line',
                                'error_type'   => 'error_type',
                                'trace'        => 'trace',
                                'request_data' => 'request_data'
                            ],
                        ],
                        'formatter' => [
                            'name' => 'db',
                            'options' => [
                                'dateTimeFormat' => 'Y-m-d H:i:s',
                            ],
                        ],
                    ],
                ],

            ],
        ],
    ],

    'error-hero-module' => [
	// it's for the enable/disable the logger functionality
        'enable' => true,

        // default to true, if set to true, then you can see sample:
        // 1. /error-preview page ( ErrorHeroModule\Controller\ErrorPreviewController )
        // 2. errorheromodule:preview command ( ErrorHeroModule\Command\Preview\ErrorPreviewConsoleCommand ) via
        //       php public/index.php error-preview
        //
        // for Mezzio ^3.0.0, the disable error-preview page is by unregister 'error-preview' from config/routes
        //
        //
        // otherwise(false), you can't see them, eg: on production env.
        'enable-error-preview-page' => true,

        'display-settings' => [

            // excluded php errors ( http://www.php.net/manual/en/errorfunc.constants.php )
            'exclude-php-errors' => [

                // can be specific error
                \E_USER_DEPRECATED,

                // can be specific error with specific message
                [\E_WARNING, 'specific error message'],

            ],

            // excluded exceptions
            'exclude-exceptions' => [

                // can be an Exception class or class extends Exception class
                \App\Exception\MyException::class,

                // can be specific exception with specific message
                [\RuntimeException::class, 'specific exception message'],

                // or specific Error class with specific message
                [\Error::class, 'specific error message'],

            ],

            // show or not error
            'display_errors'  => 0,

            // if enable and display_errors = 0, the page will bring layout and view
            'template' => [
                // non laminas-view (plates, twig) for Mezzio not need a layout definition
                // as layout defined in the view
                'layout' => 'layout/layout',
                'view'   => 'error-hero-module/error-default'
            ],

            // for Mezzio, when container doesn't has \Mezzio\Template\TemplateRendererInterface service
            // if enable, and display_errors = 0, then show a message under no_template config
            'no_template' => [
                'message' => <<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
            ],

            // if enable and display_errors = 0, the console will bring message for laminas-mvc
            'console' => [
                'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
            ],
            // if enable, display_errors = 0, and request XMLHttpRequest
            // on this case, the "template" key will be ignored.
            'ajax' => [
                'message' => <<<json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Internal Server Error",
    "status": 500,
    "detail": "We have encountered a problem and we can not fulfill your request. An error report has been generated and sent to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
            ],
        ],

        'logging-settings' => [
            // time range for same error, file, line, url, message to be re-logged
	        // in seconds range, 86400 means 1 day
            'same-error-log-time-range' => 86400,
        ],

        'email-notification-settings' => [
            // set to true to activate email notification on log error event
            'enable' => false,

            // Laminas\Mail\Message instance registered at service manager
            'mail-message'   => 'YourMailMessageService',

            // Laminas\Mail\Transport\TransportInterface instance registered at service manager
            'mail-transport' => 'YourMailTransportService',

            // email sender
            'email-from'    => 'Sender Name <sender@host.com>',

            // to include or not $_FILES on send mail
            'include-files-to-attachments' => true,

            'email-to-send' => [
                'developer1@foo.com',
                'developer2@foo.com',
            ],
        ],
    ],
    // ...
];
```

**5. Lastly, enable it**

***a. For Laminas Mvc application***

```php
// config/modules.config.php or config/application.config.php
return [
    'Application',
    'ErrorHeroModule', // <-- register here
],
```

***b. For Mezzio application***

For [laminas-mezzio-skeleton](https://github.com/mezzio/mezzio-skeleton) ^3.0.0, you need to open `config/pipeline.php` and add the `ErrorHeroModule\Middleware\Mezzio::class` middleware after default `ErrorHandler::class` registration:

```php
$app->pipe(ErrorHandler::class);
$app->pipe(ErrorHeroModule\Middleware\Mezzio::class); // here
```

and also add `error-preview` routes in `config/routes.php` (optional) :

```php
// for use laminas-router
$app->get('/error-preview[/:action]', ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction::class, 'error-preview');

// for use FastRoute
$app->get('/error-preview[/{action}]', ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction::class, 'error-preview');
```

to enable error preview page. To disable error preview page, just remove it from routes.


Give it a try!
--------------

_**Web Access**_

| URl                                                 | Preview For     |
|-----------------------------------------------------|-----------------|
| http://yourlaminasormezzioapp/error-preview         | Exception       |
| http://yourlaminasormezzioapp/error-preview/error   | Error           |
| http://yourlaminasormezzioapp/error-preview/warning | PHP E_WARNING   |
| http://yourlaminasormezzioapp/error-preview/fatal   | PHP Fatal Error |

You will get the following page if display_errors config is 0:

![error preview in web](https://cloud.githubusercontent.com/assets/459648/21668589/d4fdadac-d335-11e6-95aa-5a8cfa3f8e4b.png)

_**Console Access**_

> You can use this module in `laminas-cli`, you can install:

> ```sh
> composer require laminas/laminas-cli --sort-packages
> ```

then you can see the error-preview console:

| Command                                            | Preview For   |
|----------------------------------------------------|---------------|
| vendor/bin/laminas errorheromodule:preview         | Exception     |
| vendor/bin/laminas errorheromodule:preview error   | Error         |
| vendor/bin/laminas errorheromodule:preview warning | PHP E_WARNING |
| vendor/bin/laminas errorheromodule:preview fatal   | PHP Fatal     |

You will get the following page if display_errors config is 0:

![error preview in console](https://user-images.githubusercontent.com/459648/206887622-472d820b-f9e8-4d50-84b5-0d6a2e1c5a93.png)

You can use the error handling in your console application, by extends `BaseLoggingCommand`, like below:

```php
namespace Application\Command;

use ErrorHeroModule\Command\BaseLoggingCommand;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HelloWorld extends BaseLoggingCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        throw new Exception('some exception logged to DB');
    }
}
```

and register to your services like in the [documentation](https://docs.laminas.dev/laminas-cli/intro/).

> For production env, you can disable error-preview sample page with set `['error-hero-module']['enable-error-preview-page']` to false.

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ErrorHeroModule/blob/master/CONTRIBUTING.md)

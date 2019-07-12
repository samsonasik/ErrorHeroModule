ErrorHeroModule
===============

[![Latest Version](https://img.shields.io/github/release/samsonasik/ErrorHeroModule.svg?style=flat-square)](https://github.com/samsonasik/ErrorHeroModule/releases)
[![Build Status](https://travis-ci.org/samsonasik/ErrorHeroModule.svg?branch=master)](https://travis-ci.org/samsonasik/ErrorHeroModule)
[![Coverage Status](https://coveralls.io/repos/github/samsonasik/ErrorHeroModule/badge.svg?branch=master)](https://coveralls.io/github/samsonasik/ErrorHeroModule?branch=master)
[![PHPStan](https://img.shields.io/badge/style-level%20max-brightgreen.svg?style=flat-square&label=phpstan)](https://github.com/phpstan/phpstan)
[![Downloads](https://poser.pugx.org/samsonasik/error-hero-module/downloads)](https://packagist.org/packages/samsonasik/error-hero-module)

> This is README for version ^2.0 which only support ZF3 and ZF Expressive version 3 with php ^7.1.

> For version 1, you can read at [version 1 readme](https://github.com/samsonasik/ErrorHeroModule/tree/1.x.x) which still support ZF2 and ZF Expressive version 1 and 2 with php ^5.6|^7.0 support.

Introduction
------------

ErrorHeroModule is a module for Error Logging (DB and Mail) your ZF3 Mvc Application, and ZF Expressive for Exceptions in 'dispatch.error' or 'render.error' or during request and response, and [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php).

Features
--------

- [x] Save to DB with Db Writer Adapter.
- [x] Log Exception (dispatch.error and render.error) and PHP Errors in all events process.
- [x] Support excludes [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php) (eg: exclude E_USER_DEPRECATED) in config settings.
- [x] Support excludes [PHP Exception](http://php.net/manual/en/spl.exceptions.php) (eg: Exception class or classes that extends it) in config settings.
- [x] Handle only once log error for same error per configured time range.
- [x] Set default page (web access) or default message (console access) for error if configured 'display_errors' = 0.
- [x] Set default content when request is XMLHttpRequest via 'ajax' configuration.
- [x] Set default content when there is [no template service](https://github.com/zendframework/zend-expressive-template/blob/9b6c2e06f8c1d7e43750f72b64cc749552f2bdbe/src/TemplateRendererInterface.php) via 'no_template' configuration (ZF Expressive 3).
- [x] Provide request information ( http method, raw data, body data, query data, files data, cookie data, and ip address).
- [x] Send Mail
  - [x] many receivers to listed configured email
  - [x] with include $_FILES into attachments on upload error.

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

**2. Setup your Zend\Db\Adapter\Adapter service or your Doctrine\ORM\EntityManager service config**

You can use 'db' (with _Zend\Db_) config or 'doctrine' (with _DoctrineORMModule_) config that will be transformed to be usable with `Zend\Log\Writer\Db`.

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
                'driverClass' =>'Doctrine\DBAL\Driver\PDOMySql\Driver',
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

***a. For [ZF3 Mvc](https://zendframework.github.io/tutorials/getting-started/overview/) application, copy `error-hero-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist | config/autoload/error-hero-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist config/autoload/error-hero-module.local.php
```

***b. For [ZF Expressive](https://zendframework.github.io/zend-expressive/) application, copy `expressive-error-hero-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                                  | destination                                            |
|-----------------------------------------------------------------------------------------|--------------------------------------------------------|
|  vendor/samsonasik/error-hero-module/config/expressive-error-hero-module.local.php.dist | config/autoload/expressive-error-hero-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/error-hero-module/config/expressive-error-hero-module.local.php.dist config/autoload/expressive-error-hero-module.local.php
```

When done, you can modify logger service named `ErrorHeroModuleLogger` and `error-hero-module` config in your's local config:

```php
<?php
// config/autoload/error-hero-module.local.php or config/autoload/expressive-error-hero-module.local.php

use Zend\Db\Adapter\AdapterInterface;

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
        // 2. error-preview command (ErrorHeroModule\Controller\ErrorPreviewConsoleController) via
        //       php public/index.php error-preview
        //
        // for zf-expressive ^3.0.0, the disable error-preview page is by unregister 'error-preview' from config/routes
        //
        //
        // otherwise(false), you can't see them, eg: on production env.
        'enable-error-preview-page' => true,

        'display-settings' => [

            // excluded php errors ( http://www.php.net/manual/en/errorfunc.constants.php )
            'exclude-php-errors' => [
                \E_USER_DEPRECATED,
            ],

            // excluded exceptions
            'exclude-exceptions' => [
                \App\Exception\MyException::class, // can be an Exception class or class extends Exception class
            ],

            // show or not error
            'display_errors'  => 0,

            // if enable and display_errors = 0, the page will bring layout and view
            'template' => [
                // non zend-view (plates, twig) for expressive not need a layout definition
                // as layout defined in the view
                'layout' => 'layout/layout',
                'view'   => 'error-hero-module/error-default'
            ],

            // for expressive, when container doesn't has \Zend\Expressive\Template\TemplateRendererInterface service
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

            // if enable and display_errors = 0, the console will bring message for zend-mvc
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

            // Zend\Mail\Message instance registered at service manager
            'mail-message'   => 'YourMailMessageService',

            // Zend\Mail\Transport\TransportInterface instance registered at service manager
            'mail-transport' => 'YourMailTransportService',

            // email sender
            'email-from'    => 'Sender Name <sender@host.com>',

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

***a. For ZF Mvc application***

```php
// config/modules.config.php or config/application.config.php
return [
    'Application',
    'ErrorHeroModule', // <-- register here
],
```

***b. For ZF Expressive application***

> You need to use Zend\ServiceManager for service container and Zend\View for template engine.

For [zend-expressive-skeleton](https://github.com/zendframework/zend-expressive-skeleton) ^3.0.0, you need to open `config/pipeline.php` and add the `ErrorHeroModule\Middleware\Expressive::class` middleware after default `ErrorHandler::class` registration:

```php
$app->pipe(ErrorHandler::class);
$app->pipe(ErrorHeroModule\Middleware\Expressive::class); // here
```

and also add `error-preview` routes in `config/routes.php` (optional) :

```php
// for use zend-router
$app->get('/error-preview[/:action]', ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction::class, 'error-preview');

// for use FastRoute
$app->get('/error-preview[/{action}]', ErrorHeroModule\Middleware\Routed\Preview\ErrorPreviewAction::class, 'error-preview');
```

to enable error preview page. To disable error preview page, just remove it from routes.


Give it a try!
--------------

_**Web Access**_

| URl                                   | Preview For     |
|---------------------------------------|-----------------|
| http://yourzfapp/error-preview        | Exception       |
| http://yourzfapp/error-preview/error  | Error           |
| http://yourzfapp/error-preview/notice | PHP E_NOTICE    |
| http://yourzfapp/error-preview/fatal  | PHP Fatal Error |

You will get the following page if display_errors config is 0:

![error preview in web](https://cloud.githubusercontent.com/assets/459648/21668589/d4fdadac-d335-11e6-95aa-5a8cfa3f8e4b.png)

> For production env, you can disable error-preview sample page with set `['error-hero-module']['enable-error-preview-page']` to false.

_**Console Access**_

> If you use zend-mvc v3, you need to have `zendframework/zend-mvc-console` in your vendor, if you don't have, you can install it via command:

> ```sh
> composer require zendframework/zend-mvc-console --sort-packages
> ```

| Command                                   | Preview For  |
|-------------------------------------------|--------------|
| php public/index.php error-preview        | Exception    |
| php public/index.php error-preview error  | Error        |
| php public/index.php error-preview notice | PHP E_NOTICE |

You will get the following page if display_errors config is 0:

![error preview in console](https://cloud.githubusercontent.com/assets/459648/21669141/8e7690f0-d33b-11e6-99c7-eed4f1ab7edb.png)

> For production env, you can disable error-preview sample page with set `['error-hero-module']['enable-error-preview-page']` to false.

> For ZF Expressive, there is no default console implementation, so, if you want to apply it in your console in ZF Expressive, you may need to custom implementation error handler that utilize `ErrorHeroModule\Handler\Logging` service (see detailed usage at `ErrorHeroModule\Middleware\Expressive` class)

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ErrorHeroModule/blob/master/CONTRIBUTING.md)

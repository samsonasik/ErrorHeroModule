ErrorHeroModule
===============

[![Latest Version](https://img.shields.io/github/release/samsonasik/ErrorHeroModule.svg?style=flat-square)](https://github.com/samsonasik/ErrorHeroModule/releases)
[![Build Status](https://travis-ci.org/samsonasik/ErrorHeroModule.svg?branch=master)](https://travis-ci.org/samsonasik/ErrorHeroModule)
[![Coverage Status](https://coveralls.io/repos/github/samsonasik/ErrorHeroModule/badge.svg?branch=master)](https://coveralls.io/github/samsonasik/ErrorHeroModule?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/samsonasik/error-hero-module.svg?style=flat-square)](https://packagist.org/packages/samsonasik/error-hero-module)

Introduction
------------

ErrorHeroModule is a module for Error Logging your ZF2/ZF3 Mvc Application for Exceptions of 'dispatch.error', 'error.render', and PHP Errors ( E_NOTICE, E_USER_DEPRECATED, etc).

Features
--------

- [x] Save to DB with Db Writer Adapter
- [x] Log Exception (dispatch.error and render.error) and PHP Errors in all events process
- [x] Support excludes php error (eg: exclude E_USER_DEPRECATED) in config settings
- [x] Handle only once log error for same error per configured time range
- [x] Set default page (web access) or default message (console access) for error if configured 'display_errors' = 0
- [x] Request Information ( http method, raw data, query data, files data )
- [x] Send Mail to listed configured email.

Installation
------------

- Require this module uses [composer](https://getcomposer.org/).

```sh
composer require samsonasik/error-hero-module
```

- For its configuration, copy `vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist` to `config/autoload/error-hero-module.local.php`.

```sh
cp vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist config/autoload/error-hero-module.local.php
```

And configure with logger service named `ErrorHeroModuleLogger` and `error-hero-module` config:

```php
return [

    'log' => [
        'ErrorHeroModuleLogger' => [
            'writers' => [

                [
                    'name' => 'db',
                    'options' => [
                        'db'     => 'Zend\Db\Adapter\Adapter',
                        'table'  => 'log',
                        'column' => [
                            'timestamp' => 'date',
                            'priority'  => 'type',
                            'message'   => 'event',
                            'extra'     => [
                                'url'  => 'url',
                                'file' => 'file',
                                'line' => 'line',
                                'error_type' => 'error_type',
                            ],
                        ],
                    ],
                ],

            ],
        ],
    ],

    'error-hero-module' => [
        'enable' => true,
        'display-settings' => [

            // excluded php errors
            'exclude-php-errors' => [
                E_USER_DEPRECATED
            ],

            // if enable and display_errors = 0, the page will bring layout and view
            'template' => [
                'layout' => 'layout/layout',
                'view'   => 'error-hero-module/error-default'
            ],

            // if enable and display_errors = 0, the console will bring message
            'console' => [
                'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
            ],
        ],
        'logging-settings' => [
            'same-error-log-time-range' => 86400,
        ],
        'email-notification-settings' => [
            // set to true to activate email notification on log error
            'enable' => false,

            // Zend\Mail\Message instance registered at service manager
            'mail-message'   => 'YourMailMessageService',

            // Zend\Mail\Transport\TransportInterface instance registered at service manager
            'mail-transport' => 'YourMailTransportService',

            'email-to-send' => [
                'developer1@foo.com',
                'developer2@foo.com',
            ],
        ],
    ],
];
```

The "db" writer need a `log` table, make sure to import `data/db.mysql.sql` to your DB.
```
mysql -u root yourDB < vendor/samsonasik/error-hero-module/data/db.mysql.sql
```
If you use other RDBMS, you may follow the `log` table structure.

Lastly, enable it :
```php
// config/modules.config.php or config/application.config.php
return [
    'Application'
    'ErrorHeroModule', // <-- register here
],
```

Give it a try!
--------------

- http://yourzfapp/error-preview             : Exception
- http://yourzfapp/error-preview/error       : Error

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ErrorHeroModule/blob/master/CONTRIBUTING.md)

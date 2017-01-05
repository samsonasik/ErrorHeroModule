ErrorHeroModule
===============

[![Latest Version](https://img.shields.io/github/release/samsonasik/ErrorHeroModule.svg?style=flat-square)](https://github.com/samsonasik/ErrorHeroModule/releases)
[![Build Status](https://travis-ci.org/samsonasik/ErrorHeroModule.svg?branch=master)](https://travis-ci.org/samsonasik/ErrorHeroModule)
[![Coverage Status](https://coveralls.io/repos/github/samsonasik/ErrorHeroModule/badge.svg?branch=master)](https://coveralls.io/github/samsonasik/ErrorHeroModule?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/samsonasik/error-hero-module.svg?style=flat-square)](https://packagist.org/packages/samsonasik/error-hero-module)

Introduction
------------

ErrorHeroModule is a module for Error Logging (DB and Mail) your ZF2/ZF3 Mvc Application for Exceptions of 'dispatch.error', 'render.error', and [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php).

Features
--------

- [x] Save to DB with Db Writer Adapter
- [x] Log Exception (dispatch.error and render.error) and PHP Errors in all events process
- [x] Support excludes [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php) (eg: exclude E_USER_DEPRECATED) in config settings
- [x] Handle only once log error for same error per configured time range
- [x] Set default page (web access) or default message (console access) for error if configured 'display_errors' = 0
- [x] Set default content when request is XMLHttpRequest via 'ajax' configuration.
- [x] Request Information ( http method, raw data, query data, files data )
- [x] Send Mail (many receivers) to listed configured email.

Installation
------------

**1. Import the following SQL for Mysql**
```sql
DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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

You can use 'db' (with _Zend\Db_) config or 'doctrine' (with _DoctrineORMModule_) config that will be converted to be usable with `Zend\Log\Writer\Db`.

```php
// config/autoload/local.php
return [
    'db' => [
        'username' => 'mysqluser',
        'password' => 'mysqlpassword',
        'driver'   => 'pdo_mysql',
        'database' => 'mysqldbname',
        'host'     => 'mysqlhost',
        'driver_options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        ],
    ],      
];
```

**OR**

```php
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
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
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

**4. Copy `error-hero-module.local.php.dist` config to your local's autoload and configure it**

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist | config/autoload/error-hero-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/error-hero-module/config/error-hero-module.local.php.dist config/autoload/error-hero-module.local.php
```

When done, you can modify logger service named `ErrorHeroModuleLogger` and `error-hero-module` config in your's local config:

```php
<?php
// config/autoload/error-hero-module.local.php
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
        'enable' => true, // it's for the enable/disable the logger functionality
        'display-settings' => [

            // excluded php errors ( http://www.php.net/manual/en/errorfunc.constants.php )
            'exclude-php-errors' => [
                E_USER_DEPRECATED,
            ],

            // show or not error
            'display_errors'  => 0,

            // if enable and display_errors = 0, the page will bring layout and view
            'template' => [
                'layout' => 'layout/layout',               
                'view'   => 'error-hero-module/error-default'
            ],

            // if enable and display_errors = 0, the console will bring message
            'console' => [
                'message' => 'We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience.',
            ],
            // if enable, display_errors = 0, and request XMLHttpRequest
            // on this case, the "template" key will be ignored.
            'ajax' => [
                'message' => <<<json
{
    "error": "We have encountered a problem and we can not fulfill your request. An error report has been generated and send to the support team and someone will attend to this problem urgently. Please try again later. Thank you for your patience."
}
json
            ],
        ],

        'logging-settings' => [
            // time range for same error, file, url, message to be re-logged
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
];
```

**5. Lastly, enable it**
```php
// config/modules.config.php or config/application.config.php
return [
    'Application'
    'ErrorHeroModule', // <-- register here
],
```

Give it a try!
--------------

*Web Access*

| URl                                  | Preview For  |
|--------------------------------------|--------------|
| http://yourzfapp/error-preview       | Exception    |
| http://yourzfapp/error-preview/error | Error        |

You will get the following page if display_errors config is 0:

![error preview in web](https://cloud.githubusercontent.com/assets/459648/21668589/d4fdadac-d335-11e6-95aa-5a8cfa3f8e4b.png)

*Console Access*

| Command                                  | Preview For  |
|------------------------------------------|--------------|
| php public/index.php error-preview       | Exception    |
| php public/index.php error-preview error | Error        |

You will get the following page if display_errors config is 0:

[img]

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ErrorHeroModule/blob/master/CONTRIBUTING.md)

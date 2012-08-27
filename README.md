Sample, skeleton module for use with the ZF2 MVC layer.

### Post install: db schema
	CREATE TABLE IF NOT EXISTS `locale` (
	  	`locale_id` char(5) NOT NULL,
	  	`locale_plural_forms` varchar(255) DEFAULT NULL,
	  	PRIMARY KEY (`locale_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE IF NOT EXISTS `message` (
	  	`message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  	`locale_id` char(5) NOT NULL,
	  	`message_domain` varchar(255) NOT NULL,
	  	`message_key` text NOT NULL,
	  	`message_translation` text NOT NULL,
	  	`message_plural_index` tinyint(3) unsigned NOT NULL,
	  	PRIMARY KEY (`message_id`),
	  	KEY `locale_id` (`locale_id`),
	  	KEY `message_domain` (`message_domain`),
	  	CONSTRAINT `message_locale` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`locale_id`) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

### Post install: zend\db
If you don't already have a database set up, set this up in your config/autoload/db.global.php

	$dbParams = array(
	    'database'  => 'changeme',
	    'username'  => 'changeme',
	    'password'  => 'changeme',
	    'hostname'  => 'changeme',
	);

	return array(
	    'service_manager' => array(
	        'factories' => array(
	            'Zend\Db\Adapter\Adapter' => function ($sm) use ($dbParams) {
	                return new Zend\Db\Adapter\Adapter(array(
	                    'driver'    => 'pdo',
	                    'dsn'       => 'mysql:dbname='.$dbParams['database'].';host='.$dbParams['hostname'],
	                    'database'  => $dbParams['database'],
	                    'username'  => $dbParams['username'],
	                    'password'  => $dbParams['password'],
	                    'hostname'  => $dbParams['hostname'],
	                ));
	            },
	        ),
	    ),
	);

### Post install: configuration
You need to create a config file, such as config/autoload/translator-db.global.php
You need to specify the right translator as service manager factory.
You need to specify the type option of translation_db as database, provide an alias or instance as the db
and you can either use the default table names by not specifying any or override them.

	'service_manager' => array(
        'factories' => array(
            'translator'            => 'G403Translator\Translator\DatabaseTranslatorServiceFactory',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_db' => array(
            array(
                'type'                  => 'database',
                'db'          			=> 'Zend\Db\Adapter\Adapter',
                'locale_table_name'     => 'locale',
                'messages_table_name'   => 'message'
            ),
        ),
    ),
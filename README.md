Sample, skeleton module for use with the ZF2 MVC layer.



### Post install: zend\db
If you don't already have a database set up, set this up in your config/autoload/db.local.php

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
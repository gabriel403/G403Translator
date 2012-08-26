<?php
return array(
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
                'dbconnection'          => 'Zend\Db\Adapter\Adapter',
                'locale_table_name'     =>'locale',
                'messages_table_name'   =>'message'
            ),
        ),
    ),
);

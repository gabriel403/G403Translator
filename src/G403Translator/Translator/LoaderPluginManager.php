<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_I18n
 */

namespace G403Translator\Translator;

use Zend\I18n\Translator\LoaderPluginManager as LoaderPluginManagerO;

/**
 * Plugin manager implementation for translation loaders.
 *
 * Enforces that filters retrieved are either callbacks or instances of
 * Loader\LoaderInterface. Additionally, it registers a number of default
 * loaders.
 *
 * @category   Zend
 * @package    Zend_I18n
 * @subpackage Translator
 */
class LoaderPluginManager extends LoaderPluginManagerO
{
    /**
     * Default set of loaders
     *
     * @var array
     */
    protected $invokableClasses = array(
        'phparray' => 'Zend\I18n\Translator\Loader\PhpArray',
        'gettext'  => 'Zend\I18n\Translator\Loader\Gettext',
        'database' => 'G403Translator\Translator\Loader\Database',
    );
}

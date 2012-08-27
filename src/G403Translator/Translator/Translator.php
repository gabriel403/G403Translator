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

use Locale;
use Traversable;
use Zend\Cache;
use Zend\Cache\Storage\StorageInterface as CacheStorage;
use Zend\I18n\Exception;
use Zend\Stdlib\ArrayUtils;
use Zend\I18n\Translator\Translator as TranslatorO;

/**
 * Translator.
 *
 * @category   Zend
 * @package    Zend_I18n
 * @subpackage Translator
 */
class Translator extends TranslatorO
{
    /**
     * Instantiate a translator
     *
     * @param  array|Traversable $options
     * @return Translator
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable object; received "%s"',
                __METHOD__,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        $translator = new static();

        // locales
        if (isset($options['locale'])) {
            $locales = (array) $options['locale'];
            $translator->setLocale(array_shift($locales));
            if (count($locales) > 0) {
                $translator->setFallbackLocale(array_shift($locales));
            }
        }

        //databases
        if (isset($options['translation_db'])) {
            if (!is_array($options['translation_db'])) {
                throw new Exception\InvalidArgumentException(
                    '"translation_db" should be an array'
                );
            }

            $requiredKeys = array('dbconnection');
            foreach ($options['translation_db'] as $transdb) {
                foreach ($requiredKeys as $key) {
                    if (!isset($transdb[$key])) {
                        throw new Exception\InvalidArgumentException(
                            "'{$key}' is missing for translation db options"
                        );
                    }
                }

                $translator->addTranslationDb(
                    $transdb['type'],
                    $transdb['dbconnection'],
                    isset($transdb['locale_table_name']) ? $transdb['locale_table_name'] : 'zend_locale',
                    isset($transdb['messages_table_name']) ? $transdb['messages_table_name'] : 'zend_translate_message',
                    isset($transdb['text_domain']) ? $transdb['text_domain'] : 'default'
                );
            }

        }

        // patterns
        if (isset($options['translation_patterns'])) {
            if (!is_array($options['translation_patterns'])) {
                throw new Exception\InvalidArgumentException(
                    '"translation_patterns" should be an array'
                );
            }

            $requiredKeys = array('type', 'base_dir', 'pattern');
            foreach ($options['translation_patterns'] as $pattern) {
                foreach ($requiredKeys as $key) {
                    if (!isset($pattern[$key])) {
                        throw new Exception\InvalidArgumentException(
                            "'{$key}' is missing for translation pattern options"
                        );
                    }
                }

                $translator->addTranslationPattern(
                    $pattern['type'],
                    $pattern['base_dir'],
                    $pattern['pattern'],
                    isset($pattern['text_domain']) ? $pattern['text_domain'] : 'default'
                );
            }
        }

        // files
        if (isset($options['translation_files'])) {
            if (!is_array($options['translation_files'])) {
                throw new Exception\InvalidArgumentException(
                    '"translation_files" should be an array'
                );
            }

            $requiredKeys = array('type', 'filename');
            foreach ($options['translation_files'] as $file) {
                foreach ($requiredKeys as $key) {
                    if (!isset($file[$key])) {
                        throw new Exception\InvalidArgumentException(
                            "'{$key}' is missing for translation file options"
                        );
                    }
                }

                $translator->addTranslationFile(
                    $file['type'],
                    $file['filename'],
                    isset($file['text_domain']) ? $file['text_domain'] : 'default',
                    isset($file['locale']) ? $file['locale'] : null
                );
            }
        }

        // cache
        if (isset($options['cache'])) {
            if ($options['cache'] instanceof CacheStorage) {
                $translator->setCache($options['cache']);
            } else {
                $translator->setCache(Cache\StorageFactory::factory($options['cache']));
            }
        }

        return $translator;
    }

    /**
     * Add a translation db.
     *
     * @param  string $type
     * @param  string $filename
     * @param  string $textDomain
     * @param  string $locale
     * @return Translator
     */
    public function addTranslationDb(
        $type,
        $dbconnection,
        $locale_table_name = 'zend_locale',
        $messages_table_name ='zend_translate_message',
        $textDomain = 'default',
        $locale = null
    ) {
        $locale = $locale ?: '*';

        if (!isset($this->translationDb[$textDomain])) {
            $this->translationDb[$textDomain] = array();
        }

        $this->translationDb[$textDomain][$locale] = array(
            'type'                  => $type,
            'dbconnection'          => $dbconnection,
            'locale_table_name'     => $locale_table_name,
            'messages_table_name'   => $messages_table_name

        );

        return $this;
    }
    /**
     * Load messages for a given language and domain.
     *
     * @param  string $textDomain
     * @param  string $locale
     * @return void
     */
    protected function loadMessages($textDomain, $locale)
    {
        if (!isset($this->messages[$textDomain])) {
            $this->messages[$textDomain] = array();
        }

        if (null !== ($cache = $this->getCache())) {
            $cacheId = 'Zend_I18n_Translator_Messages_' . md5($textDomain . $locale);

            if (null !== ($result = $cache->getItem($cacheId))) {
                $this->messages[$textDomain][$locale] = $result;
                return;
            }
        }

        // Try to load from pattern
        if (isset($this->patterns[$textDomain])) {
            foreach ($this->patterns[$textDomain] as $pattern) {
                $filename = $pattern['baseDir']
                          . '/' . sprintf($pattern['pattern'], $locale);
                if (is_file($filename)) {
                    $this->messages[$textDomain][$locale] = $this->getPluginManager()
                         ->get($pattern['type'])
                         ->load($filename, $locale);
                }
            }
        }

        if(isset($this->translationDb[$textDomain])) {
            foreach ($this->translationDb[$textDomain] as $transdb) {
                $this->messages[$textDomain][$locale] = $this->getPluginManager()
                     ->get($transdb['type'])
                     ->load($transdb, $locale);
            }
        }

        // Load concrete files, may override those loaded from patterns
        foreach (array($locale, '*') as $currentLocale) {
            if (!isset($this->files[$textDomain][$currentLocale])) {
                continue;
            }

            $file = $this->files[$textDomain][$currentLocale];
            $this->messages[$textDomain][$locale] = $this->getPluginManager()
                 ->get($file['type'])
                 ->load($file['filename'], $locale);

            unset($this->files[$textDomain][$currentLocale]);
        }

        // Cache the loaded text domain
        if ($cache !== null) {
            $cache->setItem($cacheId, $this->messages[$textDomain][$locale]);
        }
    }
}

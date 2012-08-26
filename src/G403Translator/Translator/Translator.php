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
use Zend\I18n\Translator\Translator as TranslatorO

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
     * Messages loaded by the translator.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Files used for loading messages.
     *
     * @var array
     */
    protected $files = array();

    /**
     * Patterns used for loading messages.
     *
     * @var array
     */
    protected $patterns = array();

    /**
     * Db connections for loading messages
     *
     * @var array
     */
    protected $translationDb = array();

    /**
     * Default locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Locale to use as fallback if there is no translation.
     *
     * @var string
     */
    protected $fallbackLocale;

    /**
     * Translation cache.
     *
     * @var CacheStorage
     */
    protected $cache;

    /**
     * Plugin manager for translation loaders.
     *
     * @var LoaderPluginManager
     */
    protected $pluginManager;

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
     * Set the default locale.
     *
     * @param  string $locale
     * @return Translator
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get the default locale.
     *
     * @return string
     */
    public function getLocale()
    {
        if ($this->locale === null) {
            $this->locale = Locale::getDefault();
        }

        return $this->locale;
    }

    /**
     * Set the fallback locale.
     *
     * @param  string $locale
     * @return Translator
     */
    public function setFallbackLocale($locale)
    {
        $this->fallbackLocale = $locale;
        return $this;
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }

    /**
     * Sets a cache
     *
     * @param  CacheStorage $cache
     * @return Translator
     */
    public function setCache(CacheStorage $cache = null)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Returns the set cache
     *
     * @return CacheStorage The set cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the plugin manager for translation loaders
     *
     * @param  LoaderPluginManager $pluginManager
     * @return Translator
     */
    public function setPluginManager(LoaderPluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        return $this;
    }

    /**
     * Retrieve the plugin manager for translation loaders.
     *
     * Lazy loads an instance if none currently set.
     *
     * @return LoaderPluginManager
     */
    public function getPluginManager()
    {
        if (!$this->pluginManager instanceof LoaderPluginManager) {
            $this->setPluginManager(new LoaderPluginManager());
        }

        return $this->pluginManager;
    }

    /**
     * Translate a message.
     *
     * @param  string $message
     * @param  string $textDomain
     * @param  string $locale
     * @return string
     */
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        $locale      = ($locale ?: $this->getLocale());
        $translation = $this->getTranslatedMessage($message, $locale, $textDomain);

        if ($translation !== null && $translation !== '') {
            return $translation;
        }

        if (null !== ($fallbackLocale = $this->getFallbackLocale())
            && $locale !== $fallbackLocale
        ) {
            return $this->translate($message, $textDomain, $fallbackLocale);
        }

        return $message;
    }

    /**
     * Translate a plural message.
     *
     * @param  string      $singular
     * @param  string      $plural
     * @param  int         $number
     * @param  string      $textDomain
     * @param  string|null $locale
     * @return string
     * @throws Exception\OutOfBoundsException
     */
    public function translatePlural(
        $singular,
        $plural,
        $number,
        $textDomain = 'default',
        $locale = null
    ) {
        $locale      = $locale ?: $this->getLocale();
        $translation = $this->getTranslatedMessage($singular, $locale, $textDomain);

        if ($translation === null || $translation === '') {
            if (null !== ($fallbackLocale = $this->getFallbackLocale())
                && $locale !== $fallbackLocale
            ) {
                return $this->translatePlural(
                    $singular,
                    $plural,
                    $number,
                    $textDomain,
                    $fallbackLocale
                );
            }

            return ($number == 1 ? $singular : $plural);
        }

        $index = $this->messages[$textDomain][$locale]
                      ->getPluralRule()
                      ->evaluate($number);

        if (!isset($translation[$index])) {
            throw new Exception\OutOfBoundsException(sprintf(
                'Provided index %d does not exist in plural array', $index
            ));
        }

        return $translation[$index];
    }

    /**
     * Get a translated message.
     *
     * @param  string $message
     * @param  string $locale
     * @param  string $textDomain
     * @return string|null
     */
    protected function getTranslatedMessage(
        $message,
        $locale = null,
        $textDomain = 'default'
    ) {
        if ($message === '') {
            return '';
        }

        if (!isset($this->messages[$textDomain][$locale])) {
            $this->loadMessages($textDomain, $locale);
        }

        if (!isset($this->messages[$textDomain][$locale][$message])) {
            return null;
        }

        return $this->messages[$textDomain][$locale][$message];
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
     * Add a translation file.
     *
     * @param  string $type
     * @param  string $filename
     * @param  string $textDomain
     * @param  string $locale
     * @return Translator
     */
    public function addTranslationFile(
        $type,
        $filename,
        $textDomain = 'default',
        $locale = null
    ) {
        $locale = $locale ?: '*';

        if (!isset($this->files[$textDomain])) {
            $this->files[$textDomain] = array();
        }

        $this->files[$textDomain][$locale] = array(
            'type'     => $type,
            'filename' => $filename,
        );

        return $this;
    }

    /**
     * Add multiple translations with a pattern.
     *
     * @param  string $type
     * @param  string $baseDir
     * @param  string $pattern
     * @param  string $textDomain
     * @return Translator
     */
    public function addTranslationPattern(
        $type,
        $baseDir,
        $pattern,
        $textDomain = 'default'
    ) {
        if (!isset($this->patterns[$textDomain])) {
            $this->patterns[$textDomain] = array();
        }

        $this->patterns[$textDomain][] = array(
            'type'    => $type,
            'baseDir' => rtrim($baseDir, '/'),
            'pattern' => $pattern,
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

<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Composer\Autoload\ClassLoader;

/**
 * Class Delusion
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Delusion
{
    /**
     * Loads class if it's not in the black list.
     */
    const STRATEGY_ALLOW = 1;
    /**
     * Loads class if it's in a white list.
     */
    const STRATEGY_DENY = 2;
    /**
     * @var Delusion
     */
    private static $instance;
    /**
     * @var ClassLoader
     */
    private $composer;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var ClassBehavior[]
     */
    private $static_classes = [];
    /**
     * @var Suggestible[]
     */
    private $suggests = [];
    /**
     * @var int
     */
    private $strategy = self::STRATEGY_ALLOW;
    /**
     * @var array
     */
    private $white_list = [];
    /**
     * @var array
     */
    private $black_list = ['Delusion'];
    /**
     * @var string
     */
    private $prefix;

    /**
     * Init Delusion.
     *
     * @throws \RuntimeException
     */
    private function __construct()
    {
        $this->prefix = sprintf('___delusion_%s___', substr(sha1(rand()), 0, 5));
        $autoLoaders = spl_autoload_functions();
        $this->composer = $this->findComposer($autoLoaders);
        spl_autoload_register([$this, 'loadClass'], true, true);
        stream_filter_register('delusion.loader', 'Delusion\\Injector');
    }

    /**
     * Starts perform magic.
     *
     * @return Delusion
     */
    public static function injection()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Check if we have a file to transform.
     *
     * @return bool
     */
    public function hasFile()
    {
        return $this->fileName !== null;
    }

    /**
     * Get filename to transform.
     *
     * @return string
     */
    public function getFileName()
    {
        $filename = $this->fileName;
        $this->fileName = null;

        return $filename;
    }

    /**
     * Get list of classes which will not be transformed.
     *
     * @return array
     */
    public function getBlackList()
    {
        return $this->black_list;
    }

    /**
     * Set list of classes which will not be transformed.
     *
     * @param array $black_list
     */
    public function setBlackList(array $black_list)
    {
        array_push($black_list, 'Delusion');
        $black_list = array_map([$this, 'formatClass'], $black_list);
        $black_list = array_unique($black_list);
        $this->black_list = array_values($black_list);
    }

    /**
     * Add a namespace to black list.
     *
     * @param string $namespace Full or part of namespace
     */
    public function addToBlackList($namespace)
    {
        $namespace = $this->formatClass($namespace);
        array_push($this->black_list, $namespace);
        $this->black_list = array_unique($this->black_list);
    }

    /**
     * Remove from black list all namespaces which starts with specified namespace.
     *
     * @param string $namespace
     */
    public function removeFromBlackList($namespace)
    {
        $namespace = $this->formatClass($namespace);
        if ($namespace !== 'Delusion') {
            foreach ($this->black_list as $i => $pattern) {
                if (strpos($pattern, $namespace) === 0) {
                    unset($this->black_list[$i]);
                }
            }
            $this->black_list = array_values($this->black_list);
        }
    }

    /**
     * Get list of classes which will be transformed.
     *
     * @return array
     */
    public function getWhiteList()
    {
        return $this->white_list;
    }

    /**
     * Set list of classes which will be transformed.
     *
     * @param array $white_list
     */
    public function setWhiteList(array $white_list)
    {
        $white_list = array_map([$this, 'formatClass'], $white_list);
        $position = array_search('Delusion', $white_list);
        if ($position !== false) {
            array_splice($white_list, $position, 1);
        }
        $this->white_list = array_values(array_unique($white_list));
    }

    /**
     * Add a namespace to white list.
     *
     * @param string $namespace Full or part of namespace
     */
    public function addToWhiteList($namespace)
    {
        if ($namespace !== 'Delusion') {
            $namespace = $this->formatClass($namespace);
            array_push($this->white_list, $namespace);
            $this->white_list = array_unique($this->white_list);
        }
    }

    /**
     * Remove from white list all namespaces which starts with specified namespace.
     *
     * @param string $namespace
     */
    public function removeFromWhiteList($namespace)
    {
        $namespace = $this->formatClass($namespace);
        foreach ($this->white_list as $i => $pattern) {
            if (strpos($pattern, $namespace) === 0) {
                unset($this->white_list[$i]);
            }
        }
        $this->white_list = array_values($this->white_list);
    }

    /**
     * Set loads class strategy.
     *
     * @param int $strategy
     */
    public function setStrategy($strategy)
    {
        $this->strategy = intval($strategy, 10);
    }

    /**
     * Get behavior of static class.
     *
     * @param string $class
     *
     * @return ConfiguratorInterface
     * @throws \InvalidArgumentException If class does not loaded.
     */
    public function getClassBehavior($class)
    {
        $class = $this->formatClass($class);
        if (empty($this->static_classes[$class])) {
            $this->static_classes[$class] = new ClassBehavior();
        }

        return $this->static_classes[$class];
    }

    /**
     * @param string $class
     *
     * @return Suggestible
     */
    public function getSuggest($class)
    {
        $class = $this->formatClass($class);
        if (empty($this->static_classes[$class])) {
            $this->suggests[$class] = new ClassSuggest();
        }

        return $this->suggests[$class];
    }

    /**
     * Format class.
     *
     * @param string $class
     *
     * @return string
     */
    private function formatClass($class)
    {
        return ltrim($class, '\\');
    }

    /**
     * Check if namespace exists in list.
     *
     * @param array $list
     * @param string $namespace
     *
     * @return bool
     */
    private function inList(array $list, $namespace)
    {
        foreach ($list as $pattern) {
            if (strpos($namespace, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find composer ClassLoader and unregister its autoload.
     *
     * @param array $loaders
     *
     * @return ClassLoader|null
     */
    private function findComposer(array $loaders)
    {
        foreach ($loaders as $loader) {
            if ($loader[0] instanceof ClassLoader) {
                spl_autoload_unregister($loader);

                return $loader[0];
            }
        }

        return null;
    }

    /**
     * Load class by its name.
     *
     * @param string $class
     *
     * @return bool
     */
    private function loadClass($class)
    {
        $class = $this->formatClass($class);
        if ($this->strategy === self::STRATEGY_ALLOW) {
            $use_custom_loader = !$this->inList($this->black_list, $class);
        } else {
            $use_custom_loader = $this->inList($this->white_list, $class);
        }
        if ($use_custom_loader) {
            $file = $this->composer->findFile($class);
            if (empty($file)) {
                return false;
            }
            $this->fileName = $file;
            include('php://filter/read=delusion.loader/resource=' . $file);

            return true;
        } else {
            return $this->composer->loadClass($class) ? true : false;
        }
    }
}

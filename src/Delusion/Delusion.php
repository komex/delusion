<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Composer\Autoload\ClassLoader;
use TokenReflection\Broker;

/**
 * Class Delusion
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Delusion
{
    protected static $instance;
    /**
     * @var Broker
     */
    protected $broker;
    /**
     * @var bool
     */
    protected $inject = false;
    /**
     * @var ClassLoader
     */
    private $composer;

    /**
     * Init Delusion.
     *
     * @throws \RuntimeException
     */
    protected function __construct()
    {
        $autoloaders = spl_autoload_functions();
        $this->composer = $this->findComposer($autoloaders);
        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->broker = new Broker(new Broker\Backend\Memory());
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
     * Get instance of Delusion.
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

    public function getMirage($class)
    {
        if (!$this->broker->hasClass($class)) {
            $file = $this->composer->findFile($class);
            $this->broker->processFile($file);
        }
        $c = $this->broker->getClass($class);
        var_dump($c->getName(), class_exists($class, false));
    }

    /**
     * Load class by its name.
     *
     * @param string $class
     */
    private function loadClass($class)
    {
        if ($this->inject) {

        } else {
            $this->composer->loadClass($class);
        }
    }
}

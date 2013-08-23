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
     * @var ClassLoader
     */
    private static $composer;

    public function injection()
    {
        if (!empty(self::$composer)) {
            throw new \RuntimeException('Delusion is already injected.');
        }
        $autoloaders = spl_autoload_functions();
        self::$composer = $this->findComposer($autoloaders);
        spl_autoload_register([$this, 'loadClass'], true, true);
    }

    public function loadClass($class)
    {

    }

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
}

<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Composer\Autoload\ClassLoader;
use TokenReflection\Broker;
use TokenReflection\ReflectionMethod;
use TokenReflection\ReflectionConstant;
use TokenReflection\ReflectionParameter;
use TokenReflection\ReflectionProperty;

/**
 * Class Delusion
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Delusion extends \php_user_filter
{
    /**
     * @var Delusion
     */
    private static $instance;
    /**
     * @var Broker
     */
    private $broker;
    /**
     * @var bool
     */
    private $inject = false;
    /**
     * @var ClassLoader
     */
    private $composer;
    /**
     * @var string
     */
    private $current_class;

    /**
     * Init Delusion.
     *
     * @throws \RuntimeException
     */
    private function __construct()
    {
        $autoloaders = spl_autoload_functions();
        $this->composer = $this->findComposer($autoloaders);
        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->broker = new Broker(new Broker\Backend\Memory());
        stream_filter_register('delusion.loader', __CLASS__);
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

    /**
     * Spoof class.
     *
     * @param resource $in
     * @param resource $out
     * @param int $consumed
     * @param bool $closing
     *
     * @return int|void
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = $this->spoof();
            $consumed += strlen($bucket->data);
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * @return string
     */
    private function spoof()
    {
        $instance = self::$instance;
        $class = $instance->broker->getClass($instance->current_class);
        if ($class->isInterface()) {
            return $class->getSource();
        }
        $str = '<?php' . PHP_EOL;
        if ($class->getNamespaceName() != '') {
            $str .= sprintf('namespace %s;' . PHP_EOL, $class->getNamespaceName());
        }
        if ($class->getDocComment() !== false) {
            $str .= $class->getDocComment() . PHP_EOL;
        }
        if ($class->isAbstract()) {
            $str .= 'abstract ';
        }
        $str .= 'class ' . $class->getShortName();
        if ($class->getParentClassName() !== null) {
            $str .= ' extends \\' . $class->getParentClassName();
        }
        $implements = join(', \\', $class->getInterfaceNames() + ['Delusion\\PuppetThreadInterface']);
        $str .= ' implements \\' . $implements;
        $str .= ' {' . PHP_EOL;

        /** @var ReflectionConstant $constant */
        foreach ($class->getConstantReflections() as $constant) {
            $str .= '    ';
            $str .= $constant->getDocComment() ? $constant->getDocComment() . PHP_EOL : '';
            $str .= $constant->getSource() . PHP_EOL;
        }
        $str .= '    protected static $delusion_invokes = [];' . PHP_EOL;
        $str .= '    protected static $delusion_returns = [];' . PHP_EOL;
        /** @var ReflectionProperty $property */
        foreach ($class->getProperties() as $property) {
            $str .= '    ';
            $str .= $property->getDocComment() ? $property->getDocComment() . PHP_EOL : '';
            $str .= $property->getSource() . PHP_EOL;
        }
        /** @var ReflectionMethod $method */
        foreach ($class->getOwnMethods() as $method) {
            $str .= '    ';
            if ($method->isAbstract()) {
                $str .= $method->getSource();
            } else {
                $str .= $method->getDocComment() ? $method->getDocComment() . PHP_EOL : '';
                $str .= '    ';
                switch (true) {
                    case $method->isPublic():
                        $str .= 'public ';
                        break;
                    case $method->isProtected():
                        $str .= 'protected ';
                        break;
                    case $method->isPrivate():
                        $str .= 'private ';
                        break;
                }
                if ($method->isStatic()) {
                    $str .= 'static ';
                }
                $str .= 'function ' . $method->getName() . '(';
                /** @var ReflectionParameter $parameter */
                foreach ($method->getParameters() as $parameter) {
                    $str .= $parameter->getSource();
                }
                $str .= <<<END

    {
        return self::delusion(__FUNCTION__, func_get_args());
    }


END;
            }
        }

        $str .= <<<END
    protected static function delusion(\$method, array \$args)
    {
        if (empty(self::\$delusion_invokes[\$method])) {
            self::\$delusion_invokes[\$method] = [];
        }
        array_push(self::\$delusion_invokes[\$method], \$args);
        if (array_key_exists(\$method, self::\$delusion_returns)) {
            return self::\$delusion_returns[\$method];
        } else {
            \$delusion = \Delusion\Delusion::injection();
            \$closure = \$delusion::getOriginalClosure(\$method);
            return \$closure(\$args);
        }
    }

    public function delusionGetInvokesCount(\$method)
    {
        return count(self::delusionGetInvokesArguments(\$method));
    }

    public function delusionGetInvokesArguments(\$method)
    {
        if (!array_key_exists(\$method, self::\$delusion_invokes)) {
            throw new \InvalidArgumentException(
                sprintf('Delusion method "%s" not found in class %s', \$method, __CLASS__)
            );
        }
        return self::\$delusion_invokes[\$method];
    }

    public function delusionSetBehavior(\$method, \$returns)
    {
        self::\$delusion_returns[\$method] = \$returns;
    }

    public function delusionResetBehavior(\$method)
    {
        unset(self::\$delusion_returns[\$method]);
    }

END;

        return $str . '}' . PHP_EOL;
    }

    /**
     * @param string $class
     */
    public function getMiracle($class)
    {
        $this->inject = true;
        $this->loadClass($class);
        $this->inject = false;

        return new $class;
    }

    /**
     * Load class by its name.
     *
     * @param string $class
     */
    private function loadClass($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        if ($this->inject) {
            $prefix = explode('\\', $class, 2)[0];
            if (!in_array($prefix, ['TokenReflection', 'Delusion'])) {
                if (!$this->broker->hasClass($class)) {
                    $file = $this->composer->findFile($class);
                    $this->broker->processFile($file);
                    $this->current_class = $class;
                    include('php://filter/read=delusion.loader/resource=' . $file);
                }

                return;
            }
        }
        $this->composer->loadClass($class);
    }
}

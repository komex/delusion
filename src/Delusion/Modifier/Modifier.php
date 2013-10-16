<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Delusion\Modifier;

use Delusion\Transformer;

/**
 * Class Modifier
 *
 * @package Delusion\Modifier
 */
class Modifier
{
    /**
     * @var Transformer
     */
    protected $transformer;

    /**
     * @param Transformer $transformer
     */
    public function setTransformer(Transformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Get new token.
     *
     * @param int|string $type
     * @param string $value
     *
     * @return string
     */
    public function in($type, $value)
    {
        if ($type === T_CLASS) {
            $this->transformer->setModifier(new ClassModifier());
        } elseif ($type === T_TRAIT) {
            // @todo: Trait parser
        }

        return $value;
    }
}

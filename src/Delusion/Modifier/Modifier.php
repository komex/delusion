<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Delusion\Modifier;

use Delusion\Filter;

/**
 * Class Modifier
 *
 * @package Delusion\Modifier
 */
class Modifier
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @param Filter $filter
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
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
            $this->filter->setModifier(new ClassModifier());
        } elseif ($type === T_TRAIT) {
            // @todo: Trait parser
        }

        return $value;
    }
}

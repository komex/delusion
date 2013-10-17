<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Delusion\Modifier\Modifier;

/**
 * Class Injector
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Injector extends \php_user_filter
{
    /**
     * @var array
     */
    protected $tokens;
    /**
     * @var Modifier
     */
    protected $modifier;

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
        /** @var resource|object $bucket */
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (Delusion::injection()->hasFile()) {
                $bucket->data = $this->spoof();
                $consumed += strlen($bucket->data);
                stream_bucket_append($out, $bucket);
            }
        }

        return PSFS_PASS_ON;
    }

    /**
     * @param Modifier $modifier
     */
    public function setModifier(Modifier $modifier)
    {
        $modifier->setInjector($this);
        $this->modifier = $modifier;
    }

    /**
     * Transform original class for getting full control.
     *
     * @throws \InvalidArgumentException If couldn't open file
     * @return string
     */
    private function spoof()
    {
        $fileName = Delusion::injection()->getFileName();
        if (file_exists($fileName) && is_file($fileName) && is_readable($fileName)) {
            $this->tokens = token_get_all(file_get_contents($fileName));
            $this->setModifier(new Modifier());

            return $this->getCode();
        } else {
            throw new \InvalidArgumentException;
        }
    }

    /**
     * @return string
     */
    private function getCode()
    {
        $code = '';
        foreach ($this->tokens as $token) {
            if (is_string($token)) {
                $type = $value = $token;
            } else {
                list($type, $value) = $token;
            }
            $code .= $this->modifier->process($type, $value);
        }

        return $code;
    }
}

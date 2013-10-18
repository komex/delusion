<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Interface Suggestible
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface Suggestible
{
    /**
     * @param Configurator $configurator
     */
    public function delusionSuggestConnect(Configurator $configurator);
}

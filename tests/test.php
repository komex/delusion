<?php
/**
 * This file is a part of project.
 *
 * @author Andrey Kolchenko <komexx@gmail.com>
 */

require 'vendor/autoload.php';

$delusion = \Delusion\Delusion::injection();
$y = $delusion->getMiracle('\\Symfony\\Component\\Yaml\\Yaml');
\Symfony\Component\Yaml\Yaml::dump('');

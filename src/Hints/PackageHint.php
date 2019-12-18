<?php

namespace OsmScripts\Core\Hints;

/**
 * Hint class for working with objects parsed from Composer package `composer.json` files.
 *
 * @property string $name Package name
 * @property object $extra Additional package information in free format
 * @property string[] $bin Package scripts
 * @property object $autoload
 * @property string $version
 */
abstract class PackageHint
{

}
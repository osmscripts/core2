<?php

namespace OsmScripts\Core\Hints;

/**
 * Hint class for working with script configuration which is merged from `composer.json` files
 * of individual packages and then additionally processed.
 *
 * @property string $name Script title
 * @property CommandHint[] $commands Commands registered for the script
 */
abstract class ConfigHint
{
}
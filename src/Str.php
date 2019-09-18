<?php

namespace OsmScripts\Core;

/**
 * String functions. Adapted from Laravel \Illuminate\Support\Str class
 *
 * @property
 */
class Str extends Object_
{
    public function lower($value) {
        return mb_strtolower($value, 'UTF-8');
    }

    public function upper($value) {
        return mb_strtoupper($value, 'UTF-8');
    }

    public function studly($value) {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    public function camel($value) {
        return lcfirst($this->studly($value));
    }

    public function snake($value, $delimiter = '_') {
        return $this->lower(preg_replace('/(.)([A-Z])/u', "$1{$delimiter}$2", $value));
    }

    public function kebab($value) {
        return $this->snake($value, '-');
    }
}
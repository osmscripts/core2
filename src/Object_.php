<?php

namespace OsmScripts\Core;

/**
 * Generic base class
 */
class Object_
{
    public function __construct($data = []) {
        // allows user code to inject custom property values to be used instead of calculated ones
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }
}
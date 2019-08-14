<?php

namespace OsmScripts\Core;

class Object_
{
    public function __construct($data = []) {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }
}
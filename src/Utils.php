<?php

namespace OsmScripts\Core;

use Exception;

class Utils extends Object_
{
    public function readJsonOrFail($filename) {
        if (!is_file($filename)) {
            throw new Exception("'{$filename}' not found");
        }

        if (!($contents = file_get_contents($filename))) {
            throw new Exception("'{$filename}' not found");
        }

        if (!($result = json_decode($contents))) {
            throw new Exception("'{$filename}' is not valid JSON file");
        }

        return $result;
    }

    public function readJson($filename) {
        if (!is_file($filename)) {
            return null;
        }

        if (!($contents = file_get_contents($filename))) {
            return null;
        }

        if (!($result = json_decode($contents))) {
            return null;
        }

        return $result;
    }

    public function merge($target, $source) {
        if (is_object($target)) {
            foreach ($source as $key => $value) {
                if (property_exists($target, $key)) {
                    $target->$key = $this->merge($target->$key, $value);
                }
                else {
                    $target->$key = $value;
                }
            }

            return $target;
        }
        elseif (is_array($target)) {
            foreach ($source as $key => $value) {
                if (is_numeric($key)) {
                    $target[] = $value;
                }
                elseif (isset($target[$key])) {
                    $target[$key] = $this->merge($target[$key], $value);
                }
                else {
                    $target[$key] = $value;
                }
            }

            return $target;
        }
        else {
            return $source;
        }
    }
}
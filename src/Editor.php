<?php

namespace OsmScripts\Core;

/**
 * @property string $contents @temp
 */
class Editor extends Object_
{
    public function edit($contents, callable $callback) {
        $this->contents = $contents;

        try {
            $callback();
            return $this->contents;
        }
        finally {
            $this->contents = null;
        }
    }


    public function insertBefore($pos, $contents) {
        $this->contents = mb_substr($this->contents, 0, $pos) . $contents . mb_substr($this->contents, $pos);
    }

    public function add($contents) {
        $this->contents .= $contents;
    }

    public function last($text) {
        return mb_strrpos($this->contents, $text);
    }
}
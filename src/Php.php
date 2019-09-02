<?php

namespace OsmScripts\Core;

/**
 * @property string $contents @temp
 */
class Php extends Object_
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

    public function use_($class) {
        $use = "use {$class};\n";

        if (preg_match('/use\s+.*;\s*\R/u', $this->contents, $match, PREG_OFFSET_CAPTURE)) {
            // if there is use statement, we will insert before it
            $pos = $match[0][1];
        }
        else {
            // otherwise we insert just after opening php tag
            preg_match('/^\<\?php\R/u', $this->contents, $match);
            $pos = mb_strlen($match[0]);
            $use = "\n{$use}";
        }

        $this->insertBefore($pos, $use);
    }

    public function insertBefore($pos, $contents) {
        $this->contents = mb_substr($this->contents, 0, $pos) . $contents . mb_substr($this->contents, $pos);
    }

    public function last($text) {
        return mb_strrpos($this->contents, $text);
    }
}
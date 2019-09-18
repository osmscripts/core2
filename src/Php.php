<?php

namespace OsmScripts\Core;

class Php extends Editor
{
    public function use_($class) {
        $use = "use {$class};";

        if (mb_strpos($this->contents, $use) !== false) {
            return;
        }

        $use .= "\n";

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
}
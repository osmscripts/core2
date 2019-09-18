<?php

namespace OsmScripts\Core;

class Js extends Editor
{
    public function import($dependency, $alias = null) {
        if (!$alias) {
            $alias = mb_substr($dependency, mb_strrpos($dependency, '/') + 1);
        }

        $import = "import {$alias} from \"{$dependency}\";";

        if (mb_strpos($this->contents, $import) !== false) {
            return;
        }

        $import .= "\n";

        if (preg_match('/import\s+.*;\s*\R/u', $this->contents, $match, PREG_OFFSET_CAPTURE)) {
            // if there is import statement, we will insert before it
            $pos = $match[0][1];
        }
        else {
            // otherwise we insert just after opening php tag
            $pos = 0;
            $import .= "\n";
        }

        $this->insertBefore($pos, $import);
    }
}
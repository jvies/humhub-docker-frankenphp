<?php

declare(strict_types=1);

function recursiveCopy(string $src, string $dst): void
{
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (is_dir($src . '/' . $file)) {
            recursiveCopy($src . '/' . $file, $dst . '/' . $file);
        } else {
            copy($src . '/' . $file, $dst . '/' . $file);
        }
    }
    closedir($dir);
}

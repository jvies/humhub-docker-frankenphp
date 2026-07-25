<?php

declare(strict_types=1);

function logMessage(string $message): void
{
    global $quietLogs;
    if (!$quietLogs) {
        fwrite(STDOUT, "[entrypoint.php] " . $message . PHP_EOL);
    }
}

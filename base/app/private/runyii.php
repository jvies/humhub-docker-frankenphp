<?php

declare(strict_types=1);

/**
 * Executes a Yii console command by memory-forking and directly including the framework launcher.
 * This completely avoids executing sub-processes via shell layers, keeping it 100% Distroless compatible.
 */
function runYii(array $args, string $workingDir = '/app/public/protected'): int
{
    $escapedArgs = implode(' ', array_map('escapeshellarg', $args));
    logMessage("Invoking internal Yii console layer: yii " . $escapedArgs);

    // Ensure the pcntl extension is present for in-memory isolation
    if (!function_exists('pcntl_fork')) {
        fwrite(STDERR, "[ERROR] pcntl extension is required to execute inline framework tasks in Distroless." . PHP_EOL);
        exit(1);
    }

    $pid = pcntl_fork();

    if ($pid === -1) {
        fwrite(STDERR, "[ERROR] Could not fork memory for Yii internal command execution." . PHP_EOL);
        return 1;
    } elseif ($pid === 0) {
        // --- CHILD PROCESS ---
        // 1. Prepare environment and working directory
        $oldCwd = getcwd();
        chdir($workingDir);

        // 2. Mock the global $argv that Yii depends on
        // $argv[0] is always the script name, followed by commands and flags
        global $argv;
        $argv = array_merge([$workingDir . '/yii'], $args);
        $_SERVER['argv'] = $argv;

        // 3. Prevent HumHub / Yii from polluting output or capturing loops incorrectly if needed
        // Execute the native entry script directly in this process memory
        try {
            require $workingDir . '/yii';
            exit(0); // If the script didn't call exit internally, we exit cleanly
        } catch (\Throwable $e) {
            fwrite(STDERR, "[Yii Internal Error] " . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    } else {
        $status = 0;
        // --- PARENT PROCESS ---
        // Wait for this specific inline task to finish execution before proceeding to next entries
        pcntl_waitpid($pid, $status);

        // Return the exact exit code provided by Yii (0 = success, >0 = failure)
        return pcntl_wexitstatus($status);
    }
}

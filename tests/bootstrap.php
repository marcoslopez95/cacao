<?php

/**
 * Worktree bootstrap — patches the shared Composer autoloader so that
 * App\ classes resolve from this worktree's app/ directory first.
 *
 * This is needed because vendor/ is symlinked from the main repo, whose
 * autoloader maps App\ to /var/www/html/app (master), not this worktree.
 */
require __DIR__ . '/../vendor/autoload.php';

// Get the already-instantiated Composer ClassLoader from registered autoloaders
$loader = null;
foreach (spl_autoload_functions() as $autoloader) {
    if (is_array($autoloader) && $autoloader[0] instanceof Composer\Autoload\ClassLoader) {
        $loader = $autoloader[0];
        break;
    }
}

if ($loader !== null) {
    $worktreeBase = dirname(__DIR__);
    $loader->setPsr4('App\\', [$worktreeBase . '/app']);
    $loader->setPsr4('Database\\Factories\\', [$worktreeBase . '/database/factories']);
    $loader->setPsr4('Database\\Seeders\\', [$worktreeBase . '/database/seeders']);
}

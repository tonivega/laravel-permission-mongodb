<?php

namespace Maklad\Permission\Traits;

use Maklad\Permission\PermissionRegistrar;
use function app;

/**
 * Trait RefreshesPermissionCache
 * @package Maklad\Permission\Traits
 */
trait RefreshesPermissionCache
{
    /**
     * Refresh Permission Cache
     *
     * @return void
     */
    public static function bootRefreshesPermissionCache(): void
    {
        static::saved(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}

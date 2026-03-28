<?php

namespace Maklad\Permission;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Maklad\Permission\Contracts\PermissionInterface as Permission;

/**
 * Class PermissionRegistrar
 * @package Maklad\Permission
 */
class PermissionRegistrar
{
    protected Gate $gate;

    protected Repository $cache;

    protected string $cacheKey = 'maklad.permission.cache';

    protected string $roleCacheKey = 'maklad.permission.roles.cache';

    protected string $permissionClass;

    protected string $roleClass;

    /**
     * Whether caching is enabled at all.
     */
    protected bool $cacheEnabled;

    /**
     * Whether per-request memoization is enabled.
     */
    protected bool $requestMementoEnabled;

    /**
     * In-memory (per-request) store for permissions.
     */
    protected ?Collection $memoizedPermissions = null;

    /**
     * In-memory (per-request) store for roles.
     */
    protected ?Collection $memoizedRoles = null;

    /**
     * PermissionRegistrar constructor.
     * @param Gate $gate
     * @param Repository $cache
     */
    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate = $gate;
        $this->permissionClass = config('permission.models.permission');
        $this->roleClass = config('permission.models.role');

        $this->cacheEnabled = (bool) config('permission.cache.enabled', true);
        $this->requestMementoEnabled = (bool) config('permission.cache.request_memento', true);

        $this->cache = $this->getCacheStoreFromConfig();
    }

    /**
     * Get the cache store from the permission configuration.
     *
     * @return Repository
     */
    protected function getCacheStoreFromConfig(): Repository
    {
        $cacheDriver = config('permission.cache.store', 'default');

        if ($cacheDriver === 'default') {
            return app('cache.store');
        }

        return app('cache')->store($cacheDriver);
    }

    /**
     * Register Permissions
     *
     * @return bool
     */
    public function registerPermissions(): bool
    {
        $this->getPermissions()->map(function (Permission $permission) {
            $this->gate->define($permission->name, function (Authorizable $user) use ($permission) {
                return $user->hasPermissionTo($permission) ?: null;
            });
        });

        return true;
    }

    /**
     * Forget cached permissions (and roles).
     */
    public function forgetCachedPermissions(): void
    {
        // Clear the external cache store
        $this->cache->forget($this->cacheKey);
        $this->cache->forget($this->roleCacheKey);

        // Clear the per-request memento
        $this->memoizedPermissions = null;
        $this->memoizedRoles = null;
    }

    /**
     * Get Permissions — uses cache + optional per-request memento.
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        // If per-request memento is active and we already loaded, return immediately
        if ($this->requestMementoEnabled && $this->memoizedPermissions !== null) {
            return $this->memoizedPermissions;
        }

        // If caching is disabled, go straight to the database
        if (! $this->cacheEnabled) {
            $permissions = $this->getPermissionClass()->get();
            if ($this->requestMementoEnabled) {
                $this->memoizedPermissions = $permissions;
            }
            return $permissions;
        }

        // Fetch from the configured cache store
        $permissions = $this->cache->remember(
            $this->cacheKey,
            config('permission.cache_expiration_time'),
            function () {
                return $this->getPermissionClass()->get();
            }
        );

        // Memoize for the remainder of this request
        if ($this->requestMementoEnabled) {
            $this->memoizedPermissions = $permissions;
        }

        return $permissions;
    }

    /**
     * Get Roles — uses cache + optional per-request memento.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        // If per-request memento is active and we already loaded, return immediately
        if ($this->requestMementoEnabled && $this->memoizedRoles !== null) {
            return $this->memoizedRoles;
        }

        // If caching is disabled, go straight to the database
        if (! $this->cacheEnabled) {
            $roles = $this->getRoleClass()->get();
            if ($this->requestMementoEnabled) {
                $this->memoizedRoles = $roles;
            }
            return $roles;
        }

        // Fetch from the configured cache store
        $roles = $this->cache->remember(
            $this->roleCacheKey,
            config('permission.cache_expiration_time'),
            function () {
                return $this->getRoleClass()->get();
            }
        );

        // Memoize for the remainder of this request
        if ($this->requestMementoEnabled) {
            $this->memoizedRoles = $roles;
        }

        return $roles;
    }

    /**
     * Get Permission class
     *
     * @return Application|mixed
     */
    public function getPermissionClass(): mixed
    {
        return app($this->permissionClass);
    }

    /**
     * Get Role class
     *
     * @return Application|mixed
     */
    public function getRoleClass(): mixed
    {
        return app($this->roleClass);
    }

    /**
     * Check whether caching is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Check whether per-request memoization is enabled.
     *
     * @return bool
     */
    public function isRequestMementoEnabled(): bool
    {
        return $this->requestMementoEnabled;
    }

    /**
     * Programmatically enable or disable the cache at runtime.
     *
     * @param bool $enabled
     * @return $this
     */
    public function setCacheEnabled(bool $enabled): static
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }

    /**
     * Programmatically enable or disable per-request memoization at runtime.
     *
     * @param bool $enabled
     * @return $this
     */
    public function setRequestMementoEnabled(bool $enabled): static
    {
        $this->requestMementoEnabled = $enabled;
        if (! $enabled) {
            $this->memoizedPermissions = null;
            $this->memoizedRoles = null;
        }
        return $this;
    }

    /**
     * Clear only the in-memory per-request memento (does NOT touch the external cache).
     *
     * @return void
     */
    public function clearMemento(): void
    {
        $this->memoizedPermissions = null;
        $this->memoizedRoles = null;
    }
}

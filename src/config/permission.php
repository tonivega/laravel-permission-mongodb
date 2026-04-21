<?php

return [

    'models' => [

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Maklad\Permission\Contracts\PermissionInterface` contract.
         */

        'permission' => Maklad\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Maklad\Permission\Contracts\RoleInterface` contract.
         */

        'role' => Maklad\Permission\Models\Role::class,

    ],

    'collection_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',
    ],

    /*
     * By default all permissions will be cached for 24 hours unless a permission or
     * role is updated. Then the cache will be flushed automatically.
     */

    'cache_expiration_time' => 60 * 24,

    /*
     * Cache configuration.
     *
     * 'store' - Which cache store to use for permission caching.
     *   - 'default' : use Laravel's default cache store (from CACHE_DRIVER / cache.default)
     *   - any other valid store name from config/cache.php (e.g. 'redis', 'memcached', 'array', 'file')
     *
     * 'enabled' - Whether the cache is enabled at all.
     *   - true  : permissions and roles are cached (recommended for production)
     *   - false : every check hits the database (useful for debugging)
     *
     * 'request_memento' - Whether to keep an in-memory (per-request) copy of the cached data.
     *   When enabled, subsequent permission/role lookups within the same HTTP request
     *   are served from a PHP array instead of hitting the cache backend again.
     *   This dramatically reduces round-trips to Redis / Memcached during a single request.
     *   - true  : enable per-request memoization (recommended)
     *   - false : always read from the configured cache store
     */

    'cache' => [
        'store'           => 'default',
        'enabled'         => true,
        'request_memento' => true,
    ],

    /*
     * When set to true, the required permission/role names are added to the exception
     * message. This could be considered an information leak in some contexts, so
     * the default setting is false here for safety.
     */

    'display_permission_in_exception' => false,

    /*
     * When set to true, the package will log registration exceptions.
     */

    'log_registration_exception' => true,
];

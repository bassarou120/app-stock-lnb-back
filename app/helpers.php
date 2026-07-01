<?php

if (!function_exists('currentTenantId')) {
    function currentTenantId(): ?int
    {
        return app()->bound('currentTenantId') ? app('currentTenantId') : null;
    }
}
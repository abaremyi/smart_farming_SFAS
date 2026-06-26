<?php
/** SIPIS — Permission Helper | File: helpers/PermissionHelper.php */
if (!function_exists('hasPermission')) {
    function hasPermission($perms, string $p): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin)||(!empty($currentUser)&&!empty($currentUser->is_super_admin))) return true;
        return is_array($perms) && in_array($p, $perms);
    }
}
if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($perms, array $check): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin)||(!empty($currentUser)&&!empty($currentUser->is_super_admin))) return true;
        if (!is_array($perms)) return false;
        foreach ($check as $p) { if (in_array($p,$perms)) return true; }
        return false;
    }
}
if (!function_exists('hasAllPermissions')) {
    function hasAllPermissions($perms, array $check): bool {
        global $currentUser, $isSuperAdmin;
        if (!empty($isSuperAdmin)||(!empty($currentUser)&&!empty($currentUser->is_super_admin))) return true;
        if (!is_array($perms)) return false;
        foreach ($check as $p) { if (!in_array($p,$perms)) return false; }
        return true;
    }
}
<?php

namespace Shagyt\lvcrud\Helpers\crud\Traits;
use Shagyt\lvcrud\Models\Module;

trait Access
{
    /*
    |--------------------------------------------------------------------------
    |                                   CRUD ACCESS
    |--------------------------------------------------------------------------
    */

    public function allowAccess($access)
    {
        // $this->addButtons((array)$access);
        return $this->access = array_merge(array_diff((array) $access, $this->access), $this->access);
    }

    public function denyAccess($access)
    {
        // $this->removeButtons((array)$access);
        return $this->access = array_diff($this->access, (array) $access);
    }

    /**
     * Check if a permission is enabled for a Crud Panel. Return false if not.
     *
     * @param  [string] Permission.
     *
     * @return bool
     */
    public function hasAccess($permission)
    {
        if(isset($this->module->name)) {
            return Module::hasAccess($this->module->name, $permission);
        } else if (in_array($permission, $this->access) || \Auth::user()->isSuperAdmin()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if any permission is enabled for a Crud Panel. Return false if not.
     *
     * @param  [array] Permissions.
     *
     * @return bool
     */
    public function hasAccessToAny($permission_array)
    {
        foreach ($permission_array as $key => $permission) {
            if(isset($this->module->name)) {
                return \Module::hasAccess($this->module->name, $permission);
            } else if (in_array($permission, $this->access)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all permissions are enabled for a Crud Panel. Return false if not.
     *
     * @param  [array] Permissions.
     *
     * @return bool
     */
    public function hasAccessToAll($permission_array)
    {
        foreach ($permission_array as $key => $permission) {
            if(isset($this->module->name)) {
                return \Module::hasAccess($this->module->name, $permission);
            } else if (! in_array($permission, $this->access)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a permission is enabled for a Crud Panel. Fail if not.
     *
     * @param  [string] Permission.
     * @param string $permission
     *
     * @return bool|null
     */
    public function hasAccessOrFail($permission)
    {
        if(isset($this->module->name)) {
            return \Module::hasAccess($this->module->name, $permission);
        } else if(! in_array($permission, $this->access)) {
            abort(403, trans('crud.unauthorized_access'));
        }
    }
}

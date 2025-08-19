<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Add all permissions here, including jotform.view and any others you need
        $allPermissions = [
            // Team Member
            'team_member.add', 'team_member.edit', 'team_member.delete', 'team_member.view',
            // Vendor
            'vendor.add', 'vendor.edit', 'vendor.delete', 'vendor.view',
            // User
            'user.add', 'user.edit', 'user.delete', 'user.view',
            // Secure File Uploads
            'secure_file_uploads.view',
            // JotForm
            'jotform.view', 'jotform.create', 'jotform.edit', 'jotform.delete',
            // Add more permissions as needed
        ];

        $admin = User::where('role_id', '1')->first();
        $roleSuperAdmin = $this->maybeCreateSuperAdminRole($admin);

        // Create and Assign All Permissions
        foreach ($allPermissions as $permName) {
            $permissionExist = Permission::where('name', $permName)->first();
            if (is_null($permissionExist)) {
                $permission = Permission::create([
                    'name' => $permName,
                    'group_name' => 'all',
                    'guard_name' => 'web'
                ]);
                $roleSuperAdmin->givePermissionTo($permission);
                $permission->assignRole($roleSuperAdmin);
            } else {
                $roleSuperAdmin->givePermissionTo($permissionExist);
                $permissionExist->assignRole($roleSuperAdmin);
            }
        }

        // Assign super admin role permission to superadmin user
        if ($admin) {
            $admin->assignRole($roleSuperAdmin);
        }

         // Do same for the admin guard for tutorial purposes.
         $admin = User::where('role_id', '1')->first();
         $roleSuperAdmin = $this->maybeCreateSuperAdminRole($admin);
 
    // ...existing code...
 
         // Assign super admin role permission to superadmin user

         if ($admin) {
             $admin->assignRole($roleSuperAdmin);
         }
    }

    private function maybeCreateSuperAdminRole($admin): Role
    {
        if (is_null($admin)) {
            $roleSuperAdmin = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        } else {
            $roleSuperAdmin = Role::where('name', 'superadmin')->where('guard_name', 'web')->first();
        }

        if (is_null($roleSuperAdmin)) {
            $roleSuperAdmin = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        }

        if (is_null($admin)) {
            $admin           = new User();
            $admin->first_name     = "superadmin";
            $admin->last_name     = "superadmin";
            $admin->email    = "dev@jacc.app";
            $admin->phone    = "4343434343";
            $admin->role_id = "1";
            $admin->password = Hash::make('Dev123!');
            $admin->save();
        }

         // Ensure the data is inserted into model_has_roles table manually
        DB::table('model_has_roles')->updateOrInsert([
            'role_id' => $roleSuperAdmin->id,
            'model_type' => User::class,
            'model_id' => $admin->id
        ]);

        return $roleSuperAdmin;
    }
}

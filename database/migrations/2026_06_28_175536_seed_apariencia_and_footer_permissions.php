<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            'Gestionar Apariencia',
            'Gestionar Footer'
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $superAdmin = Role::where('name', 'SUPER ADMIN')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permisos);
        }

        $admin = Role::where('name', 'ADMINISTRADOR')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permisos = [
            'Gestionar Apariencia',
            'Gestionar Footer'
        ];

        foreach ($permisos as $p) {
            $perm = Permission::where('name', $p)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
};

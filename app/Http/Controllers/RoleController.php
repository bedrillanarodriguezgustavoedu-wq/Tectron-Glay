<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['SUPER ADMIN'];

    public function index()
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->paginate(10);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function permisos(int $id)
    {
        $rol = Role::findOrFail($id);
        $this->ensureRoleCanBeEdited($rol);

        $permisos = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => $this->permissionGroup($permission->name));

        return view('admin.roles.permisos', compact('rol', 'permisos'));
    }

    public function update_permisos(Request $request, int $id): RedirectResponse
    {
        $rol = Role::findOrFail($id);
        $this->ensureRoleCanBeEdited($rol);

        $validated = $request->validate([
            'permisos' => ['sometimes', 'array'],
            'permisos.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);

        $rol->syncPermissions($validated['permisos'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('mensaje', 'Permisos del rol actualizados exitosamente.')
            ->with('icono', 'success');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => mb_strtoupper(trim((string) $request->input('name')))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('admin.roles.index')
            ->with('mensaje', 'Rol creado exitosamente.')
            ->with('icono', 'success');
    }

    public function show(int $id)
    {
        $rol = Role::withCount(['users', 'permissions'])->findOrFail($id);

        return view('admin.roles.show', compact('rol'));
    }

    public function edit(int $id)
    {
        $rol = Role::findOrFail($id);
        $this->ensureRoleCanBeEdited($rol);

        return view('admin.roles.edit', compact('rol'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $rol = Role::findOrFail($id);
        $this->ensureRoleCanBeEdited($rol);

        $request->merge(['name' => mb_strtoupper(trim((string) $request->input('name')))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($rol->id),
            ],
        ]);

        $rol->name = $validated['name'];
        $rol->save();

        return redirect()->route('admin.roles.index')
            ->with('mensaje', 'Rol actualizado exitosamente.')
            ->with('icono', 'success');
    }

    public function destroy(int $id): RedirectResponse
    {
        $rol = Role::findOrFail($id);
        $this->ensureRoleCanBeEdited($rol);

        if ($rol->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->withErrors(['role' => 'No se puede eliminar un rol que está asignado a usuarios.']);
        }

        $rol->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('mensaje', 'Rol eliminado exitosamente.')
            ->with('icono', 'success');
    }

    private function ensureRoleCanBeEdited(Role $role): void
    {
        abort_if(in_array($role->name, self::PROTECTED_ROLES, true), 403, 'Este rol del sistema está protegido.');
    }

    private function permissionGroup(string $name): string
    {
        $permission = mb_strtolower($name);

        return match (true) {
            str_contains($permission, 'dashboard'), str_contains($permission, 'admin') => 'Panel administrativo',
            str_contains($permission, 'ajuste') => 'Ajustes',
            str_contains($permission, 'rol') => 'Roles',
            str_contains($permission, 'usuario') => 'Usuarios',
            str_contains($permission, 'categor') => 'Categorías',
            str_contains($permission, 'producto') => 'Productos',
            str_contains($permission, 'pedido') => 'Pedidos',
            str_contains($permission, 'marca') => 'Marcas',
            str_contains($permission, 'oferta') => 'Ofertas',
            str_contains($permission, 'banner') => 'Banners',
            str_contains($permission, 'carrusel') => 'Carruseles',
            str_contains($permission, 'sección'), str_contains($permission, 'seccion'), str_contains($permission, 'home') => 'Página de inicio',
            str_contains($permission, 'nosotros') => 'Nosotros',
            str_contains($permission, 'contacto'), str_contains($permission, 'mensaje') => 'Contacto',
            str_contains($permission, 'soporte'), str_contains($permission, 'técnico'), str_contains($permission, 'tecnico') => 'Soporte técnico',
            str_contains($permission, 'reserva') => 'Reservas',
            str_contains($permission, 'apariencia'), str_contains($permission, 'footer') => 'Apariencia',
            default => 'Otros permisos',
        };
    }
}

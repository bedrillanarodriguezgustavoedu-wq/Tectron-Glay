<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'SUPER ADMIN');
        })->withTrashed();

        if ($buscar) {
            $query->where('name', 'like', '%'.$buscar.'%')
                ->orWhere('email', 'like', '%'.$buscar.'%');
        }

        $usuarios = $query->paginate(10);

        return view('admin.usuarios.index', compact('usuarios'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();

        return view('admin.usuarios.create', compact('roles'));
    }

    public function perfil($id)
    {
        $ajuste = Ajuste::first();
        $usuario = User::find($id);

        return view('admin.usuarios.perfil', compact('usuario', 'ajuste'));
    }

    public function update_perfil(Request $request, $id)
    {
        // return response()->json($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
        ]);

        $usuario = User::find($id);
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->save();

        return redirect()->back()
            ->with('mensaje', 'Cambios de perfil guardados exitosamente')
            ->with('icono', 'success');
    }

    public function update_password(Request $request, $id)
    {
        // return response()->json($request->all());
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $usuario = User::find($id);
        if (! password_verify($request->input('current_password'), $usuario->password)) {
            return redirect()->back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
        }

        $usuario->password = bcrypt($request->password);
        $usuario->save();

        return redirect()->back()
            ->with('mensaje', 'Contraseña actualizada exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge(['rol' => mb_strtoupper(trim((string) $request->input('rol')))]);
        $validated = $request->validate([
            'rol' => [
                'required',
                'string',
                Rule::notIn(['SUPER ADMIN']),
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $usuario = new User;
            $usuario->name = $request->name;
            $usuario->email = $request->email;
            $usuario->password = bcrypt($request->password);
            $usuario->save();

            $usuario->assignRole(Role::findByName($validated['rol'], 'web'));
        });

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Usuario creado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $usuario = User::findOrFail($id);

        return view('admin.usuarios.show', compact('usuario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $usuario = User::findOrFail($id);
        $roles = Role::all();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);
        abort_if($usuario->hasRole('SUPER ADMIN'), 403, 'No se puede modificar la cuenta SUPER ADMIN desde este formulario.');

        $request->merge(['rol' => mb_strtoupper(trim((string) $request->input('rol')))]);
        $validated = $request->validate([
            'rol' => [
                'required',
                'string',
                Rule::notIn(['SUPER ADMIN']),
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request, $usuario, $validated): void {
            $usuario->name = $request->name;
            $usuario->email = $request->email;
            if ($request->password) {
                $usuario->password = bcrypt($request->password);
            }
            $usuario->save();

            $usuario->syncRoles(Role::findByName($validated['rol'], 'web'));
        });

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Usuario actualizado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->estado = false;
        $usuario->save();
        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Usuario eliminado exitosamente')
            ->with('icono', 'success');
    }

    public function restore($id)
    {
        $usuario = User::withTrashed()->find($id);
        $usuario->restore();
        $usuario->estado = true;
        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('mensaje', 'Usuario restaurado exitosamente')
            ->with('icono', 'success');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Guardia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GuardiaManagementController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Administrador $admin */
        $admin = Auth::guard('admin')->user();
        $guardias = $admin->guardias()->with('persona.user')->get();
        
        return view('admin.guardias.index', compact('guardias'));
    }

    public function create()
    {
        return view('admin.guardias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_p' => 'required|string|max:255',
            'apellido_m' => 'required|string|max:255',
            'ci' => 'required|string|max:20|unique:personas,ci',
            'telefono' => 'required|string|max:20',
            'foto' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_ini',
        ]);

        try {
            DB::beginTransaction();

            // Crear usuario
            $user = User::create([
                'name' => $request->nombre . ' ' . $request->apellido_p . ' ' . $request->apellido_m,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'payment_status' => true,
                'monthly_fee' => 50.00,
                'due_date' => now()->addMonth(),
            ]);

            // Crear persona
            $persona = Persona::create([
                'nombre' => $request->nombre,
                'apellido_p' => $request->apellido_p,
                'apellido_m' => $request->apellido_m,
                'ci' => $request->ci,
                'telefono' => $request->telefono,
                'foto' => $request->foto ?? 'default.jpg', // Valor por defecto si no se proporciona
                'user_id' => $user->id,
            ]);

            // Crear guardia y asociarlo al administrador actual
            /** @var \App\Models\Administrador $admin */
            $admin = Auth::guard('admin')->user();
            Guardia::create([
                'estado' => true,
                'fecha_ini' => $request->fecha_ini,
                'fecha_fin' => $request->fecha_fin,
                'persona_id' => $persona->id,
                'administrador_id' => $admin->id,
            ]);

            DB::commit();

            return redirect()->route('admin.guardias.index')
                ->with('success', 'Guardia registrado exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al registrar el guardia: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Guardia $guardia)
    {
        // Verificar que el guardia pertenece al administrador actual
        /** @var \App\Models\Administrador $admin */
        $admin = Auth::guard('admin')->user();
        if ($guardia->administrador_id !== $admin->id) {
            abort(403, 'No tienes permiso para ver este guardia.');
        }

        return view('admin.guardias.show', compact('guardia'));
    }

    public function edit(Guardia $guardia)
    {
        /** @var \App\Models\Administrador $admin */
        $admin = Auth::guard('admin')->user();
        if ($guardia->administrador_id !== $admin->id) {
            abort(403, 'No tienes permiso para editar este guardia.');
        }

        return view('admin.guardias.edit', compact('guardia'));
    }

    public function update(Request $request, Guardia $guardia)
    {
        /** @var \App\Models\Administrador $admin */
        $admin = Auth::guard('admin')->user();
        if ($guardia->administrador_id !== $admin->id) {
            abort(403, 'No tienes permiso para actualizar este guardia.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|unique:personas,ci,' . $guardia->persona->id,
            'telefono' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $guardia->persona->user->id,
            'estado' => 'required|boolean',
            'fecha_ini' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_ini',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar usuario
            $guardia->persona->user->update([
                'email' => $request->email,
            ]);

            // Actualizar persona
            $guardia->persona->update([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'ci' => $request->ci,
                'telefono' => $request->telefono,
            ]);

            // Actualizar guardia
            $guardia->update([
                'estado' => $request->estado,
                'fecha_ini' => $request->fecha_ini,
                'fecha_fin' => $request->fecha_fin,
            ]);

            DB::commit();

            return redirect()->route('admin.guardias.index')
                ->with('success', 'Guardia actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al actualizar el guardia: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Guardia $guardia)
    {
        /** @var \App\Models\Administrador $admin */
        $admin = Auth::guard('admin')->user();
        if ($guardia->administrador_id !== $admin->id) {
            abort(403, 'No tienes permiso para eliminar este guardia.');
        }

        try {
            DB::beginTransaction();

            // Soft delete del guardia
            $guardia->delete();
            
            // Opcionalmente también eliminar persona y usuario
            // $guardia->persona->user->delete();
            // $guardia->persona->delete();

            DB::commit();

            return redirect()->route('admin.guardias.index')
                ->with('success', 'Guardia eliminado exitosamente.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al eliminar el guardia: ' . $e->getMessage()]);
        }
    }
}
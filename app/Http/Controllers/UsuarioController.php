<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $request->input('buscar');
        $rol = $request->input('rol');

        $usuarios = Usuario::when($busqueda, function ($query, $buscar) {
                return $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                      ->orWhere('usuario', 'like', "%{$buscar}%");
                });
            })
            ->when($rol, fn($q) => $q->where('rol', $rol))
            ->orderBy('nombre', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'busqueda', 'rol'));
    }

    public function create(): View
    {
        return view('usuarios.create', ['usuario' => new Usuario()]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        Usuario::create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado satisfactoriamente.');
    }

    public function show(Usuario $usuario): View
    {
        $usuario->load('asignaciones.curso', 'asignaciones.materia');
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(GuardarUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado satisfactoriamente.');
    }

    public function destroy(Usuario $usuario): RedirectResponse
    {
        // Regla: no eliminar físicamente si tiene asignaciones vinculadas; ofrecer desactivación
        if ($usuario->asignaciones()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar el usuario porque tiene asignaciones vinculadas en el sistema. Debe desactivarlo en su lugar.',
            ]);
        }

        // Impedir autoeliminación del usuario actual
        if (auth()->id() === $usuario->id) {
            return back()->withErrors([
                'error' => 'No puede eliminar su propia cuenta activa.',
            ]);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado satisfactoriamente.');
    }
}

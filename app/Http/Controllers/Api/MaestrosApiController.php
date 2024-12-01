<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMaestroRequest;
use App\Models\Maestro;
use App\Models\Escuela;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

/**
 * Archivo: MaestrosApiController.php
 * Propósito: Controlador para gestionar datos relacionados con maestros.
 * Autor: José Balam González Rojas
 * Fecha de Creación: 2024-11-19
 * Última Modificación: 2024-11-27
 */
class MaestrosApiController extends Controller
{
    /**
     * Mostrar una lista de los recursos.
     */
    public function index(Request $request)
    {
        $adminId = Auth::id();

        $escuelas = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->pluck('id');

        $nombre = $request->input('nombre');

        $maestros = Maestro::whereIn('cesi_escuela_id', $escuelas)
            ->when($nombre, function ($query, $nombre) {
                return $query->where('maestro_nombre', 'like', '%' . $nombre . '%');
            })
            ->get();

        return response()->json(['maestros' => $maestros]);
    }

    /**
     * Obtener los colores de la escuela asociada al maestro.
     */
    public function obtenerColoresDeEscuela($maestroId)
    {
        $maestro = Maestro::find($maestroId);

        if (!$maestro) {
            return response()->json(['error' => 'Maestro no encontrado'], 404);
        }

        $escuela = $maestro->escuelas;

        $colores = $escuela->uis()->select('ui_color1', 'ui_color2', 'ui_color3', 'ui_logo')->get();

        if ($colores->isEmpty()) {
            return response()->json(['error' => 'No se encontraron colores para la escuela'], 404);
        }

        return response()->json(['colores' => $colores]);
    }

    /**
     * Mostrar el recurso especificado.
     */
    public function show($id)
    {
        $maestro = Maestro::find($id);

        if (!$maestro) {
            return response()->json(['error' => 'Maestro no encontrado'], 404);
        }

        return response()->json(['data' => $maestro], 200);
    }

    /**
     * Almacenar un nuevo recurso en el almacenamiento.
     */
    public function store(Request $request)
    {
        $request->validate($this->validationRules(), $this->validationMessages());

        $maestro = new Maestro();
        $maestro->maestro_nombre = $request->maestro_nombre;
        $maestro->maestro_usuario = $request->maestro_usuario;
        $maestro->maestro_contraseña = Hash::make($request->maestro_contraseña);
        $maestro->maestro_telefono = $request->maestro_telefono;
        $maestro->cesi_escuela_id = $request->cesi_escuela_id;

        if ($request->hasFile('maestro_foto')) {
            $maestro->maestro_foto = $this->uploadMaestroFoto($request->file('maestro_foto'));
        }

        User::create([
            'name' => $request->maestro_nombre,
            'email' => $request->maestro_usuario,
            'password' => Hash::make($request->maestro_contraseña),
            'role' => 'maestro',
        ]);

        $maestro->save();

        return response()->json(['message' => 'Maestro creado exitosamente', 'maestro' => $maestro], 201);
    }

    /**
     * Actualizar el recurso especificado en el almacenamiento.
     */
    public function update(UpdateMaestroRequest $request, Maestro $maestro)
    {
        $request->validate($this->validationRules($maestro->id), $this->validationMessages());

        $maestro->maestro_nombre = $request->maestro_nombre;
        $maestro->maestro_usuario = $request->maestro_usuario;

        if ($request->filled('maestro_contraseña')) {
            $maestro->maestro_contraseña = Hash::make($request->maestro_contraseña);
        }

        $maestro->maestro_telefono = $request->maestro_telefono;
        $maestro->cesi_escuela_id = $request->cesi_escuela_id;

        if ($request->hasFile('maestro_foto')) {
            if ($maestro->maestro_foto && Storage::exists('public/' . $maestro->maestro_foto)) {
                Storage::delete('public/' . $maestro->maestro_foto);
            }
            $maestro->maestro_foto = $this->uploadMaestroFoto($request->file('maestro_foto'));
        }

        $user = User::where('email', $maestro->maestro_usuario)->first();
        $user->name = $request->maestro_nombre;
        $user->email = $request->maestro_usuario;
        $user->password = Hash::make($request->maestro_contraseña);
        $user->role = 'maestro';
        $user->save();
        $maestro->save();

        return response()->json(['message' => 'Maestro actualizado exitosamente', 'maestro' => $maestro]);
    }

    /**
     * Reglas de validación centralizadas.
     */
    private function validationRules($maestroId = null)
    {
        return [
            'maestro_nombre' => 'required|string|max:255',
            'maestro_usuario' => 'required|email|unique:cesi_maestros,maestro_usuario' . ($maestroId ? ',' . $maestroId : ''),
            'maestro_contraseña' => 'nullable|string|min:8',
            'maestro_telefono' => 'required|string|max:15',
            'cesi_escuela_id' => 'required|exists:cesi_escuelas,id',
        ];
    }

    /**
     * Mensajes de validación centralizados en español.
     */
    private function validationMessages()
    {
        return [
            'maestro_nombre.required' => 'El nombre del maestro es obligatorio.',
            'maestro_nombre.string' => 'El nombre del maestro debe ser una cadena de texto.',
            'maestro_nombre.max' => 'El nombre del maestro no puede exceder los 255 caracteres.',
            'maestro_usuario.required' => 'El correo electrónico del maestro es obligatorio.',
            'maestro_usuario.email' => 'El correo electrónico debe tener un formato válido.',
            'maestro_usuario.unique' => 'El correo electrónico ya está registrado.',
            'maestro_contraseña.required' => 'La contraseña es obligatoria.',
            'maestro_contraseña.string' => 'La contraseña debe ser una cadena de texto.',
            'maestro_contraseña.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'maestro_telefono.required' => 'El teléfono del maestro es obligatorio.',
            'maestro_telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'maestro_telefono.max' => 'El teléfono no puede exceder los 15 caracteres.',
            'cesi_escuela_id.required' => 'La escuela es obligatoria.',
            'cesi_escuela_id.exists' => 'La escuela seleccionada no existe.',
        ];
    }

    /**
     * Manejar la carga de la foto del maestro.
     */
    private function uploadMaestroFoto($file)
    {
        return $file->store('maestros', 'public');
    }
}

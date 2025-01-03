<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tutor;
use App\Models\Escuela;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Administrador;
use App\Models\Responsable;

/**
 * Archivo: TutorController.php
 * Propósito: Controlador para gestionar tutores.
 * Autor: Alexis Daniel Uribe Oleriano
 * Fecha de Creación: 2024-11-19
 * Última Modificación: 2024-12-05
 */
class TutorController extends Controller
{

    /**
     * Muestra la lista de tutores filtrados por nombre.
     */
    public function index(Request $request)
    {
        $admin = User::find(Auth::id());
        $adminId = Administrador::where('administrador_usuario', $admin->email)->pluck('id')->first();
        $escuelas = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->pluck('id');

        $nombre = $request->input('nombre');

        $tutores = Tutor::whereIn('cesi_escuela_id', $escuelas)
            ->when($nombre, function ($query, $nombre) {
                return $query->where('tutor_nombre', 'like', '%' . $nombre . '%');
            })
            ->get();
        $escuela = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get()->first();
        $ui = $escuela ? $escuela->uis->first() : null;
        return view('tutores.index', compact('tutores', 'ui', 'escuela'));
    }

    /**
     * Muestra el formulario para crear un nuevo tutor.
     */
    public function create()
    {
        $admin = User::find(Auth::id());
        $adminId = Administrador::where('administrador_usuario', $admin->email)->pluck('id')->first();
        $escuelas = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get();

        $escuela = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get()->first();
        if (!$escuela) {
            return redirect()->back()->with('error', 'Genere una escuela primero.');
        }
        $ui = $escuela ? $escuela->uis->first() : null;

        return view('tutores.create', compact('escuelas', 'ui'));
    }


    /**
     * Guarda un nuevo tutor en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tutor_usuario' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('cesi_tutores', 'tutor_usuario'),
                Rule::unique('users', 'email'),
            ],
            'tutor_contraseña' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'tutor_nombre' => [
                'required',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', // Permitir letras, acentos, ñ y espacios
                'max:255'
            ],
            'tutor_telefono' => 'required|regex:/^[0-9]+$/|digits:10',
            'tutor_foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cesi_escuela_id' => 'required|exists:cesi_escuelas,id',
        ], [
            'tutor_usuario.required' => 'El campo correo electrónico es obligatorio.',
            'tutor_usuario.email' => 'El correo electrónico ingresado no es válido. Por ejemplo, usa un formato como "usuario@dominio.com".',
            'tutor_usuario.regex' => 'El correo electrónico ingresado no es válido. Por ejemplo, usa un formato como "usuario@dominio.com".',
            'tutor_usuario.unique' => 'El correo electrónico ya está registrado.',

            'tutor_contraseña.required' => 'El campo contraseña es obligatorio.',
            'tutor_contraseña.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'tutor_contraseña.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial (@$!%*?&).',

            'tutor_nombre.required' => 'El campo nombre es obligatorio.',
            'tutor_nombre.regex' => 'El nombre solo puede contener letras, acentos, la ñ y espacios.',
            'tutor_nombre.max' => 'El nombre no puede exceder los 255 caracteres.',

            'tutor_telefono.required' => 'El campo teléfono es obligatorio.',
            'tutor_telefono.digits' => 'El número de teléfono debe contener exactamente 10 dígitos.',
            'tutor_telefono.regex' => 'El número de teléfono debe ser numérico.',

            'tutor_foto.required' => 'El campo foto es obligatorio.',
            'tutor_foto.image' => 'El archivo debe ser una imagen.',
            'tutor_foto.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'tutor_foto.max' => 'La imagen no debe exceder los 2 MB.',

            'cesi_escuela_id.required' => 'El campo escuela es obligatorio.',
            'cesi_escuela_id.exists' => 'La escuela seleccionada no es válida.',
        ]);


        $data = $request->all();
        $data['tutor_contraseña'] = bcrypt($request->tutor_contraseña);

        if ($request->hasFile('tutor_foto')) {
            $data['tutor_foto'] = $request->file('tutor_foto')->store('tutores', 'public');
        }

        $tutor = Tutor::create($data);
        $user = new User();
        $user->name = $request->tutor_nombre;
        $user->email = $request->tutor_usuario;
        $user->password = bcrypt($request->tutor_contraseña);
        $user->role = 'tutor';
        $user->save();
        $responsable = new Responsable();
        $responsable->responsable_nombre = $request->tutor_nombre;
        $responsable->responsable_usuario = $request->tutor_usuario;
        $responsable->responsable_telefono = $request->tutor_telefono;
        $responsable->responsable_contraseña = bcrypt($request->tutor_contraseña);
        $responsable->responsable_activacion = 1;
        $responsable->responsable_foto = $tutor->tutor_foto;
        $responsable->cesi_tutore_id = $tutor->id;
        $responsable->save();
        return redirect()->route('tutores.index')->with('success', 'Tutor creado exitosamente.');
    }


    /**
     * Muestra los detalles de un tutor específico.
     */
    public function show(Tutor $tutor)
    {
        $admin = User::find(Auth::id());
        $adminId = Administrador::where('administrador_usuario', $admin->email)->pluck('id')->first();
        $escuela = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get()->first();
        $ui = $escuela ? $escuela->uis->first() : null;
        return view('tutores.show', compact('tutor', 'ui', 'escuela'));
    }

    /**
     * Muestra el formulario para editar un tutor existente.
     */
    public function edit(Tutor $tutor)
    {
        $admin = User::find(Auth::id());
        $adminId = Administrador::where('administrador_usuario', $admin->email)->pluck('id')->first();
        $admin = User::find(Auth::id());
        $adminId = Administrador::where('administrador_usuario', $admin->email)->pluck('id')->first();
        $escuelas = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get();
        $escuela = Escuela::whereHas('administrador', function ($query) use ($adminId) {
            $query->where('cesi_administrador_id', $adminId);
        })->get()->first();
        $ui = $escuela ? $escuela->uis->first() : null;

        return view('tutores.edit', compact('tutor', 'escuelas', 'ui'));
    }


    /**
     * Actualiza la información de un tutor existente en la base de datos.
     */
    public function update(Request $request, Tutor $tutor)
    {
        $user = User::where('email', $tutor->tutor_usuario)->first();
        $relatedUserId = $user?->id;

        // Validación de los datos
        $request->validate([
            'tutor_usuario' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('cesi_tutores', 'tutor_usuario')->ignore($tutor->id),
                Rule::unique('users', 'email')->ignore($relatedUserId),
            ],
            'tutor_contraseña' => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'tutor_nombre' => [
                'required',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
                'max:255'
            ],
            'tutor_telefono' => 'required|regex:/^[0-9]+$/|digits:10',
            'tutor_foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cesi_escuela_id' => 'required|exists:cesi_escuelas,id',
        ], [
            'tutor_usuario.required' => 'El campo correo electrónico es obligatorio.',
            'tutor_usuario.email' => 'El correo electrónico ingresado no es válido. Por ejemplo, usa un formato como "usuario@dominio.com".',
            'tutor_usuario.regex' => 'El correo electrónico ingresado no es válido. Por ejemplo, usa un formato como "usuario@dominio.com".',
            'tutor_usuario.unique' => 'El correo electrónico ya está registrado.',

            'tutor_contraseña.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'tutor_contraseña.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial (@$!%*?&).',

            'tutor_nombre.required' => 'El campo nombre es obligatorio.',
            'tutor_nombre.regex' => 'El nombre solo puede contener letras, acentos, la ñ y espacios.',
            'tutor_nombre.max' => 'El nombre no puede exceder los 255 caracteres.',

            'tutor_telefono.required' => 'El campo teléfono es obligatorio.',
            'tutor_telefono.digits' => 'El número de teléfono debe contener exactamente 10 dígitos.',
            'tutor_telefono.regex' => 'El número de teléfono debe ser numérico.',

            'tutor_foto.image' => 'El archivo debe ser una imagen.',
            'tutor_foto.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'tutor_foto.max' => 'La imagen no debe exceder los 2 MB.',

            'cesi_escuela_id.required' => 'El campo escuela es obligatorio.',
            'cesi_escuela_id.exists' => 'La escuela seleccionada no es válida.',
        ]);

        // Prepara los datos
        $data = $request->all();

        // Actualiza la contraseña solo si se proporciona
        if ($request->filled('tutor_contraseña')) {
            $data['tutor_contraseña'] = bcrypt($request->tutor_contraseña);
        } else {
            unset($data['tutor_contraseña']);  // Elimina la contraseña si no se proporciona
        }

        // Actualiza la foto si se proporciona una nueva
        if ($request->hasFile('tutor_foto')) {
            if ($tutor->tutor_foto) {
                Storage::delete('public/' . $tutor->tutor_foto);  // Eliminar la foto anterior
            }
            $data['tutor_foto'] = $request->file('tutor_foto')->store('tutores', 'public');
        }

        // Actualiza los datos del usuario
        $user->name = $request->tutor_nombre;
        $user->email = $request->tutor_usuario;
        if ($request->filled('tutor_contraseña')) {
            $user->password = bcrypt($request->tutor_contraseña);  // Actualiza solo si la contraseña es proporcionada
        }
        $user->role = 'tutor';
        $user->save();

        // Actualiza los datos del tutor
        $tutor->update($data);

        // Actualiza los datos del responsable
        $responsable = Responsable::where('cesi_tutore_id', $tutor->id)->first();
        $responsable->responsable_nombre = $request->tutor_nombre;
        $responsable->responsable_usuario = $request->tutor_usuario;
        $responsable->responsable_telefono = $request->tutor_telefono;

        if ($request->filled('tutor_contraseña')) {
            $responsable->responsable_contraseña = bcrypt($request->tutor_contraseña);  // Solo actualiza la contraseña si se proporciona
        }

        $responsable->responsable_activacion = 1;
        $responsable->responsable_foto = $tutor->tutor_foto;
        $responsable->cesi_tutore_id = $tutor->id;
        $responsable->save();

        return redirect()->route('tutores.index')->with('success', 'Tutor actualizado exitosamente.');
    }

    /**
     * Elimina un tutor de la base de datos.
     */
    public function destroy(Tutor $tutor)
    {
        $responsables = Responsable::where('cesi_tutore_id', $tutor->id)->get();
        foreach ($responsables as $responsable) {
            $user = User::where('email', $responsable->responsable_usuario)->first();
            if ($user) {
                $user->delete();
            }
        }
        if ($tutor->tutor_foto) {
            Storage::delete('public/' . $tutor->tutor_foto);
        }
        $tutor->delete();



        return redirect()->route('tutores.index')->with('success', 'Tutor eliminado exitosamente.');
    }
}

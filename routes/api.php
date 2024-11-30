<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\MaestrosApiController;
use App\Http\Controllers\Api\ResponsableApiController;
use App\Http\Controllers\Api\RegistroApiController;
use App\Http\Controllers\Api\LogInApiController;
use App\Http\Controllers\Api\SalonApiController;
use App\Http\Controllers\Api\EscuelaApiController;
use App\Http\Controllers\Api\AsistenciasApiController;
use App\Http\Controllers\Api\ListaApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PaseApiController;
use App\Http\Controllers\Api\RastreoApiController;
use App\Http\Controllers\Api\RecogidaApiController;
use App\Http\Controllers\Api\SesionApiController;
use App\Http\Controllers\Api\TutorApiController;
use Illuminate\Support\Facades\Route;

/**
 * Archivo: api.php
 * Propósito: Genera las rutas de la API.
 * Autor: José Balam González Rojas
 * Fecha de Creación: 2024-11-19
 * Última Modificación: 2024-11-27
 */

// Rutas para el controlador LogInApiController (autenticación)
Route::prefix('auth')->group(function () {
    Route::post('/login', [LogInApiController::class, 'login']); // Iniciar sesión y generar un token
    Route::post('/logout', [LogInApiController::class, 'logout'])->middleware('auth:sanctum'); // Cerrar sesión y eliminar el token
});

// Rutas para el controlador RegistroApiController (registro de usuarios)
Route::prefix('registro')->group(function () {
    Route::post('/', [RegistroApiController::class, 'register']); // Ruta para registrar un nuevo usuario
});

// Rutas para el controlador DashboardApiController (panel de administración)
Route::prefix('dashboard')->group(function () {
    Route::get('/responsables-inactivos', [DashboardApiController::class, 'index']); // Mostrar responsables inactivos
});

// Rutas para el controlador ResponsableApiController (gestión de responsables)
Route::prefix('responsables')->group(function () {
    Route::post('/', [ResponsableApiController::class, 'store']); // Crear un nuevo responsable
    Route::get('{id}', [ResponsableApiController::class, 'show']); // Mostrar un responsable por ID
    Route::put('{responsable}', [ResponsableApiController::class, 'update']); // Actualizar responsable
    Route::delete('{responsable}', [ResponsableApiController::class, 'destroy']); // Eliminar responsable
    Route::get('{responsableId}/school-colors', [ResponsableApiController::class, 'getSchoolColorsByResponsable']); // Obtener colores y logo de la escuela asociada a un responsable
});

// Rutas para el controlador RecogidaApiController (gestión de recogidas de alumnos)
Route::prefix('recogida')->group(function () {
    Route::get('alumnos/{idTutor}', [RecogidaApiController::class, 'alumnosSinRecogida']); // Obtener alumnos sin recogida
    Route::post('generar', [RecogidaApiController::class, 'generarRecogida']); // Crear una nueva recogida
    Route::get('tutor/{idTutor}', [RecogidaApiController::class, 'recogidasPorTutor']); // Obtener todas las recogidas de un tutor
    Route::get('estatus', [RecogidaApiController::class, 'recogidasPorEstatus']); // Obtener recogidas por estatus (pendiente, completa, cancelada)
    Route::get('reporte/{idTutor}', [RecogidaApiController::class, 'generarReportePDF']); // Generar reporte en PDF de recogidas
    Route::get('reportes/{idTutor}', [RecogidaApiController::class, 'reportesPorTutor']); // Obtener reportes generados por un tutor
});

// Rutas para el controlador RastreoApiController (gestión de rastreos de recogidas)
Route::prefix('rastreo')->group(function () {
    Route::get('recogida/{recogidaId}', [RastreoApiController::class, 'index']); // Obtener todos los rastreos de una recogida
    Route::post('recogida/{recogidaId}', [RastreoApiController::class, 'create']); // Crear un nuevo rastreo para una recogida
    Route::get('recogida/{recogidaId}/{id}', [RastreoApiController::class, 'show']); // Mostrar rastreo específico de una recogida
    Route::put('recogida/{recogidaId}/{id}', [RastreoApiController::class, 'update']); // Actualizar rastreo de una recogida
    Route::delete('recogida/{recogidaId}/{id}', [RastreoApiController::class, 'destroy']); // Eliminar un rastreo de una recogida
});

// Rutas para el controlador SesionApiController (gestión de sesiones)
Route::prefix('sesiones')->group(function () {
    Route::get('/', [SesionApiController::class, 'index']); // Mostrar todas las sesiones
    Route::post('/', [SesionApiController::class, 'create']); // Crear una nueva sesión
    Route::get('/{id}', [SesionApiController::class, 'show']); // Mostrar una sesión específica
    Route::put('/{id}', [SesionApiController::class, 'update']); // Actualizar una sesión
    Route::delete('/{id}', [SesionApiController::class, 'destroy']); // Eliminar una sesión
    Route::get('/{id}/responsable', [SesionApiController::class, 'responsable']); // Obtener responsable asociado a una sesión
});

// Rutas para el controlador SalonApiController (gestión de salones)
Route::prefix('salones')->group(function () {
    Route::get('/', [SalonApiController::class, 'index']); // Listar salones con filtros
    Route::post('/', [SalonApiController::class, 'store']); // Crear un nuevo salón
    Route::get('/{salon}', [SalonApiController::class, 'show']); // Mostrar salón específico
    Route::put('/{salon}', [SalonApiController::class, 'update']); // Actualizar salón
    Route::delete('/{salon}', [SalonApiController::class, 'destroy']); // Eliminar salón
});

// Rutas para el controlador TutorApiController (gestión de tutores)
Route::prefix('tutores')->group(function () {
    Route::get('/{id}', [TutorApiController::class, 'showTutor']); // Obtener datos de un tutor por su ID
    Route::get('/{id}/alumnos', [TutorApiController::class, 'showAlumnosByTutor']); // Obtener los alumnos de un tutor
    Route::get('/{tutorId}/alumnos/{id}', [TutorApiController::class, 'showAlumno']); // Obtener datos de un alumno por su ID y tutor
    Route::get('/{id}/escuela/colores', [TutorApiController::class, 'showEscuelaColores']); // Obtener colores de la escuela asociada a un tutor
    Route::get('/{id}/responsables', [TutorApiController::class, 'showResponsablesByTutor']); // Obtener responsables asociados a un tutor
    Route::get('/{tutorId}/responsables/{id}', [TutorApiController::class, 'showResponsable']); // Mostrar un responsable específico de un tutor
    Route::post('/{id}/foto', [TutorApiController::class, 'updateFoto']); // Actualizar la foto de un tutor
});

// Rutas para el controlador AsistenciasApiController (gestión de asistencias)
Route::prefix('asistencias')->group(function () {
    Route::post('/', [AsistenciasApiController::class, 'store']); // Crear nueva asistencia
    Route::get('/{id}', [AsistenciasApiController::class, 'show']); // Mostrar asistencia específica
    Route::put('/{asistencia}', [AsistenciasApiController::class, 'update']); // Actualizar asistencia
    Route::delete('/{asistencia}', [AsistenciasApiController::class, 'destroy']); // Eliminar asistencia
});

// Rutas para el controlador EscuelaApiController (gestión de escuelas)
Route::prefix('escuelas')->group(function () {
    Route::get('/', [EscuelaApiController::class, 'index']); // Mostrar todas las escuelas
    Route::post('/crear', [EscuelaApiController::class, 'create']); // Crear una nueva escuela
    Route::get('/{id}', [EscuelaApiController::class, 'show']); // Mostrar escuela específica
    Route::put('/{id}', [EscuelaApiController::class, 'update']); // Actualizar escuela existente
    Route::delete('/{id}', [EscuelaApiController::class, 'destroy']); // Eliminar escuela
});

// Rutas para el controlador ListaApiController (gestión de listas)
Route::prefix('listas')->group(function () {
    Route::get('/', [ListaApiController::class, 'index']); // Mostrar todas las listas asociadas al maestro
    Route::post('/crear', [ListaApiController::class, 'create']); // Crear una nueva lista
    Route::get('/{id}', [ListaApiController::class, 'show']); // Mostrar una lista específica
    Route::put('/{id}', [ListaApiController::class, 'update']); // Actualizar una lista
    Route::delete('/{id}', [ListaApiController::class, 'destroy']); // Eliminar lista
});

// Rutas para el controlador NotificacionApiController
Route::prefix('notificaciones')->group(function () {
    Route::get('alumno/{alumnoId}', [NotificacionApiController::class, 'index']); // Obtener todas las notificaciones de un alumno específico
    Route::post('alumno/{maestroId}/{alumnoId}', [NotificacionApiController::class, 'create']); // Crear una nueva notificación para un alumno específico
    Route::get('tutor/{tutorId}', [NotificacionApiController::class, 'show']); // Obtener las notificaciones de un tutor específico
    Route::put('alumno/{alumnoId}/notificacion/{id}', [NotificacionApiController::class, 'update']); // Actualizar una notificación de un alumno específico
    Route::delete('alumno/{alumnoId}/notificacion/{id}', [NotificacionApiController::class, 'destroy']); // Eliminar una notificación de un alumno específico
});

// Rutas para MaestrosApiController
Route::prefix('maestros')->group(function () {
    Route::get('/', [MaestrosApiController::class, 'index']); // Obtener la lista de todos los maestros
    Route::get('/{maestroId}/colores', [MaestrosApiController::class, 'obtenerColoresDeEscuela']); // Obtener los colores de la escuela de un maestro específico
    Route::get('/{id}', [MaestrosApiController::class, 'show']); // Obtener un maestro específico por su ID
    Route::post('/', [MaestrosApiController::class, 'store']); // Crear un nuevo maestro
    Route::put('/{maestro}', [MaestrosApiController::class, 'update']); // Actualizar la información de un maestro específico
});

// Rutas para el controlador PaseApiController
Route::prefix('pase')->group(function () {
    Route::get('alumno/{alumnoId}', [PaseApiController::class, 'index']); // Obtener todos los pases de un alumno específico
    Route::post('alumno/{alumnoId}', [PaseApiController::class, 'create']); // Crear un nuevo pase para un alumno específico
    Route::get('alumno/{alumnoId}/{id}', [PaseApiController::class, 'show']); // Obtener un pase específico de un alumno
    Route::put('alumno/{alumnoId}/{id}', [PaseApiController::class, 'update']); // Actualizar un pase específico de un alumno
    Route::delete('alumno/{alumnoId}/{id}', [PaseApiController::class, 'destroy']); // Eliminar un pase específico de un alumno
});

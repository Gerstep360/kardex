@props(['title' => 'Inicio'])
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#176b50"><title>{{ $title }} · Kardex</title>{{ \Illuminate\Support\Facades\Vite::fonts() }}@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body>
<a href="#contenido" class="skip-link">Saltar al contenido</a>
<div class="app-shell">
    <header class="app-header">
        <div class="masthead"><div class="masthead-brand"><button class="icon-button mobile-only" id="menu-toggle" aria-expanded="false" aria-controls="sidebar" aria-label="Abrir menú"><x-icon name="menu" /></button><x-brand /><span class="masthead-divider"></span><div class="institution"><strong>Maestro Pitágoras II</strong><small>Técnico Humanístico · BTH</small></div></div><div class="topbar-right"><span class="year-tag"><span class="status-dot"></span> Gestión {{ date('Y') }}</span><details class="account-menu"><summary><span class="avatar">{{ mb_substr(auth()->user()->nombre, 0, 1) }}</span><span class="account-copy"><strong>{{ auth()->user()->nombre }}</strong><small>{{ ucfirst(auth()->user()->rol) }}</small></span><x-icon name="chevron" /></summary><div class="account-popover"><strong>{{ auth()->user()->usuario }}</strong><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" /> Cerrar sesión</button></form></div></details></div></div>
        <div class="sidebar-overlay" data-close-sidebar></div>
        <div class="sidebar" id="sidebar"><div class="sidebar-brand mobile-only"><x-brand /><button class="icon-button" data-close-sidebar aria-label="Cerrar menú"><x-icon name="close" /></button></div><nav class="main-navigation" aria-label="Navegación principal">
            <x-nav-link route="dashboard" icon="grid" label="Inicio" />
            @if(auth()->user()->esAdministrador())<x-nav-link route="estudiantes.index" icon="users" label="Estudiantes" /><x-nav-link route="cursos.index" icon="school" label="Cursos" />@elseif(auth()->user()->esDocente())<x-nav-link route="notas.index" icon="edit" label="Calificaciones" />@endif
            @if(auth()->user()->puedeConsultarBoletines())<x-nav-link route="boletines.index" icon="file" label="Boletines" />@endif
            @if(auth()->user()->esDocente())<x-nav-link route="promedios.index" icon="chart" label="Promedios" /><x-nav-link route="reportes.index" icon="file" label="Reportes" />@endif
            @if(auth()->user()->esAdministrador())
            <details @class(['nav-group', 'group-active' => request()->routeIs('promedios.*', 'reportes.*')])><summary><x-icon name="chart" /> Resultados <x-icon name="chevron" /></summary><div class="nav-popover"><x-nav-link route="promedios.index" icon="chart" label="Promedios" /><x-nav-link route="reportes.index" icon="file" label="Reportes por materia" /></div></details>
            <details @class(['nav-group', 'group-active' => request()->routeIs('materias.*', 'matriculas.*', 'asignaciones.*', 'periodos.*')])><summary><x-icon name="book" /> Organización <x-icon name="chevron" /></summary><div class="nav-popover"><p class="nav-label">ORGANIZAR LA GESTIÓN</p><x-nav-link route="materias.index" icon="book" label="Materias" /><x-nav-link route="matriculas.index" icon="file" label="Matrículas" /><x-nav-link route="asignaciones.index" icon="users" label="Asignaciones docentes" /><x-nav-link route="periodos.index" icon="calendar" label="Periodos académicos" /></div></details>
            <details @class(['nav-group', 'group-active' => request()->routeIs('usuarios.*', 'respaldos.*')])><summary><x-icon name="shield" /> Administración <x-icon name="chevron" /></summary><div class="nav-popover"><p class="nav-label">CUIDAR LA PLATAFORMA</p><x-nav-link route="usuarios.index" icon="users" label="Usuarios y acceso" /><x-nav-link route="respaldos.index" icon="shield" label="Copias de seguridad" /></div></details>
            @endif
        </nav></div>
    </header>
    <div class="workspace"><main id="contenido" class="page-content" tabindex="-1"><div class="breadcrumb"><span>Mi espacio</span><x-icon name="chevron" /><strong>{{ $title }}</strong></div><x-alerts />{{ $slot }}<footer class="page-footer"><span>Kardex / Espacio académico</span><span>Maestro Pitágoras II · {{ date('Y') }}</span></footer></main></div>
</div>
<x-confirm-dialog />
</body></html>

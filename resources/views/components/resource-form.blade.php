@props(['module', 'record', 'title', 'docentes' => null, 'materias' => null, 'cursos' => null, 'estudiantes' => null])
@php($editing = $record->exists)
<x-app :title="$title"><a class="text-link back-link" href="{{ route($module.'.index') }}"><x-icon name="arrow" /> Volver al listado</a><x-page-heading :title="$title" description="Los detalles correctos hacen que todo funcione mejor." />
<div class="form-layout"><form method="POST" action="{{ route($module.($editing ? '.update' : '.store'), $editing ? $record : []) }}" class="panel">@csrf @if($editing) @method('PUT') @endif<div class="panel-header"><h2>Información del registro</h2><x-badge tone="primary">{{ $editing ? 'Edición' : 'Nuevo registro' }}</x-badge></div><div class="form-body"><div class="form-grid">
@switch($module)
@case('estudiantes')
    <x-field name="codigo" label="Código de estudiante" :value="$record->codigo" maxlength="30" placeholder="Ej. EST-2026-009" /><x-field name="estado" label="Estado" :value="$record->estado" :options="['activo' => 'Activo', 'inactivo' => 'Inactivo']" />
    <x-field name="nombres" label="Nombres" :value="$record->nombres" maxlength="120" autocomplete="given-name" /><x-field name="apellidos" label="Apellidos" :value="$record->apellidos" maxlength="120" autocomplete="family-name" />@break
@case('usuarios')
    <x-field name="nombre" label="Nombre completo" :value="$record->nombre" maxlength="150" autocomplete="name" /><x-field name="usuario" label="Nombre de usuario" :value="$record->usuario" maxlength="50" autocomplete="off" />
    <x-field name="rol" label="Rol" :value="$record->rol" :options="['administrador' => 'Administrador', 'docente' => 'Docente']" /><x-field name="activo" label="Estado de acceso" :value="$record->exists ? (int) $record->activo : 1" :options="[1 => 'Activo', 0 => 'Inactivo']" />
    <x-field name="password" label="Contraseña" type="password" minlength="6" autocomplete="new-password" :required="!$editing" :hint="$editing ? 'Déjala vacía para conservar la contraseña actual.' : 'Usa al menos 6 caracteres.'" />@break
@case('cursos')
    <x-field name="nombre" label="Nombre del curso" :value="$record->nombre" maxlength="100" placeholder="Ej. 4.º de Secundaria" /><x-field name="paralelo" label="Paralelo" :value="$record->paralelo" maxlength="20" placeholder="Ej. A" /><x-field name="gestion" label="Gestión" type="number" :value="$record->gestion" min="2000" max="2100" />@break
@case('materias')
    <x-field name="nombre" label="Nombre de la materia" :value="$record->nombre" maxlength="120" placeholder="Ej. Matemáticas" /><x-field name="sigla" label="Sigla" :value="$record->sigla" maxlength="20" placeholder="Ej. MAT-401" />@break
@case('periodos')
    <x-field name="nombre" label="Nombre del periodo" :value="$record->nombre" maxlength="80" placeholder="Ej. Primer Trimestre" /><x-field name="numero" label="Orden del periodo" type="number" :value="$record->numero" min="1" max="12" /><x-field name="gestion" label="Gestión" type="number" :value="$record->gestion" min="2000" max="2100" />@break
@case('matriculas')
    <x-field name="estudiante_id" label="Estudiante" :value="$record->estudiante_id" :options="$estudiantes->mapWithKeys(fn ($item) => [$item->id => $item->nombre_completo.' · '.$item->codigo])->all()" class="full-width" />
    <x-field name="curso_id" label="Curso" :value="$record->curso_id" :options="$cursos->mapWithKeys(fn ($item) => [$item->id => $item->nombre_completo])->all()" class="full-width" />
    <x-field name="gestion" label="Gestión" type="number" :value="$record->gestion" min="2000" max="2100" hint="Debe coincidir con la gestión del curso." />@break
@case('asignaciones')
    <x-field name="docente_id" label="Docente" :value="$record->docente_id" :options="$docentes->pluck('nombre', 'id')->all()" class="full-width" /><x-field name="materia_id" label="Materia" :value="$record->materia_id" :options="$materias->pluck('nombre', 'id')->all()" />
    <x-field name="curso_id" label="Curso" :value="$record->curso_id" :options="$cursos->mapWithKeys(fn ($item) => [$item->id => $item->nombre_completo])->all()" />
    <x-field name="gestion" label="Gestión" type="number" :value="$record->gestion" min="2000" max="2100" hint="Debe coincidir con la gestión del curso." />@break
@endswitch
</div></div><div class="form-actions"><a class="button button-secondary" href="{{ route($module.'.index') }}">Cancelar</a><button class="button" type="submit"><x-icon name="check" /> {{ $editing ? 'Guardar cambios' : 'Guardar registro' }}</button></div></form>
<aside class="form-help"><x-icon name="spark" /><h3>Todo en su lugar.</h3><p>Completa los campos marcados con un asterisco. Podrás actualizar esta información cuando lo necesites.</p>@if(in_array($module, ['asignaciones', 'matriculas']))<p>Los cursos disponibles corresponden a la gestión seleccionada al abrir este formulario.</p><form method="GET" action="{{ route($module.'.create') }}" class="stack"><label for="cambiar_gestion">Consultar cursos de otra gestión</label><input id="cambiar_gestion" type="number" name="gestion" min="2000" max="2100" value="{{ $record->gestion }}" required><button class="button button-secondary">Cargar cursos</button></form>@endif @if($module === 'usuarios')<p>Los docentes acceden a sus materias y calificaciones. Los administradores gestionan toda la plataforma.</p>@endif</aside></div>
</x-app>

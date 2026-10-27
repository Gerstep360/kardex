@props(['module', 'record', 'title', 'docentes' => null, 'materias' => null, 'cursos' => null, 'estudiantes' => null])
@php($editing = $record->exists)
<x-app :title="$title"><a class="text-link back-link" href="{{ route($module.'.index') }}"><x-icon name="arrow" /> Volver al listado</a><x-page-heading :title="$title" description="Los detalles correctos hacen que todo funcione mejor." />
<div class="form-layout"><form method="POST" action="{{ route($module.($editing ? '.update' : '.store'), $editing ? $record : []) }}" class="panel">@csrf @if($editing) @method('PUT') @endif<div class="panel-header"><h2>Datos a guardar</h2><x-badge tone="primary">{{ $editing ? 'Editando' : 'Nuevo' }}</x-badge></div><div class="form-body"><div class="form-grid">
@switch($module)
@case('estudiantes')
    @if(request()->filled('volver_curso'))<input type="hidden" name="volver_curso" value="{{ request('volver_curso') }}">@endif
    <p class="form-section-note full-width">Completa todos los datos del estudiante. Son necesarios para su ficha y el centralizador.</p>
    <x-field name="codigo" label="Código RUDE" :value="$record->codigo" maxlength="30" placeholder="Ej. 51980054201413862" hint="Cópialo tal como aparece en su documento. Puede incluir letras." />
    <x-field name="nombres" label="Nombres" :value="$record->nombres" maxlength="120" autocomplete="given-name" />
    <x-field name="apellido_paterno" label="Apellido paterno" :value="$record->apellido_paterno" maxlength="120" />
    <x-field name="apellido_materno" label="Apellido materno" :value="$record->apellido_materno" maxlength="120" />
    <x-field name="genero" label="Género" :value="$record->genero" :options="['M' => 'Masculino', 'F' => 'Femenino']" />
    <x-field name="fecha_nacimiento" label="Fecha de nacimiento" type="date" :value="$record->fecha_nacimiento?->format('Y-m-d')" :max="date('Y-m-d')" />
    <x-field name="lugar_nacimiento" label="Lugar de nacimiento" :value="$record->lugar_nacimiento" maxlength="20" placeholder="Ej. SC" hint="Usa el lugar que aparece en su documento." />
    <x-field name="documento_identidad" label="Número de C.I." :value="$record->documento_identidad" maxlength="30" />
    <x-field name="estado" label="Estado del estudiante" :value="$record->estado" :options="['activo' => 'Activo', 'inactivo' => 'Inactivo']" />
    @if($record->exists && $record->datosFaltantes())<p class="form-section-note full-width">Esta ficha tiene datos pendientes. Complétalos para poder guardar los cambios.</p>@endif
    @break
@case('usuarios')
    <x-field name="nombre" label="Nombre completo" :value="$record->nombre" maxlength="150" autocomplete="name" /><x-field name="usuario" label="Nombre de usuario" :value="$record->usuario" maxlength="50" autocomplete="off" />
    <x-field name="rol" label="Rol" :value="$record->rol" :options="['administrador' => 'Administrador', 'docente' => 'Docente', 'secretaria' => 'Secretaría']" /><x-field name="activo" label="Estado de acceso" :value="$record->exists ? (int) $record->activo : 1" :options="[1 => 'Activo', 0 => 'Inactivo']" />
    <x-field name="password" label="Contraseña" type="password" minlength="6" autocomplete="new-password" :required="!$editing" :hint="$editing ? 'Déjala vacía para conservar la contraseña actual.' : 'Usa al menos 6 caracteres.'" />@break
@case('cursos')
    <x-field name="nombre" label="Nombre del curso" :value="$record->nombre" maxlength="100" placeholder="Ej. 4.º de Secundaria" /><x-field name="paralelo" label="Paralelo" :value="$record->paralelo" maxlength="20" placeholder="Ej. A" /><x-field name="gestion" label="Gestión" type="number" :value="$record->gestion" min="2000" max="2100" /><x-field name="turno" label="Turno" :value="$record->turno" :required="false" :options="['MAÑANA' => 'Mañana', 'TARDE' => 'Tarde', 'NOCHE' => 'Noche']" /><x-field name="nivel" label="Nivel educativo" :value="$record->nivel" :required="false" maxlength="100" placeholder="SECUNDARIA COMUNITARIA PRODUCTIVA" class="full-width" />@break
@case('materias')
    <x-field name="nombre" label="Nombre de la materia" :value="$record->nombre" maxlength="120" placeholder="Ej. Matemáticas" /><x-field name="sigla" label="Sigla" :value="$record->sigla" maxlength="20" placeholder="Ej. MAT-401" /><x-field name="campo" label="Campo de conocimiento" :value="$record->campo" :required="false" :options="['Comunidad y Sociedad' => 'Comunidad y Sociedad', 'Ciencia, Tecnología y Producción' => 'Ciencia, Tecnología y Producción', 'Vida, Tierra y Territorio' => 'Vida, Tierra y Territorio', 'Cosmos y Pensamiento' => 'Cosmos y Pensamiento']" /><x-field name="orden" label="Orden en el centralizador" type="number" :value="$record->orden ?? 0" :required="false" min="0" max="1000" hint="Las materias del PDF reciben su orden automáticamente." />@break
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
</div></div><div class="form-actions"><a class="button button-secondary" href="{{ route($module.'.index') }}">Cancelar</a><button class="button" type="submit"><x-icon name="check" /> {{ $editing ? 'Guardar cambios' : 'Guardar' }}</button></div></form>
<aside class="form-help"><x-icon name="spark" /><h3>Todo en su lugar.</h3><p>Completa los campos marcados con un asterisco. Podrás actualizar esta información cuando lo necesites.</p>@if(in_array($module, ['asignaciones', 'matriculas']))<p>Los cursos disponibles corresponden a la gestión seleccionada al abrir este formulario.</p><form method="GET" action="{{ route($module.'.create') }}" class="stack"><label for="cambiar_gestion">Consultar cursos de otra gestión</label><input id="cambiar_gestion" type="number" name="gestion" min="2000" max="2100" value="{{ $record->gestion }}" required><button class="button button-secondary">Cargar cursos</button></form>@endif @if($module === 'usuarios')<p>Docentes: registran las notas de sus materias. Secretaría: consulta estudiantes y descarga boletines, sin cambiar notas. Administradores: gestionan la plataforma e importan documentos.</p>@endif</aside></div>
</x-app>

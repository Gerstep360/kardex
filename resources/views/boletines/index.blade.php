<x-app title="Boletines">
    <x-page-heading title="Boletines de calificaciones" description="Busca al estudiante. Elige la gestión y descarga sus notas, sin volver a escribirlas." eyebrow="CONSULTAR Y DESCARGAR">
        @if(auth()->user()->esAdministrador())<a class="button button-secondary" href="{{ route('importaciones.index') }}"><x-icon name="plus" /> Cargar centralizador PDF</a>@endif
    </x-page-heading>
    <ol class="guided-steps" aria-label="Pasos para generar un boletín"><li><span>1</span> Busca al estudiante</li><li><span>2</span> Abre su boletín</li><li><span>3</span> Descarga el PDF</li></ol>
    <section class="panel">
        <form method="GET" action="{{ route('boletines.index') }}" class="filters" data-academic-filter>
            <div class="filter-field search-field"><label for="buscar">Nombre, apellido o código RUDE</label><div class="search-input"><x-icon name="search" /><input id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Escribe aquí el nombre o el RUDE" maxlength="120"></div></div>
            <div class="filter-field"><label for="gestion">Gestión</label><select id="gestion" name="gestion" data-academic-year>@foreach($gestiones->push((int) date('Y'))->unique()->sortDesc() as $year)<option value="{{ $year }}" @selected($gestion === (int) $year)>{{ $year }}</option>@endforeach</select></div>
            <div class="filter-field"><label for="periodo_id">¿Qué notas necesitas?</label><select id="periodo_id" name="periodo_id"><option value="">Año completo</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(request('periodo_id') == $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
            <div class="filter-field"><label for="curso_id">Curso</label><select id="curso_id" name="curso_id"><option value="">Todos los cursos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(request('curso_id') == $curso->id)>{{ $curso->nombre }} · {{ $curso->paralelo }}</option>@endforeach</select></div>
            <button class="button" type="submit"><x-icon name="search" /> Buscar estudiante</button><a class="text-link" href="{{ route('boletines.index') }}">Limpiar búsqueda</a>
        </form>
        <div class="panel-header"><h2>Estudiantes encontrados</h2><x-badge>{{ $estudiantes->total() }} estudiantes</x-badge></div>
        @if($estudiantes->isNotEmpty())
            <div class="table-scroll" role="region" aria-label="Estudiantes y acceso a sus boletines" tabindex="0"><table class="data-table"><thead><tr><th scope="col">Estudiante</th><th scope="col">Código RUDE</th><th scope="col">Curso en {{ $gestion }}</th><th scope="col">Boletín</th></tr></thead><tbody>
            @foreach($estudiantes as $estudiante)
                @php($matricula = $estudiante->matriculas->first())
                <tr><td><strong>{{ $estudiante->nombre_completo }}</strong></td><td>{{ $estudiante->codigo }}</td><td>{{ $matricula?->curso->nombre ?? 'Sin matrícula en esta gestión' }}@if($matricula)<small>Paralelo {{ $matricula->curso->paralelo }}</small>@endif</td><td><a class="button button-secondary" href="{{ route('boletines.show', ['estudiante' => $estudiante->id, 'gestion' => $gestion, 'periodo_id' => request('periodo_id')]) }}">Ver boletín <x-icon name="arrow" /></a></td></tr>
            @endforeach
            </tbody></table></div>
            @if($estudiantes->hasPages())<div class="pagination">{{ $estudiantes->links() }}</div>@endif
        @else<x-empty-state title="No encontramos ese estudiante" description="Prueba con un apellido o su código RUDE. También puedes quitar el filtro de curso." icon="users" />@endif
    </section>
    <section class="document-help" id="centralizadores"><x-icon name="file" /><div><h2>¿Necesitas las notas de todo un curso?</h2><p>El centralizador reúne a sus estudiantes y materias en una tabla.</p></div></section>
    <div class="course-grid">@foreach($cursos as $curso)<a class="document-course" href="{{ route('boletines.centralizador', ['curso' => $curso->id, 'gestion' => $gestion, 'periodo_id' => request('periodo_id')]) }}"><span><strong>{{ $curso->nombre }} · {{ $curso->paralelo }}</strong><small>Ver centralizador · {{ $gestion }}</small></span><x-icon name="arrow" /></a>@endforeach</div>
</x-app>
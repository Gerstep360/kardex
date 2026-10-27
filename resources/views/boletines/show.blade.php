<x-app title="Boletín del estudiante">
    <a class="text-link back-link" href="{{ route('boletines.index', ['gestion' => $gestion, 'periodo_id' => $periodoId]) }}"><x-icon name="arrow" /> Volver a buscar estudiantes</a>
    <x-page-heading title="Boletín de calificaciones" :description="$estudiante->nombre_completo" eyebrow="VISTA PREVIA">
        @if($matricula && $filas->isNotEmpty() && $periodos->isNotEmpty())
            <a class="button button-secondary" href="{{ route('boletines.imprimir', ['estudiante' => $estudiante->id, 'gestion' => $gestion, 'periodo_id' => $periodoId]) }}" target="_blank" rel="noopener"><x-icon name="print" /> Imprimir</a>
            <a class="button" href="{{ route('boletines.pdf', ['estudiante' => $estudiante->id, 'gestion' => $gestion, 'periodo_id' => $periodoId]) }}"><x-icon name="download" /> Descargar boletín PDF</a>
        @endif
    </x-page-heading>
    <section class="panel">
        <div class="panel-header"><div><h2>{{ $institucion['nombre'] }}</h2><p>Estas son las notas guardadas por los docentes o cargadas desde un PDF revisado.</p></div><x-badge tone="primary">Gestión {{ $gestion }}</x-badge></div>
        <dl class="detail-grid"><div><dt>Estudiante</dt><dd>{{ $estudiante->nombre_completo }}</dd></div><div><dt>Código RUDE</dt><dd>{{ $estudiante->codigo }}</dd></div><div><dt>Curso y paralelo</dt><dd>{{ $matricula ? $matricula->curso->nombre.' · '.$matricula->curso->paralelo : 'Sin matrícula' }}</dd></div><div><dt>Periodo seleccionado</dt><dd>{{ $periodoId ? $periodos->first()?->nombre : 'Año completo' }}</dd></div><div><dt>Gestión</dt><dd>{{ $gestion }}</dd></div><div><dt>Notas pendientes</dt><dd>{{ $pendientes }}</dd></div></dl>
        @if(!$matricula)<x-empty-state title="No tiene matrícula en esta gestión" description="Prueba con otra gestión. Si debe estar matriculado, pide a administración que revise su matrícula." />
        @elseif($filas->isEmpty())<x-empty-state title="Este curso aún no tiene materias asignadas" description="Administración debe asignar las materias y sus docentes para que aparezcan en el boletín." />
        @elseif($periodos->isEmpty())<x-empty-state title="Falta configurar los trimestres" description="Administración debe crear los periodos de esta gestión." icon="calendar" />
        @else<x-boletin-table :filas="$filas" :periodos="$periodos" :anual="$periodoId === null" />@endif
    </section>
    <p class="filter-note">@if($periodoId === null) Promedio anual: sumamos las notas de los tres trimestres y dividimos entre 3. Un trimestre sin nota cuenta como cero. @else Estás viendo la nota del trimestre elegido. @endif Se aprueba con {{ config('academico.nota_aprobacion') }} puntos o más.</p>
    @if($pendientes)<div class="document-notice"><x-icon name="clock" /><p>{{ $pendientes === 1 ? 'Falta una nota por llenar.' : 'Faltan '.$pendientes.' notas por llenar.' }} El boletín las mostrará como “Sin nota”. El resultado cambiará cuando el docente las complete.</p></div>@endif
    <a class="button button-secondary document-return" href="{{ route('boletines.index', ['buscar' => $estudiante->codigo, 'gestion' => $gestion]) }}"><x-icon name="calendar" /> Elegir otra gestión o trimestre</a>
</x-app>

<x-app title="Reportes"><x-page-heading title="Notas por materia"
        description="Elige una materia para ver las notas, imprimirlas o descargarlas para Excel."
        eyebrow="REPORTES ACADÉMICOS"><x-year-filter :gestiones="$gestiones" :gestion="$gestion"
            :action="route('reportes.index')" /></x-page-heading>
    <div class="course-grid">@forelse($asignaciones as $asignacion)<x-assignment-card :asignacion="$asignacion"
    :href="route('reportes.consolidado', ['asignacion_id' => $asignacion->id])" />@empty<div
                class="panel full-width"><x-empty-state title="Todavía no hay reportes para esta gestión"
                    description="Los reportes estarán disponibles cuando existan asignaciones de materias." /></div>
            @endforelse</div>
</x-app>
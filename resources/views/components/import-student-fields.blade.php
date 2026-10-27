@props(['pendientes'])
@if($pendientes->isNotEmpty())
<section class="form-body missing-student-data" aria-labelledby="datos-faltantes">
    <h2 id="datos-faltantes">Completa los datos que no aparecen en el PDF</h2>
    <p>Estos datos son obligatorios. Cópialos del documento del estudiante antes de guardar las notas.</p>
    @foreach($pendientes as $estudiante)
        <fieldset><legend>{{ $estudiante['nombre'] }} · RUDE {{ $estudiante['codigo'] }}</legend><div class="form-grid">
        @foreach($estudiante['campos'] as $campo => $etiqueta)
            @php($nombreCampo = 'estudiantes['.$estudiante['indice'].']['.$campo.']')
            @if($campo === 'genero')
                <x-field :name="$nombreCampo" :label="$etiqueta" :options="['M' => 'Masculino', 'F' => 'Femenino']" />
            @elseif($campo === 'fecha_nacimiento')
                <x-field :name="$nombreCampo" :label="$etiqueta" type="date" :max="date('Y-m-d')" />
            @else
                <x-field :name="$nombreCampo" :label="$etiqueta" :maxlength="$campo === 'lugar_nacimiento' ? 20 : ($campo === 'documento_identidad' ? 30 : 120)" />
            @endif
        @endforeach
        </div></fieldset>
    @endforeach
</section>
@endif
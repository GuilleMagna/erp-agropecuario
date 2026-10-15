@props(['titulo', 'serie'])
@php
    $maximo = max(1, collect($serie)->max(fn ($g) => abs($g['importe'])) ?? 1);
    $positivos = collect($serie)->filter(fn ($g) => $g['importe'] > 0);
    $totalPositivo = $positivos->sum('importe');
    $avance = 0;
@endphp
<div class="card h-100">
    <div class="card-header fw-semibold">{{ $titulo }}</div>
    <div class="card-body">
        <h3 class="h6">Barras · Total por grupo</h3>
        <div style="max-height:360px;overflow-y:auto" class="mb-4">
            @forelse($serie as $grupo)
                @php
                    $ancho = abs($grupo['importe']) / $maximo * 98;
                @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between gap-2 small">
                        <a href="{{ $grupo['url'] }}" class="text-decoration-none">{{ $grupo['nombre'] }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="visually-hidden">Ver comprobantes</span></a>
                        <span class="text-nowrap {{ $grupo['importe'] < 0 ? 'text-danger' : '' }}">${{ number_format($grupo['importe'],2,',','.') }}</span>
                    </div>
                    <a href="{{ $grupo['url'] }}" aria-label="Ver comprobantes de {{ $grupo['nombre'] }}">
                        <svg viewBox="0 0 200 14" width="100%" height="18" role="img" aria-label="{{ $grupo['nombre'] }}: ${{ number_format($grupo['importe'],2,',','.') }}">
                            <line x1="100" x2="100" y1="0" y2="14" stroke="#64748b" />
                            <rect x="{{ $grupo['importe'] < 0 ? 100 - $ancho : 100 }}" y="2" width="{{ $ancho }}" height="10" rx="2" fill="{{ $grupo['importe'] < 0 ? '#dc3545' : $grupo['color'] }}" />
                        </svg>
                    </a>
                </div>
            @empty<p class="text-muted">No hay comprobantes para estos filtros.</p>@endforelse
        </div>
        <h3 class="h6">Torta · Participación de saldos positivos</h3>
        @if($totalPositivo > 0)
            <svg viewBox="0 0 42 42" width="220" height="220" class="d-block mx-auto" role="img" aria-label="Distribución de importes positivos por {{ $titulo }}">
                <circle cx="21" cy="21" r="15.915494309" fill="none" stroke="#e9ecef" stroke-width="10" />
                @foreach($positivos as $grupo)
                    @php
                        $porcentaje = $grupo['importe'] / $totalPositivo * 100;
                        $desfase = 25 - $avance;
                        $avance += $porcentaje;
                    @endphp
                    <a href="{{ $grupo['url'] }}" aria-label="Ver comprobantes de {{ $grupo['nombre'] }}">
                        <circle cx="21" cy="21" r="15.915494309" fill="none" stroke="{{ $grupo['color'] }}" stroke-width="10" stroke-dasharray="{{ $porcentaje }} {{ 100 - $porcentaje }}" stroke-dashoffset="{{ $desfase }}">
                            <title>{{ $grupo['nombre'] }}: {{ number_format($porcentaje,1,',','.') }}% · ${{ number_format($grupo['importe'],2,',','.') }}</title>
                        </circle>
                    </a>
                @endforeach
            </svg>
            <div style="max-height:200px;overflow-y:auto">
                @foreach($positivos as $grupo)
                    <a class="d-flex justify-content-between gap-2 small text-decoration-none mb-1" href="{{ $grupo['url'] }}">
                        <span><span style="display:inline-block;width:10px;height:10px;background:{{ $grupo['color'] }}" aria-hidden="true"></span> {{ $grupo['nombre'] }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></span>
                        <span>{{ number_format($grupo['importe']/$totalPositivo*100,1,',','.') }}%</span>
                    </a>
                @endforeach
            </div>
        @else<p class="text-muted">No hay saldos positivos para representar en la torta.</p>@endif
        <small class="text-muted">Las notas de crédito restan del saldo de cada grupo. Los grupos con saldo negativo aparecen en las barras y no forman parte de la torta.</small>
    </div>
</div>

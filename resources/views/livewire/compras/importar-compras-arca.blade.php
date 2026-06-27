<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-cloud-upload me-2 text-primary"></i>Importar comprobantes desde ARCA
            </h5>
            <small class="text-muted">Importá el Excel de «Mis Comprobantes Recibidos» exportado desde el portal de ARCA / AFIP</small>
        </div>
        <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver a Compras
        </a>
    </div>

    {{-- Indicador de pasos --}}
    <div class="d-flex align-items-center mb-4 gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                 style="width:32px;height:32px;font-size:.85rem;
                        background:{{ $paso === 'subir' ? '#0d6efd' : '#198754' }};
                        color:white;">
                @if ($paso === 'subir') 1 @else <i class="bi bi-check-lg"></i> @endif
            </div>
            <span class="fw-semibold {{ $paso === 'subir' ? 'text-primary' : 'text-success' }}">Subir archivo</span>
        </div>
        <div class="flex-grow-1 border-top mx-2"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                 style="width:32px;height:32px;font-size:.85rem;
                        background:{{ $paso === 'previsualizar' ? '#0d6efd' : ($paso === 'resultado' ? '#198754' : '#dee2e6') }};
                        color:{{ in_array($paso, ['previsualizar','resultado']) ? 'white' : '#6c757d' }};">
                @if ($paso === 'resultado') <i class="bi bi-check-lg"></i> @else 2 @endif
            </div>
            <span class="fw-semibold {{ $paso === 'previsualizar' ? 'text-primary' : ($paso === 'resultado' ? 'text-success' : 'text-muted') }}">
                Previsualizar
            </span>
        </div>
        <div class="flex-grow-1 border-top mx-2"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                 style="width:32px;height:32px;font-size:.85rem;
                        background:{{ $paso === 'resultado' ? '#198754' : '#dee2e6' }};
                        color:{{ $paso === 'resultado' ? 'white' : '#6c757d' }};">
                @if ($paso === 'resultado') <i class="bi bi-check-lg"></i> @else 3 @endif
            </div>
            <span class="fw-semibold {{ $paso === 'resultado' ? 'text-success' : 'text-muted' }}">Resultado</span>
        </div>
    </div>

    {{-- ─── PASO 1: SUBIR ARCHIVO ─── --}}
    @if ($paso === 'subir')
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        {{-- Instrucciones --}}
                        <div class="alert alert-info d-flex gap-3 mb-4" role="alert">
                            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong>¿Cómo exportar el archivo desde ARCA?</strong>
                                <ol class="mb-0 mt-1 ps-3">
                                    <li>Ingresá a <strong>arca.gob.ar</strong> con tu CUIT y clave fiscal</li>
                                    <li>Ir a <strong>Mis Comprobantes → Comprobantes Recibidos</strong></li>
                                    <li>Filtrá por el período deseado y hacé clic en <strong>Exportar → Excel</strong></li>
                                    <li>Subí ese archivo acá (formatos .xlsx, .xls o .csv)</li>
                                </ol>
                            </div>
                        </div>

                        <form wire:submit="procesarArchivo">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Archivo de ARCA</label>

                                <div class="border-2 border-dashed rounded-3 p-5 text-center position-relative"
                                     style="border-color:#0d6efd40; background:#f8f9ff; cursor:pointer;"
                                     onclick="document.getElementById('archivoInput').click()">
                                    <i class="bi bi-file-earmark-excel fs-1 text-success mb-2 d-block"></i>
                                    @if ($archivo)
                                        <p class="mb-1 fw-semibold text-dark">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                            {{ $archivo->getClientOriginalName() }}
                                        </p>
                                        <small class="text-muted">{{ number_format($archivo->getSize() / 1024, 1) }} KB</small>
                                    @else
                                        <p class="mb-1 fw-semibold text-muted">Hacé clic para seleccionar el archivo</p>
                                        <small class="text-muted">Excel (.xlsx, .xls) o CSV — máx. 10 MB</small>
                                    @endif
                                    <input id="archivoInput" type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                           wire:model="archivo" accept=".xlsx,.xls,.csv" style="cursor:pointer;">
                                </div>

                                @error('archivo')
                                    <div class="text-danger small mt-2">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror

                                <div wire:loading wire:target="archivo" class="text-muted small mt-2">
                                    <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                                    Cargando archivo…
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4"
                                        wire:loading.attr="disabled" wire:target="procesarArchivo,archivo">
                                    <span wire:loading wire:target="procesarArchivo">
                                        <span class="spinner-border spinner-border-sm me-2"></span>Procesando…
                                    </span>
                                    <span wire:loading.remove wire:target="procesarArchivo">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Procesar archivo
                                    </span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── PASO 2: PREVISUALIZAR ─── --}}
    @if ($paso === 'previsualizar')
        @php
            $nuevas      = collect($filasParsadas)->where('estado', 'nuevo');
            $duplicadas  = collect($filasParsadas)->where('estado', 'duplicado');
            $conError    = collect($filasParsadas)->where('estado', 'error');
        @endphp

        {{-- Resumen rápido --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-success">{{ $nuevas->count() }}</div>
                    <div class="small text-muted">
                        <i class="bi bi-plus-circle me-1"></i>Se importarán
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-warning">{{ $duplicadas->count() }}</div>
                    <div class="small text-muted">
                        <i class="bi bi-skip-forward me-1"></i>Ya existen (se omitirán)
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-danger">{{ $conError->count() }}</div>
                    <div class="small text-muted">
                        <i class="bi bi-exclamation-triangle me-1"></i>Con errores (se omitirán)
                    </div>
                </div>
            </div>
        </div>

        @if ($nuevas->isEmpty() && $conError->isEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-info-circle me-2"></i>
                Todos los comprobantes del archivo ya existen en el sistema. No hay nada nuevo para importar.
            </div>
        @endif

        {{-- Tabla previsualización --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    {{ count($filasParsadas) }} comprobante{{ count($filasParsadas) !== 1 ? 's' : '' }} encontrado{{ count($filasParsadas) !== 1 ? 's' : '' }}
                </span>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Nuevo
                    </span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Duplicado
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Error
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>N° Comprobante</th>
                            <th>Proveedor</th>
                            <th>CUIT</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filasParsadas as $fila)
                            @php
                                $rowClass = match($fila['estado']) {
                                    'nuevo'     => '',
                                    'duplicado' => 'table-warning',
                                    'error'     => 'table-danger',
                                    default     => '',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>
                                    @if ($fila['estado'] === 'nuevo')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-plus-circle me-1"></i>Nuevo
                                        </span>
                                    @elseif ($fila['estado'] === 'duplicado')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-skip-forward me-1"></i>Existe
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"
                                              title="{{ $fila['error_msg'] ?? '' }}">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Error
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $fila['fecha'] ? \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ strtoupper(str_replace('_', ' ', $fila['tipo_comprobante'])) }}
                                    </span>
                                </td>
                                <td class="font-monospace">{{ $fila['numero_comprobante'] }}</td>
                                <td class="text-truncate" style="max-width:180px;" title="{{ $fila['nombre'] }}">
                                    {{ $fila['nombre'] ?: '—' }}
                                </td>
                                <td class="font-monospace text-muted">{{ $fila['cuit'] ?: '—' }}</td>
                                <td class="text-end">$ {{ number_format($fila['subtotal'], 2, ',', '.') }}</td>
                                <td class="text-end text-muted">
                                    @if ($fila['iva_importe'] > 0)
                                        $ {{ number_format($fila['iva_importe'], 2, ',', '.') }}
                                        @if ($fila['iva_porc'] > 0)
                                            <small class="text-muted">({{ $fila['iva_porc'] }}%)</small>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">$ {{ number_format($fila['total'], 2, ',', '.') }}</td>
                            </tr>
                            @if ($fila['estado'] === 'error' && !empty($fila['error_msg']))
                                <tr class="table-danger border-0">
                                    <td colspan="9" class="py-1 ps-4 text-danger small">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $fila['error_msg'] }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Botones --}}
        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-outline-secondary" wire:click="reiniciar">
                <i class="bi bi-arrow-left me-1"></i>Volver a subir otro archivo
            </button>

            @if ($nuevas->isNotEmpty())
                <button class="btn btn-success px-4" wire:click="confirmarImport"
                        wire:loading.attr="disabled" wire:target="confirmarImport"
                        onclick="return confirm('¿Confirmar la importación de {{ $nuevas->count() }} comprobante(s)?')">
                    <span wire:loading wire:target="confirmarImport">
                        <span class="spinner-border spinner-border-sm me-2"></span>Importando…
                    </span>
                    <span wire:loading.remove wire:target="confirmarImport">
                        <i class="bi bi-cloud-check me-1"></i>
                        Importar {{ $nuevas->count() }} comprobante{{ $nuevas->count() !== 1 ? 's' : '' }}
                    </span>
                </button>
            @endif
        </div>
    @endif

    {{-- ─── PASO 3: RESULTADO ─── --}}
    @if ($paso === 'resultado')
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm text-center p-5">
                    @if ($resumen['importadas'] > 0)
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                        </div>
                        <h4 class="fw-bold mb-1">¡Importación completada!</h4>
                    @else
                        <div class="mb-3">
                            <i class="bi bi-info-circle-fill text-info" style="font-size:4rem;"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Importación finalizada</h4>
                    @endif
                    <p class="text-muted mb-4">El proceso de importación terminó con el siguiente resultado:</p>

                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="card border-success-subtle bg-success-subtle py-3 rounded-3">
                                <div class="fs-1 fw-bold text-success">{{ $resumen['importadas'] }}</div>
                                <div class="small fw-semibold text-success">Importadas</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-warning-subtle bg-warning-subtle py-3 rounded-3">
                                <div class="fs-1 fw-bold text-warning">{{ $resumen['duplicadas'] }}</div>
                                <div class="small fw-semibold text-warning">Omitidas</div>
                                <div class="small text-muted" style="font-size:.7rem;">ya existían</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-danger-subtle bg-danger-subtle py-3 rounded-3">
                                <div class="fs-1 fw-bold text-danger">{{ $resumen['errores'] }}</div>
                                <div class="small fw-semibold text-danger">Con errores</div>
                            </div>
                        </div>
                    </div>

                    @if ($resumen['importadas'] > 0)
                        <div class="alert alert-success text-start" role="alert">
                            <i class="bi bi-check2-circle me-2"></i>
                            Los comprobantes importados ya están disponibles en
                            <strong>Compras → Listado</strong>.
                            Los proveedores nuevos fueron creados automáticamente.
                        </div>
                    @endif

                    @if ($resumen['errores'] > 0)
                        <div class="alert alert-warning text-start" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $resumen['errores'] }} fila{{ $resumen['errores'] !== 1 ? 's' : '' }} no se pudieron importar
                            por datos inválidos (fecha o importe). Podés agregarlas manualmente desde
                            <strong>Compras → Nueva compra</strong>.
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <button class="btn btn-outline-primary" wire:click="reiniciar">
                            <i class="bi bi-upload me-1"></i>Importar otro archivo
                        </button>
                        <a href="{{ route('compras.index') }}" class="btn btn-primary">
                            <i class="bi bi-list-ul me-1"></i>Ver compras
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

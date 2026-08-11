<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Sincronización de comprobantes ARCA
            </h5>
            <small class="text-muted">La descarga automática se ejecuta fuera de cPanel mediante GitHub Actions.</small>
        </div>
        <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver a Compras
        </a>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-3">
        <i class="bi bi-shield-check fs-4 mt-1"></i>
        <div>
            <div class="fw-semibold mb-1">Sincronización externa segura</div>
            <div class="small">
                El servidor recibe los comprobantes por un endpoint HTTPS autenticado y no ejecuta
                Node, Python, navegadores ni procesos del sistema. La ejecución manual se inicia desde
                el workflow <strong>Sincronizar comprobantes ARCA</strong> en GitHub Actions.
            </div>
        </div>
    </div>

    @if ($empresasArca->isEmpty())
        <div class="alert alert-warning">
            No hay empresas activas para ARCA. Configuralas desde
            <a href="{{ route('admin.empresas.index') }}" class="alert-link">Sistema → Empresas</a>.
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Empresas habilitadas</h6>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach ($empresasArca as $empresa)
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-building me-1"></i>{{ $empresa->razon_social }}
                            <span class="font-monospace ms-1">{{ $empresa->arca_cuit_representado ?? $empresa->cuit }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

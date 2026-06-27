@extends('layouts.app')

@section('title', 'Corrales — Feedlot')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-grid me-2 text-primary"></i>Corrales</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item text-muted">Feedlot</li>
                <li class="breadcrumb-item active">Corrales</li>
            </ol>
        </nav>
    </div>
</div>

<livewire:feedlot.gestion-corrales />
@endsection

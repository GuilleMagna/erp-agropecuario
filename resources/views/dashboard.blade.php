@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1">
                        Bienvenido, {{ auth()->user()->nombre_completo }}
                    </h5>
                    <p class="text-muted mb-0">
                        {{ auth()->user()->getRoleNames()->first() }} 
                        &mdash; {{ now()->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
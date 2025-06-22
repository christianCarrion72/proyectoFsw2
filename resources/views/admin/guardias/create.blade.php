@extends('admin.layouts.template')

@section('title', 'Registrar Nuevo Guardia')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Registrar Nuevo Guardia</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.guardias.index') }}">Guardias</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
            Formulario de Registro
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.guardias.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="{{ old('nombre') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="apellido_p" class="form-label">Apellido Paterno *</label>
                            <input type="text" class="form-control" id="apellido_p" name="apellido_p" 
                                   value="{{ old('apellido_p') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="apellido_m" class="form-label">Apellido Materno *</label>
                            <input type="text" class="form-control" id="apellido_m" name="apellido_m" 
                                   value="{{ old('apellido_m') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto</label>
                            <input type="text" class="form-control" id="foto" name="foto" 
                                   value="{{ old('foto') }}" placeholder="URL de la foto (opcional)">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ci" class="form-label">Cédula de Identidad *</label>
                            <input type="text" class="form-control" id="ci" name="ci" 
                                   value="{{ old('ci') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                   value="{{ old('telefono') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                   name="password_confirmation" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_ini" class="form-label">Fecha de Inicio *</label>
                            <input type="date" class="form-control" id="fecha_ini" name="fecha_ini" 
                                   value="{{ old('fecha_ini') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                   value="{{ old('fecha_fin') }}">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.guardias.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Guardia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
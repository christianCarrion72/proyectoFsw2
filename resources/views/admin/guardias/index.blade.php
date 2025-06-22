@extends('admin.layouts.template')

@section('title', 'Gestión de Guardias')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Guardias</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Guardias</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Mis Guardias
            <a href="{{ route('admin.guardias.create') }}" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Registrar Nuevo Guardia
            </a>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>CI</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardias as $guardia)
                        <tr>
                            <td>{{ $guardia->id }}</td>
                            <td>{{ $guardia->persona->nombre }} {{ $guardia->persona->apellido }}</td>
                            <td>{{ $guardia->persona->ci }}</td>
                            <td>{{ $guardia->persona->user->email }}</td>
                            <td>
                                <span class="badge {{ $guardia->estado ? 'bg-success' : 'bg-danger' }}">
                                    {{ $guardia->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>{{ $guardia->fecha_ini }}</td>
                            <td>{{ $guardia->fecha_fin ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.guardias.show', $guardia) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.guardias.edit', $guardia) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.guardias.destroy', $guardia) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este guardia?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No tienes guardias registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
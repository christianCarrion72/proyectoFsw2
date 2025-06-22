@extends('admin.layouts.template')

@section('title', 'Detalles del Guardia')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Detalles del Guardia</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.guardias.index') }}">Guardias</a></li>
        <li class="breadcrumb-item active">Detalles</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user me-1"></i>
            Información del Guardia
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Datos Personales</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td>{{ $guardia->persona->nombre }}</td>
                        </tr>
                        <tr>
                            <td><strong>Apellido:</strong></td>
                            <td>{{ $guardia->persona->apellido }}</td>
                        </tr>
                        <tr>
                            <td><strong>CI:</strong></td>
                            <td>{{ $guardia->persona->ci }}</td>
                        </tr>
                        <tr>
                            <td><strong>Teléfono:</strong></td>
                            <td>{{ $guardia->persona->telefono }}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ $guardia->persona->user->email }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Información del Guardia</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td>
                                @if($guardia->estado)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de Inicio:</strong></td>
                            <td>{{ $guardia->fecha_ini }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de Fin:</strong></td>
                            <td>{{ $guardia->fecha_fin ?? 'No definida' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Estado de Pago:</strong></td>
                            <td>
                                @if($guardia->persona->user->payment_status)
                                    <span class="badge bg-success">Al día</span>
                                @else
                                    <span class="badge bg-danger">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Registrado:</strong></td>
                            <td>{{ $guardia->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.guardias.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <div>
                    <a href="{{ route('admin.guardias.edit', $guardia) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <form action="{{ route('admin.guardias.destroy', $guardia) }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('¿Estás seguro de eliminar este guardia?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
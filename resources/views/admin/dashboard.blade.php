@extends('admin.layouts.template')

@section('content')
    <div class="container-fluid">
        <h1 class="mt-4">Dashboard de Administración Anti-Bullying</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>

        <!-- KPIs Principales -->
        <div class="row" id="kpi-cards">
            <div class="col-xl-2 col-md-4">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Total Incidentes</div>
                                <div class="h3" id="total-incidents">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Cyberbullying</div>
                                <div class="h3" id="total-cyberbullying">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-laptop fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card bg-secondary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Tweets Analizados</div>
                                <div class="h3" id="total-tweets">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fab fa-twitter fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Alta Severidad</div>
                                <div class="h3" id="high-severity">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-fire fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Casos Resueltos</div>
                                <div class="h3" id="resolved-cases">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small">Tiempo Promedio (días)</div>
                                <div class="h3" id="avg-resolution-time">Cargando...</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Gráficos que funcionan del template del administrador -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-chart-area mr-1"></i>Area Chart Example
                    </div>
                    <div class="card-body"><canvas id="myAreaChart" width="100" height="50"></canvas></div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-chart-bar mr-1"></i>Bar Chart Example</div>
                    <div class="card-body"><canvas id="myBarChart" width="100%" height="50"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Tabla de Incidentes Recientes -->
        <div class="card mb-4" id="incidents-table">
            <div class="card-header">
                <i class="fas fa-table mr-1"></i>Incidentes Recientes de Bullying
                <div class="float-right">
                    <button class="btn btn-sm btn-success" onclick="regenerateData()">
                        <i class="fas fa-sync-alt"></i> Regenerar Datos
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="exportData()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="incidentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Severidad</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Tiempo Resolución</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="incidents-tbody">
                            <tr>
                                <td colspan="8" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para detalles del incidente -->
        <div class="modal fade" id="incidentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalles del Incidente</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="incident-details">
                        <!-- Contenido dinámico -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="updateIncidentStatus()">Actualizar Estado</button>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

<!-- Toastr para notificaciones -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">

<!-- Dashboard JavaScript -->
<script src="{{ asset('js/bullying-dashboard.js') }}"></script>

<script>
// Configuración adicional específica para este dashboard
$(document).ready(function() {
    // Configurar CSRF token para todas las peticiones AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Mostrar mensaje de bienvenida
    setTimeout(function() {
        if (typeof toastr !== 'undefined') {
            toastr.info('Dashboard de Administración Anti-Bullying cargado correctamente');
        }
    }, 1000);
});
</script>
@endsection

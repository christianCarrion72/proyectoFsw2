// Dashboard de Vigilancia Anti-Bullying
// Variables globales para los gráficos
let charts = {};
let currentIncidentId = null;

// Función para obtener colores por defecto
function getDefaultColors(count) {
    const colors = [
        '#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997',
        '#17a2b8', '#6f42c1', '#e83e8c', '#6c757d', '#007bff'
    ];
    
    const result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    return result;
}

// Configuración global de Chart.js (versión moderna)
function configureChartDefaults() {
    if (typeof Chart !== 'undefined') {
        // Chart.js v3+ syntax
        Chart.defaults.font = {
            family: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif'
        };
        Chart.defaults.color = '#292b2c';
        
        // Configurar plugins por defecto
        Chart.defaults.plugins.legend.display = true;
        Chart.defaults.plugins.tooltip.enabled = true;
        
        console.log('Chart.js configurado correctamente, versión:', Chart.version);
    } else {
        console.error('Chart.js no está disponible');
    }
}

// Inicializar dashboard al cargar la página
$(document).ready(function() {
    // Esperar a que Chart.js esté disponible
    function initializeDashboard() {
        if (typeof Chart !== 'undefined') {
            console.log('Inicializando dashboard con Chart.js versión:', Chart.version);
            configureChartDefaults();
            
            loadKPIs();
            loadAllCharts();
            loadIncidentsTable();
            
            // Actualizar datos cada 5 minutos
            setInterval(function() {
                loadKPIs();
                refreshAllCharts();
            }, 300000);
        } else {
            console.log('Esperando a que Chart.js se cargue...');
            setTimeout(initializeDashboard, 100);
        }
    }
    
    initializeDashboard();
});

// Cargar KPIs principales
function loadKPIs() {
    $.ajax({
        url: '/api/bullying/kpis',
        method: 'GET',
        success: function(data) {
            updateKPICards(data);
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar KPIs:', error);
            showErrorMessage('Error al cargar los indicadores principales');
        }
    });
}

// Actualizar tarjetas de KPIs
function updateKPICards(data) {
    $('#total-incidents').text(data.total_incidents || 0);
    $('#total-cyberbullying').text(data.total_cyberbullying_cases || 0);
    $('#total-tweets').text(data.total_tweets_analyzed || 0);
    $('#high-severity').text(data.high_severity_incidents || 0);
    $('#resolved-cases').text(data.resolved_incidents || 0);
    $('#avg-resolution-time').text((data.average_resolution_time || 0) + ' días');
    
    // Mostrar información adicional en tooltips o badges
    if (data.cyberbullying_detection_rate) {
        $('#total-tweets').attr('title', `Tasa de detección: ${data.cyberbullying_detection_rate}%`);
    }
    
    if (data.most_common_location) {
        $('#total-incidents').attr('title', `Ubicación más común: ${data.most_common_location}`);
    }
    
    if (data.most_affected_age_group) {
        $('#high-severity').attr('title', `Grupo más afectado: ${data.most_affected_age_group}`);
    }
}

// Cargar tabla de incidentes
function loadIncidentsTable() {
    $.ajax({
        url: '/api/bullying/incidents',
        method: 'GET',
        success: function(data) {
            console.log('Datos recibidos:', data);
            let incidents = [];
            
            // Verificar diferentes formatos de respuesta
            if (data && data.incidents) {
                if (Array.isArray(data.incidents)) {
                    incidents = data.incidents;
                } else if (typeof data.incidents === 'object') {
                    // Convertir objeto con índices numéricos a array
                    incidents = Object.values(data.incidents);
                    console.log('Convertido objeto a array:', incidents);
                }
            } else if (data && Array.isArray(data)) {
                incidents = data;
            } else if (data && typeof data === 'object') {
                // Si data es un objeto con índices numéricos
                incidents = Object.values(data);
                console.log('Convertido data objeto a array:', incidents);
            } else {
                console.error('Formato de datos inesperado:', data);
            }
            
            populateIncidentsTable(incidents);
        },
        error: function(xhr) {
            console.error('Error cargando incidentes:', xhr.responseText);
            $('#incidents-tbody').html('<tr><td colspan="8" class="text-center text-danger">Error al cargar datos</td></tr>');
        }
    });
}

// Poblar tabla de incidentes
function populateIncidentsTable(incidents) {
    const tbody = $('#incidents-tbody');
    tbody.empty();
    
    // Verificar que incidents sea un array
    if (!Array.isArray(incidents)) {
        console.error('Los datos de incidentes no son un array:', incidents);
        tbody.html('<tr><td colspan="8" class="text-center text-danger">Error: Formato de datos incorrecto</td></tr>');
        return;
    }
    
    if (incidents.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center">No hay incidentes registrados</td></tr>');
        return;
    }
    
    incidents.slice(0, 20).forEach(function(incident) {
        const statusBadge = getStatusBadge(incident.status);
        const severityBadge = getSeverityBadge(incident.severity);
        const typeBadge = getTypeBadge(incident.type);
        
        const row = `
            <tr>
                <td>${incident.id}</td>
                <td>${formatDate(incident.date)}</td>
                <td>${typeBadge}</td>
                <td>${severityBadge}</td>
                <td>${incident.location}</td>
                <td>${statusBadge}</td>
                <td>${incident.resolution_days ? incident.resolution_days + ' días' : 'Pendiente'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showIncidentDetails(${incident.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editIncident(${incident.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Inicializar DataTable si no existe
    if (!$.fn.DataTable.isDataTable('#incidentsTable')) {
        $('#incidentsTable').DataTable({
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "sInfoThousands": ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                },
                "buttons": {
                    "copy": "Copiar",
                    "colvis": "Visibilidad"
                }
            },
            "pageLength": 10,
            "order": [[1, "desc"]] // Ordenar por fecha descendente
        });
    }
}

// Cargar todos los gráficos (solo 3 para debugging)
function loadAllCharts() {
    console.log('Cargando solo 3 gráficos para debugging...');
    loadChart('monthly', 'monthlyChart', 'line');
    loadChart('types', 'typesChart', 'doughnut');
    loadChart('severity', 'severityChart', 'bar');
    // Comentados temporalmente para debugging
    // loadChart('locations', 'locationChart', 'pie');
    // loadChart('age-groups', 'ageGroupChart', 'doughnut');
    // loadChart('status', 'statusChart', 'pie');
    // loadChart('cyberbullying-sentiment', 'cyberbullySentimentChart', 'bar');
    // loadChart('tweets-classification', 'tweetsClassificationChart', 'doughnut');
    // loadChart('resolution-time', 'resolutionTimeChart', 'bar');
    // loadChart('reporting-source', 'reportingSourceChart', 'pie');
}

// Cargar un gráfico específico
function loadChart(type, canvasId, chartType) {
    console.log(`Cargando gráfico: ${type} en canvas: ${canvasId}`);
    $.ajax({
        url: `/api/bullying/chart/${type}`,
        method: 'GET',
        success: function(data) {
            console.log(`Datos recibidos para gráfico ${type}:`, data);
            createChart(canvasId, chartType, data);
        },
        error: function(xhr, status, error) {
            console.error(`Error al cargar gráfico ${type}:`, error, xhr.responseText);
            showErrorMessage(`Error al cargar el gráfico de ${type}`);
            // Mostrar mensaje en el canvas
            showChartError(canvasId, `Error al cargar datos de ${type}`);
        }
    });
}

// Inicializar gráficos
function initializeCharts() {
    loadAllCharts();
}

// Crear gráfico con Chart.js
function createChart(canvasId, type, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error(`Canvas ${canvasId} no encontrado`);
        return;
    }
    
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está disponible para crear el gráfico:', canvasId);
        showChartError(canvasId, 'Chart.js no disponible');
        return;
    }
    
    // Verificar que los datos sean válidos
    if (!data || !data.labels || !data.data) {
        console.error('Datos inválidos para el gráfico:', canvasId, data);
        showChartError(canvasId, 'Datos inválidos');
        return;
    }
    
    // Destruir gráfico existente si existe
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    
    const config = {
        type: type,
        data: {
            labels: data.labels,
            datasets: [{
                label: getChartLabel(canvasId),
                data: data.data,
                backgroundColor: data.colors || getDefaultColors(data.data.length),
                borderColor: data.borderColors || data.colors || getDefaultColors(data.data.length),
                borderWidth: type === 'line' ? 2 : 1,
                fill: type === 'line' ? false : true
            }]
        },
        options: getChartOptions(type, canvasId)
    };
    
    console.log(`Creando gráfico ${canvasId} con configuración:`, config);
    
    try {
        charts[canvasId] = new Chart(ctx.getContext('2d'), config);
        console.log(`Gráfico ${canvasId} creado exitosamente`);
    } catch (error) {
        console.error(`Error al crear gráfico ${canvasId}:`, error);
        showChartError(canvasId, `Error al crear gráfico: ${error.message}`);
    }
}

// Obtener etiqueta para el gráfico
function getChartLabel(canvasId) {
    const labels = {
        'monthlyChart': 'Incidentes por Mes',
        'typesChart': 'Tipos de Bullying',
        'severityChart': 'Nivel de Severidad',
        'locationChart': 'Ubicaciones',
        'ageGroupChart': 'Grupos de Edad',
        'statusChart': 'Estados',
        'cyberbullySentimentChart': 'Sentimiento',
        'tweetsClassificationChart': 'Clasificación',
        'resolutionTimeChart': 'Tiempo de Resolución',
        'reportingSourceChart': 'Fuente de Reporte'
    };
    return labels[canvasId] || 'Datos';
}

// Obtener opciones de configuración para gráficos
function getChartOptions(type, canvasId) {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: type === 'line' ? 'top' : 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1
            }
        }
    };
    
    if (type === 'line' || type === 'bar') {
        baseOptions.scales = {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            }
        };
    }
    
    // Configuraciones específicas por gráfico
    if (canvasId === 'monthlyChart') {
        baseOptions.plugins.tooltip.callbacks = {
            label: function(context) {
                return `${context.dataset.label}: ${context.parsed.y} incidentes`;
            }
        };
    }
    
    return baseOptions;
}

// Mostrar error en el gráfico
function showChartError(canvasId, message) {
    const ctx = document.getElementById(canvasId);
    if (ctx) {
        const context = ctx.getContext('2d');
        context.clearRect(0, 0, ctx.width, ctx.height);
        context.fillStyle = '#dc3545';
        context.font = '14px Arial';
        context.textAlign = 'center';
        context.fillText(message, ctx.width / 2, ctx.height / 2);
    }
}

// Actualizar todos los gráficos
function refreshAllCharts() {
    loadAllCharts();
}

// Crear gráfico de líneas
function createLineChart(canvasId, title, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    
    charts[canvasId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Incidentes',
                data: data,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Crear gráfico de barras
function createBarChart(canvasId, title, labels, data, colors) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    
    charts[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cantidad',
                data: data,
                backgroundColor: colors || ['#dc3545', '#fd7e14', '#6f42c1', '#20c997'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Crear gráfico circular
function createPieChart(canvasId, title, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    
    charts[canvasId] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Crear gráfico de barras horizontal
function createHorizontalBarChart(canvasId, title, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    
    charts[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Incidentes',
                data: data,
                backgroundColor: '#17a2b8',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                title: {
                    display: true,
                    text: title
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Funciones de utilidad
function getStatusBadge(status) {
    const badges = {
        'Pending': '<span class="badge badge-warning">Pendiente</span>',
        'In Progress': '<span class="badge badge-info">En Progreso</span>',
        'Resolved': '<span class="badge badge-success">Resuelto</span>',
        'Closed': '<span class="badge badge-secondary">Cerrado</span>'
    };
    return badges[status] || '<span class="badge badge-light">Desconocido</span>';
}

function getSeverityBadge(severity) {
    const colors = {
        1: 'success',
        2: 'info', 
        3: 'warning',
        4: 'danger',
        5: 'dark'
    };
    return `<span class="badge badge-${colors[severity] || 'light'}">Nivel ${severity}</span>`;
}

function getTypeBadge(type) {
    const badges = {
        'Physical': '<span class="badge badge-danger">Físico</span>',
        'Verbal': '<span class="badge badge-warning">Verbal</span>',
        'Cyberbullying': '<span class="badge badge-info">Ciberacoso</span>',
        'Social Exclusion': '<span class="badge badge-secondary">Exclusión Social</span>'
    };
    return badges[type] || '<span class="badge badge-light">' + type + '</span>';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Funciones de interacción
function refreshChart(type) {
    const chartMap = {
        'monthly': { canvas: 'monthlyChart', type: 'line' },
        'types': { canvas: 'typesChart', type: 'doughnut' },
        'severity': { canvas: 'severityChart', type: 'bar' },
        'locations': { canvas: 'locationChart', type: 'pie' },
        'age-groups': { canvas: 'ageGroupChart', type: 'doughnut' },
        'status': { canvas: 'statusChart', type: 'pie' },
        'cyberbullying-sentiment': { canvas: 'cyberbullySentimentChart', type: 'bar' },
        'tweets-classification': { canvas: 'tweetsClassificationChart', type: 'doughnut' },
        'resolution-time': { canvas: 'resolutionTimeChart', type: 'bar' },
        'reporting-source': { canvas: 'reportingSourceChart', type: 'pie' }
    };
    
    const chartInfo = chartMap[type];
    if (chartInfo) {
        loadChart(type, chartInfo.canvas, chartInfo.type);
        showSuccessMessage(`Gráfico actualizado`);
    }
}

function regenerateData() {
    if (!confirm('¿Está seguro de que desea regenerar todos los datos? Esta acción no se puede deshacer.')) {
        return;
    }
    
    $.ajax({
        url: '/api/bullying/regenerate',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            showSuccessMessage('Datos regenerados exitosamente');
            setTimeout(function() {
                location.reload();
            }, 2000);
        },
        error: function(xhr) {
            console.error('Error regenerando datos:', xhr.responseText);
            showErrorMessage('Error al regenerar los datos');
        }
    });
}

// Exportar datos a CSV
function exportData() {
    $.ajax({
        url: '/api/bullying/incidents',
        method: 'GET',
        success: function(data) {
            if (data.incidents && data.incidents.length > 0) {
                downloadCSV(data.incidents, 'bullying_incidents_export.csv');
                showSuccessMessage('Datos exportados exitosamente');
            } else {
                showErrorMessage('No hay datos para exportar');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al exportar datos:', error);
            showErrorMessage('Error al exportar los datos');
        }
    });
}

// Descargar datos como CSV
function downloadCSV(data, filename) {
    if (!data || data.length === 0) return;
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function showIncidentDetails(incidentId) {
    currentIncidentId = incidentId;
    
    $.ajax({
        url: '/api/bullying/incidents',
        method: 'GET',
        success: function(data) {
            const incident = data.incidents.find(i => i.id === incidentId);
            if (incident) {
                displayIncidentDetails(incident);
                $('#incidentModal').modal('show');
            }
        },
        error: function(xhr) {
            showErrorMessage('Error al cargar detalles del incidente');
        }
    });
}

function displayIncidentDetails(incident) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información Básica</h6>
                <p><strong>ID:</strong> ${incident.id}</p>
                <p><strong>Fecha:</strong> ${formatDate(incident.date)}</p>
                <p><strong>Tipo:</strong> ${incident.type}</p>
                <p><strong>Severidad:</strong> Nivel ${incident.severity}</p>
            </div>
            <div class="col-md-6">
                <h6>Estado y Ubicación</h6>
                <p><strong>Estado:</strong> ${incident.status}</p>
                <p><strong>Ubicación:</strong> ${incident.location}</p>
                <p><strong>Tiempo de Resolución:</strong> ${incident.resolution_days ? incident.resolution_days + ' días' : 'Pendiente'}</p>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Descripción</h6>
                <p class="text-muted">Incidente de ${incident.type.toLowerCase()} reportado en ${incident.location}</p>
            </div>
        </div>
    `;
    
    $('#incident-details').html(details);
}

function editIncident(incidentId) {
    showIncidentDetails(incidentId);
}

function updateIncidentStatus() {
    if (!currentIncidentId) return;
    
    showSuccessMessage('Estado actualizado (funcionalidad en desarrollo)');
    $('#incidentModal').modal('hide');
}

// Funciones de notificación
function showSuccessMessage(message) {
    toastr.success(message);
}

function showErrorMessage(message) {
    toastr.error(message);
}

// Configurar toastr
if (typeof toastr !== 'undefined') {
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };
}
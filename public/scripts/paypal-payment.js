// PayPal Payment Script - Versión Mejorada
let paypalButtonRendered = false;

function showPayPalPayment() {
    document.getElementById('paypalPaymentForm').style.display = 'block';
    
    // Renderizar botón de PayPal solo una vez
    if (!paypalButtonRendered) {
        renderPayPalButton();
        paypalButtonRendered = true;
    }
}

function hidePayPalPayment() {
    document.getElementById('paypalPaymentForm').style.display = 'none';
}

function renderPayPalButton() {
    // Limpiar contenedor antes de renderizar
    const container = document.getElementById('paypal-button-container');
    if (container) {
        container.innerHTML = '';
    }
    
    paypal.Buttons({
        createOrder: function(data, actions) {
            console.log('Creando orden PayPal...');
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '10.00',
                        currency_code: 'USD'
                    },
                    description: 'Acceso Premium RDS'
                }]
            });
        },
        onApprove: function(data, actions) {
            console.log('PayPal onApprove ejecutado:', data);
            showPayPalStatus('Procesando pago...', 'info');
            
            return actions.order.capture().then(function(details) {
                console.log('PayPal order captured:', details);
                showPayPalStatus('Pago completado. Verificando...', 'success');
                
                // Preparar datos para enviar al servidor
                const paymentData = {
                    orderID: data.orderID,
                    payerID: data.payerID,
                    details: {
                        id: details.id,
                        status: details.status,
                        payer: details.payer,
                        purchase_units: details.purchase_units
                    }
                };
                
                console.log('Enviando datos al servidor:', paymentData);
                
                // Obtener token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    console.error('Token CSRF no encontrado');
                    showPayPalStatus('Error de configuración: Token CSRF no encontrado', 'error');
                    return;
                }
                
                // Enviar datos al servidor
                fetch('/payment/paypal/success', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(paymentData)
                })
                .then(response => {
                    console.log('Respuesta del servidor recibida:', response.status);
                    
                    // Verificar si la respuesta es JSON válida
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta del servidor no es JSON válida');
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Datos procesados del servidor:', data);
                    
                    if (data.success) {
                        showPayPalStatus('¡Pago procesado exitosamente! Redirigiendo...', 'success');
                        
                        // Redirigir después de 2 segundos
                        setTimeout(() => {
                            window.location.href = data.redirect_url || '/dashboard';
                        }, 2000);
                    } else {
                        console.error('Error del servidor:', data.message);
                        showPayPalStatus('Error al procesar el pago: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud:', error);
                    showPayPalStatus('Error de conexión: ' + error.message, 'error');
                });
            }).catch(function(error) {
                console.error('Error capturando orden PayPal:', error);
                showPayPalStatus('Error al procesar el pago con PayPal', 'error');
            });
        },
        onError: function(err) {
            console.error('PayPal Error:', err);
            showPayPalStatus('Error en el pago con PayPal. Inténtalo de nuevo.', 'error');
        },
        onCancel: function(data) {
            console.log('PayPal cancelado:', data);
            showPayPalStatus('Pago cancelado por el usuario', 'warning');
        }
    }).render('#paypal-button-container').catch(function(error) {
        console.error('Error renderizando botón PayPal:', error);
        showPayPalStatus('Error al cargar PayPal. Recarga la página e intenta de nuevo.', 'error');
    });
}

function showPayPalStatus(message, type) {
    const statusDiv = document.getElementById('paypalPaymentStatus');
    if (!statusDiv) {
        console.error('Elemento paypalPaymentStatus no encontrado');
        return;
    }
    
    let alertClass;
    let icon;
    
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            icon = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            alertClass = 'alert-danger';
            icon = '<i class="fas fa-exclamation-triangle"></i>';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            icon = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'info':
            alertClass = 'alert-info';
            icon = '<i class="fas fa-info-circle"></i>';
            break;
        default:
            alertClass = 'alert-secondary';
            icon = '<i class="fas fa-info"></i>';
    }
    
    statusDiv.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${icon} ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Auto-ocultar después de 10 segundos para errores, 5 para éxito
    const timeout = type === 'error' ? 10000 : 5000;
    setTimeout(() => {
        const alert = statusDiv.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => {
                statusDiv.innerHTML = '';
            }, 300);
        }
    }, timeout);
}
const stripe = Stripe(window.STRIPE_KEY); 
const elements = stripe.elements();

// Crear elemento de tarjeta
const cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#424770',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
        invalid: {
            color: '#9e2146',
        },
    },
});

let cardMounted = false;

function showCardPayment() {
    document.getElementById('cardPaymentForm').style.display = 'block';
    
    // Montar el elemento de tarjeta solo una vez
    if (!cardMounted) {
        cardElement.mount('#card-element');
        cardMounted = true;
        
        // Manejar errores en tiempo real
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
    }
}

function hideCardPayment() {
    document.getElementById('cardPaymentForm').style.display = 'none';
}

function showCardStatus(message, type) {
    const statusDiv = document.getElementById('cardPaymentStatus');
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-warning';
    
    statusDiv.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

// Manejar envío del formulario
document.getElementById('submitCardPayment').addEventListener('click', async function(event) {
    event.preventDefault();
    
    const submitButton = document.getElementById('submitCardPayment');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    
    showCardStatus('Procesando pago...', 'warning');
    
    try {
        // Crear método de pago
        const {error, paymentMethod} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                email: document.getElementById('cardEmail').value,
            },
        });
        
        if (error) {
            console.error('Error creando método de pago:', error);
            showCardStatus('Error: ' + error.message, 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-credit-card"></i> Procesar Pago';
            return;
        }
        
        console.log('Método de pago creado:', paymentMethod);
        
        // Enviar al servidor
        const response = await fetch('/payment/process/card', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                payment_method_id: paymentMethod.id,
                amount: parseFloat(document.getElementById('cardAmount').value)
            })
        });
        
        const result = await response.json();
        
        if (result.requires_action) {
            // Manejar 3D Secure
            const {error: confirmError} = await stripe.confirmCardPayment(
                result.payment_intent.client_secret
            );
            
            if (confirmError) {
                showCardStatus('Error de autenticación: ' + confirmError.message, 'error');
            } else {
                showCardStatus('Pago procesado exitosamente', 'success');
                setTimeout(() => {
                    // Redirigir al dashboard en lugar de recargar
                    window.location.href = result.redirect_url || '/dashboard';
                }, 2000);
            }
        } else if (response.ok && result.success) {
            showCardStatus('Pago procesado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = result.redirect_url;
            }, 2000);
        } else {
            showCardStatus('Error al procesar el pago', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showCardStatus('Error de conexión: ' + error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-credit-card"></i> Procesar Pago';
    }
});
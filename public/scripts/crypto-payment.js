let web3;
let userAccount;
const SEPOLIA_CHAIN_ID = '0xaa36a7'; // 11155111 en hexadecimal
const USD_AMOUNT = 1; // Monto cambiado a $1 USD

// Direcciones de contratos de tokens en Sepolia
const TOKEN_CONTRACTS = {
    'USDT': '0x7169D38820dfd117C3FA1f22a697dBA58d90BA06', // USDT en Sepolia
    'BNB': '0x0000000000000000000000000000000000000000',   // Placeholder para BNB
    'BTC': 'BITCOIN_NETWORK' // Bitcoin usa su propia red
};

// Precios aproximados para conversión (en producción usar API real)
let CRYPTO_PRICES = {
    'ETH': 2000,  // $2000 por ETH
    'USDT': 1,    // $1 por USDT
    'BNB': 300,   // $300 por BNB
    'BTC': 45000  // $45000 por BTC
};

function showCryptoPayment() {
    document.getElementById('cryptoPaymentForm').style.display = 'block';
    updatePaymentAmount(); // Actualizar monto al mostrar
}

// Función para actualizar el monto según la criptomoneda seleccionada
function updatePaymentAmount() {
    const tokenType = document.getElementById('tokenType').value;
    const cryptoAmount = calculateCryptoAmount(USD_AMOUNT, tokenType);
    
    document.getElementById('paymentAmount').value = cryptoAmount;
    document.getElementById('usdAmount').textContent = `Monto a pagar: $${USD_AMOUNT} USD`;
    
    // Actualizar texto informativo
    const infoText = document.getElementById('conversionInfo');
    if (infoText) {
        infoText.textContent = `≈ ${cryptoAmount} ${tokenType} (1 ${tokenType} = $${CRYPTO_PRICES[tokenType]})`;
    }
    
    // Mostrar el monto convertido en tiempo real
    console.log(`Monto actualizado: ${cryptoAmount} ${tokenType} = $${USD_AMOUNT} USD`);
}

// Función para calcular la cantidad de criptomoneda equivalente
function calculateCryptoAmount(usdAmount, tokenType) {
    const price = CRYPTO_PRICES[tokenType];
    const cryptoAmount = usdAmount / price;
    
    // Formatear según el tipo de token
    if (tokenType === 'BTC') {
        return cryptoAmount.toFixed(8); // Bitcoin usa 8 decimales
    } else if (tokenType === 'USDT') {
        return cryptoAmount.toFixed(2); // USDT usa 2 decimales para mostrar
    } else {
        return cryptoAmount.toFixed(6); // ETH y otros usan 6 decimales para mostrar
    }
}

// Función para obtener precios actuales (opcional - usar API real)
async function updateCryptoPrices() {
    try {
        // En producción, usar una API real como CoinGecko
        const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=ethereum,tether,binancecoin,bitcoin&vs_currencies=usd');
        const prices = await response.json();
        
        CRYPTO_PRICES = {
            'ETH': prices.ethereum?.usd || CRYPTO_PRICES.ETH,
            'USDT': prices.tether?.usd || CRYPTO_PRICES.USDT,
            'BNB': prices.binancecoin?.usd || CRYPTO_PRICES.BNB,
            'BTC': prices.bitcoin?.usd || CRYPTO_PRICES.BTC
        };
        
        updatePaymentAmount(); // Actualizar monto con precios nuevos
        showStatus('Precios actualizados', 'success');
    } catch (error) {
        console.log('No se pudieron actualizar los precios, usando valores por defecto');
    }
}

async function connectWallet() {
    if (typeof window.ethereum !== 'undefined') {
        try {
            // Actualizar precios antes de conectar
            await updateCryptoPrices();
            
            // Solicitar acceso a MetaMask
            await window.ethereum.request({ method: 'eth_requestAccounts' });
            
            web3 = new Web3(window.ethereum);
            const accounts = await web3.eth.getAccounts();
            userAccount = accounts[0];
            
            // Verificar que estamos en la red Sepolia
            const chainId = await window.ethereum.request({ method: 'eth_chainId' });
            if (chainId !== SEPOLIA_CHAIN_ID) {
                await switchToSepolia();
            }
            
            document.getElementById('connectWalletBtn').innerHTML = 
                `<i class="fas fa-check me-2"></i>Conectado: ${userAccount.substring(0, 6)}...${userAccount.substring(38)}`;
            document.getElementById('connectWalletBtn').disabled = true;
            document.getElementById('sendPaymentBtn').disabled = false;
            
            showStatus('Wallet conectado exitosamente', 'success');
            
        } catch (error) {
            console.error('Error conectando wallet:', error);
            showStatus('Error conectando MetaMask: ' + error.message, 'error');
        }
    } else {
        showStatus('MetaMask no está instalado. Por favor, instala MetaMask para continuar.', 'error');
    }
}

async function switchToSepolia() {
    try {
        await window.ethereum.request({
            method: 'wallet_switchEthereumChain',
            params: [{ chainId: SEPOLIA_CHAIN_ID }],
        });
    } catch (switchError) {
        // Si la red no está agregada, la agregamos
        if (switchError.code === 4902) {
            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [{
                    chainId: SEPOLIA_CHAIN_ID,
                    chainName: 'Sepolia Test Network',
                    nativeCurrency: {
                        name: 'Ethereum',
                        symbol: 'ETH',
                        decimals: 18
                    },
                    rpcUrls: ['https://sepolia.infura.io/v3/'],
                    blockExplorerUrls: ['https://sepolia.etherscan.io/']
                }]
            });
        }
    }
}

async function sendPayment() {
    const tokenType = document.getElementById('tokenType').value;
    const amount = document.getElementById('paymentAmount').value;
    
    try {
        showStatus('Preparando transacción...', 'pending');
        
        let txHash;
        
        if (tokenType === 'BTC') {
            // Para Bitcoin, mostrar instrucciones especiales
            showBitcoinPaymentInstructions(amount);
            return;
        } else if (tokenType === 'ETH') {
            txHash = await sendEthPayment(amount);
        } else {
            txHash = await sendTokenPayment(tokenType, amount);
        }
        
        if (txHash) {
            showStatus('Transacción enviada. Hash: ' + txHash, 'success');
            
            // Llenar el formulario oculto y enviarlo
            document.getElementById('transactionHash').value = txHash;
            document.getElementById('tokenTypeInput').value = tokenType;
            document.getElementById('amountInput').value = amount;
            document.getElementById('walletAddressInput').value = userAccount;
            
            // Esperar un momento y enviar el formulario
            setTimeout(() => {
                document.getElementById('cryptoForm').submit();
            }, 2000);
        }
        
    } catch (error) {
        console.error('Error enviando pago:', error);
        showStatus('Error enviando pago: ' + error.message, 'error');
    }
}

function showBitcoinPaymentInstructions(amount) {
    const btcAddress = 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh'; // Dirección Bitcoin de ejemplo
    
    const instructions = `
        <div class="bitcoin-payment-instructions">
            <h5><i class="fab fa-bitcoin me-2"></i>Pago con Bitcoin</h5>
            <p><strong>Cantidad a enviar:</strong> ${amount} BTC</p>
            <p><strong>Dirección Bitcoin:</strong></p>
            <div class="address-container">
                <code id="btcAddress">${btcAddress}</code>
                <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="copyBtcAddress()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Instrucciones:</strong><br>
                1. Abre tu wallet de Bitcoin<br>
                2. Envía exactamente ${amount} BTC a la dirección mostrada<br>
                3. Guarda el hash de la transacción<br>
                4. Pega el hash en el campo de abajo y confirma el pago
            </div>
            <div class="mt-3">
                <label for="btcTxHash" class="form-label">Hash de transacción Bitcoin:</label>
                <input type="text" class="form-control" id="btcTxHash" placeholder="Pega aquí el hash de tu transacción Bitcoin">
                <button type="button" class="btn btn-success mt-2" onclick="confirmBitcoinPayment()">
                    <i class="fas fa-check me-2"></i>Confirmar Pago Bitcoin
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('transactionStatus').innerHTML = instructions;
    document.getElementById('transactionStatus').className = 'transaction-status status-pending';
    document.getElementById('transactionStatus').style.display = 'block';
}

function copyBtcAddress() {
    const address = document.getElementById('btcAddress').textContent;
    navigator.clipboard.writeText(address).then(() => {
        showStatus('Dirección Bitcoin copiada al portapapeles', 'success');
    });
}

function confirmBitcoinPayment() {
    const txHash = document.getElementById('btcTxHash').value;
    
    if (!txHash || txHash.length < 10) {
        showStatus('Por favor, ingresa un hash de transacción válido', 'error');
        return;
    }
    
    // Llenar el formulario para Bitcoin
    document.getElementById('transactionHash').value = txHash;
    document.getElementById('tokenTypeInput').value = 'BTC';
    document.getElementById('amountInput').value = document.getElementById('paymentAmount').value;
    document.getElementById('walletAddressInput').value = 'Bitcoin Network';
    
    showStatus('Procesando pago Bitcoin...', 'pending');
    
    setTimeout(() => {
        document.getElementById('cryptoForm').submit();
    }, 1000);
}

async function sendEthPayment(amount) {
    const amountWei = web3.utils.toWei(amount, 'ether');
    
    const transaction = {
        from: userAccount,
        to: window.RECEIVING_ADDRESS,
        value: amountWei,
        gas: 21000,
        gasPrice: web3.utils.toWei('10', 'gwei') // Gas price más bajo
    };
    
    const txHash = await web3.eth.sendTransaction(transaction);
    return txHash.transactionHash;
}

async function sendTokenPayment(tokenType, amount) {
    const contractAddress = TOKEN_CONTRACTS[tokenType];
    
    if (!contractAddress || contractAddress === '0x0000000000000000000000000000000000000000') {
        throw new Error('Token no soportado en Sepolia');
    }
    
    // ABI extendido para verificar balance y decimales
    const tokenABI = [
        {
            "constant": true,
            "inputs": [{
                "name": "_owner",
                "type": "address"
            }],
            "name": "balanceOf",
            "outputs": [{
                "name": "balance",
                "type": "uint256"
            }],
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "decimals",
            "outputs": [{
                "name": "",
                "type": "uint8"
            }],
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {"name": "_to", "type": "address"},
                {"name": "_value", "type": "uint256"}
            ],
            "name": "transfer",
            "outputs": [{"name": "", "type": "bool"}],
            "type": "function"
        }
    ];
    
    const contract = new web3.eth.Contract(tokenABI, contractAddress);
    
    try {
        // Verificar decimales del contrato
        const decimals = await contract.methods.decimals().call();
        console.log(`Decimales del token: ${decimals}`);
        
        // Verificar balance
        const balance = await contract.methods.balanceOf(userAccount).call();
        const balanceFormatted = balance / Math.pow(10, decimals);
        console.log(`Balance actual: ${balanceFormatted} ${tokenType}`);
        
        // Calcular cantidad con decimales correctos
        const amountTokens = Math.floor(amount * Math.pow(10, decimals));
        console.log(`Cantidad a enviar: ${amountTokens} (${amount} ${tokenType})`);
        
        // Verificar si hay suficiente balance
        if (parseInt(balance) < amountTokens) {
            throw new Error(`Saldo insuficiente. Tienes ${balanceFormatted} ${tokenType}, necesitas ${amount} ${tokenType}`);
        }
        
        const tx = await contract.methods.transfer(window.RECEIVING_ADDRESS, amountTokens).send({
            from: userAccount,
            gas: 100000, // Aumentar gas limit
            gasPrice: web3.utils.toWei('20', 'gwei') // Aumentar gas price
        });
        
        return tx.transactionHash;
        
    } catch (error) {
        console.error('Error detallado:', error);
        throw error;
    }
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('transactionStatus');
    statusDiv.className = `transaction-status status-${type}`;
    statusDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'clock'} me-2"></i>${message}`;
    statusDiv.style.display = 'block';
}

function copyAddress() {
    const address = document.getElementById('receivingAddress').textContent;
    navigator.clipboard.writeText(address).then(() => {
        showStatus('Dirección copiada al portapapeles', 'success');
    });
}

// Inicializar precios al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    updateCryptoPrices();
    
    // Agregar event listener para el cambio de criptomoneda
    const tokenSelect = document.getElementById('tokenType');
    if (tokenSelect) {
        tokenSelect.addEventListener('change', function() {
            updatePaymentAmount();
            showStatus(`Monto actualizado para ${this.value}`, 'success');
        });
    }
    
    // Actualizar monto inicial
    updatePaymentAmount();
});
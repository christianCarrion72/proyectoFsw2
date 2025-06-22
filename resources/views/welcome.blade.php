<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BullyGuard AI - Sistema de Detección de Bullying</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hero-section {
            text-align: center;
            color: white;
            z-index: 2;
            max-width: 800px;
            padding: 2rem;
        }

        .logo {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            animation: fadeInDown 1s ease-out;
        }

        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 3rem;
            opacity: 0.8;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease-out 0.9s both;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .feature-text {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .login-buttons {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 1.2s both;
        }

        .login-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .login-btn.admin {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border-color: #ff6b6b;
        }

        .login-btn.admin:hover {
            background: linear-gradient(45deg, #ee5a24, #ff6b6b);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
        }

        .login-btn.guard {
            background: linear-gradient(45deg, #4834d4, #686de0);
            border-color: #4834d4;
        }

        .login-btn.guard:hover {
            background: linear-gradient(45deg, #686de0, #4834d4);
            box-shadow: 0 10px 25px rgba(72, 52, 212, 0.4);
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        .ai-indicator {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 1rem 1.5rem;
            color: white;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.7;
            }
            50% {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .logo {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .login-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .login-btn {
                width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="hero-section">
            <h1 class="logo">
                <i class="fas fa-shield-alt"></i> BullyGuard AI
            </h1>
            
            <p class="subtitle">
                Sistema Inteligente de Detección y Prevención de Bullying
            </p>
            
            <p class="description">
                Utilizamos tecnología de inteligencia artificial avanzada y análisis de audio en tiempo real 
                para identificar y prevenir situaciones de acoso escolar, creando un ambiente seguro para todos.
            </p>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="feature-title">IA Avanzada</h3>
                    <p class="feature-text">
                        Algoritmos de machine learning que detectan patrones de comportamiento agresivo
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h3 class="feature-title">Análisis de Audio</h3>
                    <p class="feature-text">
                        Monitoreo en tiempo real de conversaciones para identificar situaciones de riesgo
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="feature-title">Vigilancia 24/7</h3>
                    <p class="feature-text">
                        Sistema de cámaras inteligentes con detección automática de incidentes
                    </p>
                </div>
            </div>
            
            <div class="login-buttons">
                <a href="{{ route('admin.login.view') }}" class="login-btn admin">
                    <i class="fas fa-user-cog"></i>
                    Acceso Administrador
                </a>
                
                <a href="{{ route('guardia.login.view') }}" class="login-btn guard">
                    <i class="fas fa-shield-alt"></i>
                    Acceso Guardia
                </a>
            </div>
        </div>
    </div>
    
    <div class="ai-indicator">
        <i class="fas fa-robot"></i>
        IA Activa - Monitoreando
    </div>
    
    <script>
        // Animación adicional para los elementos
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto de partículas flotantes adicionales
            const container = document.querySelector('.floating-shapes');
            
            for (let i = 0; i < 5; i++) {
                setTimeout(() => {
                    const particle = document.createElement('div');
                    particle.className = 'shape';
                    particle.style.width = Math.random() * 40 + 20 + 'px';
                    particle.style.height = particle.style.width;
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.top = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 6 + 's';
                    particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                    container.appendChild(particle);
                }, i * 1000);
            }
            
            // Efecto hover para las tarjetas de características
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(255, 255, 255, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(255, 255, 255, 0.1)';
                });
            });
        });
    </script>
</body>
</html>
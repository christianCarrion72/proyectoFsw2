#!/bin/bash

# Script para iniciar la aplicación Laravel en Docker
# Uso: ./docker-start.sh [--build] [--migrate]

set -e

echo "🐳 Iniciando aplicación Laravel con Docker..."

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instala Docker primero."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instala Docker Compose primero."
    exit 1
fi

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo "📝 Creando archivo .env desde .env.docker..."
    cp .env.docker .env
    echo "⚠️  Recuerda configurar las variables de entorno en .env"
fi

# Verificar si se debe construir las imágenes
BUILD_FLAG=""
if [[ "$1" == "--build" ]] || [[ "$2" == "--build" ]]; then
    BUILD_FLAG="--build"
    echo "🔨 Construyendo imágenes Docker..."
fi

# Detener contenedores existentes
echo "🛑 Deteniendo contenedores existentes..."
docker-compose down

# Iniciar servicios
echo "🚀 Iniciando servicios..."
docker-compose up -d $BUILD_FLAG

# Esperar a que la base de datos esté lista
echo "⏳ Esperando a que la base de datos esté lista..."
sleep 15

# Generar clave de aplicación si no existe
echo "🔑 Generando clave de aplicación..."
docker-compose exec app php artisan key:generate --force

# Ejecutar migraciones si se especifica
if [[ "$1" == "--migrate" ]] || [[ "$2" == "--migrate" ]]; then
    echo "📊 Ejecutando migraciones y seeders..."
    docker-compose run --rm migrate
fi

# Optimizar aplicación
echo "⚡ Optimizando aplicación..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Crear enlace simbólico para storage
echo "🔗 Creando enlace simbólico para storage..."
docker-compose exec app php artisan storage:link

echo "✅ ¡Aplicación iniciada exitosamente!"
echo "🌐 La aplicación está disponible en: http://localhost:8080"
echo "🗄️  Base de datos MySQL en: localhost:3306"
echo "🔴 Redis en: localhost:6379"
echo ""
echo "📋 Comandos útiles:"
echo "   docker-compose logs -f app     # Ver logs de la aplicación"
echo "   docker-compose exec app bash  # Acceder al contenedor"
echo "   docker-compose down           # Detener todos los servicios"
echo "   docker-compose up -d          # Iniciar servicios en segundo plano"
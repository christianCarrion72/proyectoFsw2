# 🐳 Guía de Docker para Bullying Detection System

Esta guía te ayudará a ejecutar la aplicación Laravel usando Docker con contenedores optimizados y ligeros.

## 📋 Requisitos Previos

- Docker Desktop instalado
- Docker Compose instalado
- Al menos 2GB de RAM disponible
- Puertos 8080, 3306 y 6379 disponibles

## 🚀 Inicio Rápido

### 1. Clonar y preparar el proyecto
```bash
git clone <tu-repositorio>
cd rds-laravel
```

### 2. Configurar variables de entorno
```bash
# Copiar archivo de configuración para Docker
cp .env.docker .env

# Editar las variables según tus necesidades
nano .env
```

### 3. Iniciar la aplicación

#### Opción A: Usando el script automatizado (Linux/Mac)
```bash
# Hacer ejecutable el script
chmod +x docker-start.sh

# Iniciar con construcción y migraciones
./docker-start.sh --build --migrate
```

#### Opción B: Comandos manuales
```bash
# Construir y iniciar contenedores
docker-compose up -d --build

# Generar clave de aplicación
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose run --rm migrate

# Optimizar aplicación
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan storage:link
```

## 🏗️ Arquitectura de Contenedores

### Servicios incluidos:

| Servicio | Imagen | Puerto | Descripción |
|----------|--------|--------|--------------|
| **app** | php:8.1-fpm-alpine | 9000 | Aplicación Laravel |
| **webserver** | nginx:alpine | 8080 | Servidor web Nginx |
| **db** | mysql:8.0 | 3306 | Base de datos MySQL |
| **redis** | redis:alpine | 6379 | Cache y sesiones |
| **migrate** | - | - | Ejecuta migraciones |

### Características de optimización:

- ✅ **Imágenes Alpine**: Reducen el tamaño en ~70%
- ✅ **Multi-stage builds**: Optimización de capas
- ✅ **Cache de dependencias**: Builds más rápidos
- ✅ **.dockerignore**: Excluye archivos innecesarios
- ✅ **Usuario no-root**: Mejora la seguridad
- ✅ **Volúmenes persistentes**: Datos seguros

## 🔧 Comandos Útiles

### Gestión de contenedores
```bash
# Ver estado de contenedores
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f app

# Acceder al contenedor de la aplicación
docker-compose exec app bash

# Detener todos los servicios
docker-compose down

# Detener y eliminar volúmenes
docker-compose down -v
```

### Comandos de Laravel
```bash
# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Ejecutar seeders
docker-compose exec app php artisan db:seed

# Limpiar cache
docker-compose exec app php artisan cache:clear

# Generar nueva clave
docker-compose exec app php artisan key:generate

# Ejecutar tests
docker-compose exec app php artisan test
```

### Base de datos
```bash
# Conectar a MySQL
docker-compose exec db mysql -u root -p acoso

# Backup de base de datos
docker-compose exec db mysqldump -u root -p acoso > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u root -p acoso < backup.sql
```

## 🌐 Acceso a la Aplicación

- **Aplicación web**: http://localhost:8080
- **Base de datos**: localhost:3306
- **Redis**: localhost:6379

## 📊 Monitoreo y Logs

### Ver logs específicos
```bash
# Logs de la aplicación
docker-compose logs app

# Logs de Nginx
docker-compose logs webserver

# Logs de MySQL
docker-compose logs db

# Logs en tiempo real de todos los servicios
docker-compose logs -f
```

### Métricas de contenedores
```bash
# Uso de recursos
docker stats

# Información de imágenes
docker images

# Espacio usado por Docker
docker system df
```

## 🔒 Seguridad

### Configuraciones implementadas:
- Usuario no-root en contenedores
- Variables de entorno para secretos
- Nginx con headers de seguridad
- Restricciones de acceso a archivos sensibles

### Recomendaciones adicionales:
- Cambiar credenciales por defecto
- Usar HTTPS en producción
- Configurar firewall apropiado
- Actualizar imágenes regularmente

## 🚨 Solución de Problemas

### Problemas comunes:

#### Puerto ya en uso
```bash
# Verificar qué proceso usa el puerto
netstat -tulpn | grep :8080

# Cambiar puerto en docker-compose.yml
ports:
  - "8081:80"  # Cambiar 8080 por 8081
```

#### Permisos de archivos
```bash
# Corregir permisos
docker-compose exec app chown -R www:www /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

#### Base de datos no conecta
```bash
# Verificar que el contenedor esté corriendo
docker-compose ps db

# Reiniciar servicio de base de datos
docker-compose restart db
```

#### Limpiar todo y empezar de nuevo
```bash
# Detener y eliminar todo
docker-compose down -v --rmi all

# Limpiar sistema Docker
docker system prune -a

# Volver a construir
docker-compose up -d --build
```

## 📈 Optimización de Rendimiento

### Para desarrollo:
```yaml
# En docker-compose.yml, agregar:
volumes:
  - .:/var/www/html
  - /var/www/html/vendor
  - /var/www/html/node_modules
```

### Para producción:
- Usar `--no-dev` en composer
- Habilitar OPcache
- Configurar Redis para cache
- Usar CDN para assets estáticos

## 🤝 Contribución

Para contribuir mejoras a la configuración de Docker:

1. Fork el repositorio
2. Crea una rama para tu feature
3. Realiza tus cambios
4. Prueba la configuración
5. Envía un Pull Request

## 📞 Soporte

Si encuentras problemas:

1. Revisa esta documentación
2. Verifica los logs: `docker-compose logs`
3. Busca en issues del repositorio
4. Crea un nuevo issue con detalles del problema
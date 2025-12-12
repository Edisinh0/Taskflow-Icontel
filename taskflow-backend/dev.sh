#!/bin/bash

# Archivo: /taskflow-backend/dev.sh
# Scripts útiles para desarrollo

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

COMPOSE_DEV="docker-compose -f docker-compose.dev.yml"

case "$1" in
    start)
        echo -e "${GREEN}Iniciando entorno de desarrollo...${NC}"
        $COMPOSE_DEV up -d
        echo -e "${GREEN}✓ Entorno iniciado${NC}"
        echo -e "${BLUE}Acceso: http://localhost:8080${NC}"
        echo -e "${BLUE}Mailhog: http://localhost:8025${NC}"
        ;;
    
    stop)
        echo -e "${YELLOW}Deteniendo entorno de desarrollo...${NC}"
        $COMPOSE_DEV down
        echo -e "${GREEN}✓ Entorno detenido${NC}"
        ;;
    
    restart)
        echo -e "${YELLOW}Reiniciando entorno...${NC}"
        $COMPOSE_DEV restart
        echo -e "${GREEN}✓ Entorno reiniciado${NC}"
        ;;
    
    build)
        echo -e "${YELLOW}Reconstruyendo imágenes...${NC}"
        $COMPOSE_DEV build --no-cache
        echo -e "${GREEN}✓ Imágenes reconstruidas${NC}"
        ;;
    
    logs)
        $COMPOSE_DEV logs -f
        ;;
    
    shell)
        echo -e "${BLUE}Accediendo al contenedor de la aplicación...${NC}"
        $COMPOSE_DEV exec app sh
        ;;
    
    artisan)
        shift
        $COMPOSE_DEV exec app php artisan "$@"
        ;;
    
    composer)
        shift
        $COMPOSE_DEV exec app composer "$@"
        ;;
    
    npm)
        shift
        $COMPOSE_DEV exec app npm "$@"
        ;;
    
    fresh)
        echo -e "${YELLOW}Refrescando base de datos...${NC}"
        $COMPOSE_DEV exec app php artisan migrate:fresh --seed
        echo -e "${GREEN}✓ Base de datos refrescada${NC}"
        ;;
    
    test)
        echo -e "${BLUE}Ejecutando tests...${NC}"
        $COMPOSE_DEV exec app php artisan test
        ;;
    
    setup)
        echo -e "${GREEN}Configuración inicial del entorno de desarrollo...${NC}"
        
        # Crear .env.local si no existe
        if [ ! -f ".env.local" ]; then
            echo -e "${YELLOW}Creando .env.local...${NC}"
            cp .env.example .env.local
            echo -e "${GREEN}✓ .env.local creado${NC}"
        fi
        
        # Copiar .env.local a .env para Docker
        cp .env.local .env
        
        # Iniciar contenedores
        echo -e "${YELLOW}Iniciando contenedores...${NC}"
        $COMPOSE_DEV up -d
        
        # Esperar a que MariaDB esté listo
        echo -e "${YELLOW}Esperando a que MariaDB esté listo...${NC}"
        sleep 10
        
        # Instalar dependencias
        echo -e "${YELLOW}Instalando dependencias de Composer...${NC}"
        $COMPOSE_DEV exec app composer install
        
        echo -e "${YELLOW}Instalando dependencias de NPM...${NC}"
        $COMPOSE_DEV exec app npm install
        
        # Generar key
        echo -e "${YELLOW}Generando APP_KEY...${NC}"
        $COMPOSE_DEV exec app php artisan key:generate
        
        # Ejecutar migraciones
        echo -e "${YELLOW}Ejecutando migraciones...${NC}"
        $COMPOSE_DEV exec app php artisan migrate --seed
        
        # Compilar assets
        echo -e "${YELLOW}Compilando assets...${NC}"
        $COMPOSE_DEV exec app npm run build
        
        echo -e "\n${GREEN}========================================${NC}"
        echo -e "${GREEN}   ✓ Configuración completada${NC}"
        echo -e "${GREEN}========================================${NC}\n"
        echo -e "${BLUE}Acceso: http://localhost:8080${NC}"
        echo -e "${BLUE}Mailhog: http://localhost:8025${NC}"
        ;;
    
    *)
        echo -e "${BLUE}TaskFlow - Scripts de Desarrollo${NC}\n"
        echo "Uso: ./dev.sh [comando]"
        echo ""
        echo "Comandos disponibles:"
        echo "  setup      - Configuración inicial completa"
        echo "  start      - Iniciar entorno de desarrollo"
        echo "  stop       - Detener entorno"
        echo "  restart    - Reiniciar servicios"
        echo "  build      - Reconstruir imágenes"
        echo "  logs       - Ver logs en tiempo real"
        echo "  shell      - Acceder a shell del contenedor"
        echo "  artisan    - Ejecutar comando Artisan"
        echo "  composer   - Ejecutar comando Composer"
        echo "  npm        - Ejecutar comando NPM"
        echo "  fresh      - Refrescar base de datos"
        echo "  test       - Ejecutar tests"
        echo ""
        echo "Ejemplos:"
        echo "  ./dev.sh setup"
        echo "  ./dev.sh artisan migrate"
        echo "  ./dev.sh composer require package/name"
        echo "  ./dev.sh npm run dev"
        ;;
esac

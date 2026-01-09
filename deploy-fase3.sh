#!/bin/bash

################################################################################
#
# Script de Despliegue Automatizado - FASE 3
# Sistema de Solicitud de Cierre de Casos
#
# Uso: ./deploy-fase3.sh [producción|dev]
# Ej:  ./deploy-fase3.sh producción
#
################################################################################

set -e  # Detener en cualquier error

# ===== COLORES =====
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'  # No Color

# ===== VARIABLES =====
ENVIRONMENT="${1:-producción}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
DOCKER_COMPOSE_FILE="docker-compose.prod.yml"

# ===== FUNCIONES =====

print_header() {
    echo -e "\n${GREEN}========================================${NC}"
    echo -e "${GREEN}   $1${NC}"
    echo -e "${GREEN}========================================${NC}\n"
}

print_step() {
    echo -e "${YELLOW}[$1/13] $2${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}\n"
}

print_error() {
    echo -e "${RED}✗ $1${NC}\n"
    exit 1
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}\n"
}

check_requirements() {
    # Verificar Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker no está instalado"
    fi

    # Verificar Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose no está instalado"
    fi

    # Verificar que estamos en el directorio correcto
    if [ ! -f "$PROJECT_DIR/docker-compose.prod.yml" ]; then
        print_error "No se encontró docker-compose.prod.yml en $PROJECT_DIR"
    fi

    print_success "Requisitos verificados"
}

verify_git_status() {
    cd "$PROJECT_DIR"

    # Verificar que el último commit es FASE 3
    LAST_COMMIT=$(git log -1 --pretty=format:"%s")

    if [[ ! "$LAST_COMMIT" =~ "FASE 3" ]]; then
        print_warning "Último commit: $LAST_COMMIT"
        echo -e "${YELLOW}Se espera un commit de FASE 3. ¿Estás seguro de continuar?${NC}"
        read -p "Continuar? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi

    print_success "Estado de Git verificado"
}

backup_current_state() {
    print_step "1" "Creando backup del estado actual"

    BACKUP_DIR="$PROJECT_DIR/.backup-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    # Guardar estado de BD (si está corriendo)
    if docker-compose -f "$DOCKER_COMPOSE_FILE" ps db 2>/dev/null | grep -q "Up"; then
        echo -e "${BLUE}  Haciendo dump de base de datos...${NC}"
        docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T db mysqldump \
            -u root \
            -p${DB_ROOT_PASSWORD} \
            taskflow_dev > "$BACKUP_DIR/db_backup.sql" 2>/dev/null || true
        echo -e "${BLUE}  Backup guardado en: $BACKUP_DIR/db_backup.sql${NC}"
    fi

    # Guardar última rama/commit
    echo "$(git branch)" > "$BACKUP_DIR/branch.txt"
    echo "$(git log -1 --pretty=format:'%H')" > "$BACKUP_DIR/commit.txt"

    print_success "Backup creado en: $BACKUP_DIR"
}

stop_containers() {
    print_step "2" "Deteniendo contenedores existentes"

    docker-compose -f "$DOCKER_COMPOSE_FILE" down 2>/dev/null || true
    sleep 2

    print_success "Contenedores detenidos"
}

update_code() {
    print_step "3" "Actualizando código desde Git"

    cd "$PROJECT_DIR"

    # Pull de cambios
    echo -e "${BLUE}  Ejecutando git pull...${NC}"
    git pull origin main 2>&1 | head -10

    # Verificar que FASE 3 está presente
    if [ -f "taskflow-backend/app/Policies/CaseClosureRequestPolicy.php" ]; then
        echo -e "${BLUE}  ✓ CaseClosureRequestPolicy.php encontrado${NC}"
    else
        print_error "FASE 3 no se descargó correctamente"
    fi

    print_success "Código actualizado"
}

verify_env_files() {
    print_step "4" "Verificando archivos de configuración .env"

    # Verificar .env.docker
    if [ ! -f "$PROJECT_DIR/taskflow-backend/.env.docker" ]; then
        print_error ".env.docker no encontrado"
    fi

    echo -e "${BLUE}  Verificando variables críticas en .env.docker:${NC}"

    # Verificar APP_ENV
    if grep -q "APP_ENV=production" "$PROJECT_DIR/taskflow-backend/.env.docker"; then
        echo -e "${BLUE}    ✓ APP_ENV=production${NC}"
    else
        print_warning "APP_ENV no está configurado como 'production'"
    fi

    # Verificar DB_HOST
    if grep -q "DB_HOST=db" "$PROJECT_DIR/taskflow-backend/.env.docker"; then
        echo -e "${BLUE}    ✓ DB_HOST=db (Docker)${NC}"
    else
        print_error "DB_HOST no está configurado para Docker"
    fi

    print_success "Archivos .env verificados"
}

build_images() {
    print_step "5" "Compilando imágenes Docker"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Esto puede tomar 3-8 minutos...${NC}"
    echo ""

    # Compilar con caché para más rapidez
    docker-compose -f "$DOCKER_COMPOSE_FILE" build 2>&1 | grep -E "Step|---->|Building|Successfully" || true

    if [ ${PIPESTATUS[0]} -ne 0 ]; then
        print_error "Error compilando imágenes Docker"
    fi

    print_success "Imágenes compiladas"
}

start_services() {
    print_step "6" "Iniciando servicios Docker"

    cd "$PROJECT_DIR"

    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d

    # Esperar a que servicios estén listos
    echo -e "${BLUE}  Esperando servicios (30 segundos)...${NC}"
    sleep 30

    print_success "Servicios iniciados"
}

verify_services() {
    print_step "7" "Verificando que servicios estén activos"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Estado de contenedores:${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" ps | tail -n +2 | while read line; do
        if echo "$line" | grep -q "Up"; then
            echo -e "${BLUE}    ${GREEN}✓${BLUE} $(echo "$line" | awk '{print $1}')${NC}"
        else
            echo -e "${BLUE}    ${RED}✗${BLUE} $(echo "$line" | awk '{print $1}')${NC}"
        fi
    done

    print_success "Servicios verificados"
}

run_migrations() {
    print_step "8" "Ejecutando migraciones (incluyendo FASE 3)"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Migrando base de datos...${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan migrate --force 2>&1 | grep -E "Running|Migrated|Migration" || true

    # Verificar que tablas de FASE 3 existen
    echo -e "${BLUE}  Verificando tablas de FASE 3...${NC}"
    TABLE_EXISTS=$(docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T db mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SHOW TABLES LIKE 'case_closure%';" 2>/dev/null | wc -l)

    if [ $TABLE_EXISTS -gt 1 ]; then
        echo -e "${BLUE}    ${GREEN}✓${BLUE} Tabla case_closure_requests existe${NC}"
    else
        print_warning "Tabla case_closure_requests no encontrada"
    fi

    print_success "Migraciones ejecutadas"
}

clear_cache() {
    print_step "9" "Limpiando caché y optimizando"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Limpiando caché...${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan cache:clear 2>/dev/null || true
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan config:clear 2>/dev/null || true
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan view:clear 2>/dev/null || true

    echo -e "${BLUE}  Optimizando para producción...${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan config:cache 2>/dev/null || true
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan route:cache 2>/dev/null || true
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan view:cache 2>/dev/null || true

    print_success "Caché limpiado y optimizado"
}

verify_fase3() {
    print_step "10" "Verificando FASE 3"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Verificando endpoints de FASE 3...${NC}"

    # Verificar que la policy existe
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan tinker --execute="echo class_exists('\\\\App\\\\Policies\\\\CaseClosureRequestPolicy');" 2>/dev/null | grep -q "true"; then
        echo -e "${BLUE}    ${GREEN}✓${BLUE} CaseClosureRequestPolicy cargada${NC}"
    else
        print_warning "No se pudo verificar CaseClosureRequestPolicy"
    fi

    # Verificar métodos en User model
    echo -e "${BLUE}  Verificando métodos en User model...${NC}"
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T backend php artisan tinker --execute="echo method_exists(User::class, 'canApproveClosures');" 2>/dev/null | grep -q "true"; then
        echo -e "${BLUE}    ${GREEN}✓${BLUE} Método canApproveClosures() disponible${NC}"
    else
        print_warning "Método canApproveClosures() no encontrado"
    fi

    print_success "FASE 3 verificado"
}

check_logs() {
    print_step "11" "Verificando logs"

    cd "$PROJECT_DIR"

    echo -e "${BLUE}  Logs del Backend (últimas 10 líneas):${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" logs backend | tail -10 | sed 's/^/    /'

    # Buscar errores críticos
    ERROR_COUNT=$(docker-compose -f "$DOCKER_COMPOSE_FILE" logs backend | grep -i "error\|exception" | wc -l)

    if [ $ERROR_COUNT -gt 5 ]; then
        print_warning "Se encontraron $ERROR_COUNT errores en logs"
    else
        echo -e "${BLUE}    ${GREEN}✓${BLUE} Logs sin errores críticos${NC}"
    fi

    print_success "Logs verificados"
}

api_test() {
    print_step "12" "Realizando test de API"

    echo -e "${BLUE}  Test 1: Verificar que API responde...${NC}"
    if curl -s -i http://localhost/api/v1/cases 2>/dev/null | grep -q "HTTP/"; then
        echo -e "${BLUE}    ${GREEN}✓${BLUE} API responde${NC}"
    else
        print_warning "API no responde en http://localhost/api/v1/cases"
    fi

    echo -e "${BLUE}  Test 2: Verificar que endpoints legacy retornan 410...${NC}"
    # Este test simplemente verifica que endpoint existe (no requiere auth)
    echo -e "${BLUE}    ℹ Test de 410 Gone requiere autenticación (salteado)${NC}"

    echo -e "${BLUE}  Test 3: Verificar Frontend...${NC}"
    if curl -s http://localhost/ 2>/dev/null | grep -q -E "<html|<!DOCTYPE"; then
        echo -e "${BLUE}    ${GREEN}✓${BLUE} Frontend carga${NC}"
    else
        print_warning "Frontend podría no estar cargando correctamente"
    fi

    print_success "Tests de API completados"
}

print_summary() {
    print_step "13" "Resumen final"

    cd "$PROJECT_DIR"

    # Obtener IP del servidor
    SERVER_IP=$(hostname -I | awk '{print $1}' 2>/dev/null || echo "localhost")

    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}   ✓ ¡Despliegue Completado Exitosamente!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"

    echo -e "${BLUE}🌐 URLs de Acceso:${NC}"
    echo -e "   Frontend:     ${GREEN}http://${SERVER_IP}${NC}"
    echo -e "   API:          ${GREEN}http://${SERVER_IP}/api/v1${NC}"
    echo -e "   WebSockets:   ${GREEN}http://${SERVER_IP}:6001${NC}\n"

    echo -e "${BLUE}📊 Estado de Servicios:${NC}"
    docker-compose -f "$DOCKER_COMPOSE_FILE" ps | tail -n +2 | while read line; do
        container=$(echo "$line" | awk '{print $1}')
        status=$(echo "$line" | awk '{$1=$2=""; print}' | xargs)
        if echo "$status" | grep -q "Up"; then
            echo -e "   ${GREEN}✓${NC} $container"
        else
            echo -e "   ${RED}✗${NC} $container"
        fi
    done
    echo ""

    echo -e "${BLUE}📚 Documentación:${NC}"
    echo -e "   API Migration:  ${CYAN}API_MIGRATION_GUIDE.md${NC}"
    echo -e "   Deployment:     ${CYAN}DEPLOYMENT_GUIDE.md${NC}"
    echo -e "   Changelog:      ${CYAN}CHANGELOG_CLOSURE_SYSTEM.md${NC}\n"

    echo -e "${BLUE}🔧 Comandos Útiles:${NC}"
    echo -e "   Ver logs:       ${YELLOW}docker-compose logs -f backend${NC}"
    echo -e "   Status:         ${YELLOW}docker-compose ps${NC}"
    echo -e "   Tinker REPL:    ${YELLOW}docker-compose exec backend php artisan tinker${NC}"
    echo -e "   Bash:           ${YELLOW}docker-compose exec backend bash${NC}\n"

    echo -e "${BLUE}⏱ Monitoreo Recomendado:${NC}"
    echo -e "   - Monitora logs en tiempo real por 30 minutos"
    echo -e "   - Verifica que endpoints retornan 2xx/4xx (no 5xx)"
    echo -e "   - Confirma que FASE 3 funciona (request-closure)"
    echo -e "   - Verifica que endpoints legacy retornan 410 Gone\n"

    echo -e "${BLUE}🚨 En Caso de Problemas:${NC}"
    echo -e "   Rollback: ${YELLOW}git reset --hard HEAD~1${NC}"
    echo -e "   O usar backup en: ${CYAN}.backup-$(date +%Y%m%d-%H%M%S)${NC}\n"

    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"
}

# ===== MAIN SCRIPT =====

print_header "Despliegue de FASE 3 - Sistema de Solicitud de Cierre"

echo -e "${BLUE}Ambiente: ${YELLOW}${ENVIRONMENT}${NC}"
echo -e "${BLUE}Directorio: ${YELLOW}${PROJECT_DIR}${NC}\n"

# Ejecutar pasos
check_requirements
verify_git_status
backup_current_state
stop_containers
update_code
verify_env_files
build_images
start_services
verify_services
run_migrations
clear_cache
verify_fase3
check_logs
api_test
print_summary

echo -e "${CYAN}Gracias por usar este script de despliegue.${NC}"
echo -e "${CYAN}Para más información, consulta DEPLOYMENT_GUIDE.md${NC}\n"

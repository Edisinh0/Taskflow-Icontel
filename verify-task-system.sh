#!/bin/bash

# Script para verificar la implementación del sistema de creación de tareas SuiteCRM v4.1
# Uso: bash verify-task-system.sh

echo "=========================================="
echo "Verificación del Sistema de Creación de Tareas"
echo "=========================================="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

# Función para verificar archivo
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} Archivo existe: $1"
        ((PASS++))
    else
        echo -e "${RED}✗${NC} Archivo NO existe: $1"
        ((FAIL++))
    fi
}

# Función para verificar contenido en archivo
check_content() {
    if grep -q "$2" "$1" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} Contenido encontrado en $1: '$2'"
        ((PASS++))
    else
        echo -e "${RED}✗${NC} Contenido NO encontrado en $1: '$2'"
        ((FAIL++))
    fi
}

echo "📁 VERIFICANDO ARCHIVOS BACKEND"
echo "=================================="

check_file "taskflow-backend/app/Http/Requests/TaskRequest.php"
check_file "taskflow-backend/app/Http/Controllers/Api/TaskController.php"
check_file "taskflow-backend/app/Models/Task.php"
check_file "taskflow-backend/app/Models/CrmCase.php"
check_file "taskflow-backend/routes/api.php"

echo ""
echo "📝 VERIFICANDO CONTENIDO BACKEND"
echo "=================================="

check_content "taskflow-backend/app/Http/Requests/TaskRequest.php" "date_start.*required.*date_format"
check_content "taskflow-backend/app/Http/Requests/TaskRequest.php" "date_due.*required.*date_format"
check_content "taskflow-backend/app/Http/Requests/TaskRequest.php" "parent_type.*required.*in:Cases,Opportunities"
check_content "taskflow-backend/app/Http/Controllers/Api/TaskController.php" "public function store(TaskRequest"
check_content "taskflow-backend/app/Http/Controllers/Api/TaskController.php" "private function createTaskInSuiteCRM"
check_content "taskflow-backend/app/Http/Controllers/Api/TaskController.php" "set_entry"

echo ""
echo "📁 VERIFICANDO ARCHIVOS FRONTEND"
echo "=================================="

check_file "taskflow-frontend/src/components/TaskCreateModal.vue"
check_file "taskflow-frontend/src/stores/tasks.js"

echo ""
echo "📝 VERIFICANDO CONTENIDO FRONTEND"
echo "=================================="

check_content "taskflow-frontend/src/components/TaskCreateModal.vue" "defineProps"
check_content "taskflow-frontend/src/components/TaskCreateModal.vue" "parentId"
check_content "taskflow-frontend/src/components/TaskCreateModal.vue" "parentType"
check_content "taskflow-frontend/src/components/TaskCreateModal.vue" "date_start"
check_content "taskflow-frontend/src/components/TaskCreateModal.vue" "date_due"
check_content "taskflow-frontend/src/stores/tasks.js" "async function createTask"

echo ""
echo "📚 VERIFICANDO DOCUMENTACIÓN"
echo "=================================="

check_file "TASK_CREATE_MODAL_GUIDE.md"
check_file "TASK_CREATION_BACKEND_DOCS.md"
check_content "TASK_CREATE_MODAL_GUIDE.md" "TaskCreateModal"
check_content "TASK_CREATION_BACKEND_DOCS.md" "TaskRequest.php"

echo ""
echo "=========================================="
echo "RESULTADO FINAL"
echo "=========================================="
echo -e "${GREEN}Verificaciones pasadas: $PASS${NC}"
echo -e "${RED}Verificaciones fallidas: $FAIL${NC}"

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}✓ Sistema completamente implementado${NC}"
    exit 0
else
    echo -e "${RED}✗ Faltan implementaciones${NC}"
    exit 1
fi

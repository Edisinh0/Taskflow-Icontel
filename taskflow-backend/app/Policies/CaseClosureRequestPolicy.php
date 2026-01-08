<?php

namespace App\Policies;

use App\Models\CaseClosureRequest;
use App\Models\CrmCase;
use App\Models\User;

class CaseClosureRequestPolicy
{
    /**
     * Determinar si el usuario puede ver la lista de solicitudes de cierre
     * Solo usuarios de SAC o administradores pueden ver
     */
    public function viewAny(User $user): bool
    {
        return $user->canApproveClosures();
    }

    /**
     * Determinar si el usuario puede ver una solicitud de cierre específica
     */
    public function view(User $user, CaseClosureRequest $closureRequest): bool
    {
        // Admins siempre pueden ver
        if ($user->isAdmin()) {
            return true;
        }

        // SAC users que están asignados pueden ver
        if ($user->isSACDepartment() && $closureRequest->assigned_to_user_id === $user->id) {
            return true;
        }

        // El usuario que solicitó el cierre puede ver
        if ($closureRequest->requested_by_user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede solicitar cierre de un caso
     * Puede solicitar si: está asignado al caso OR es el creador OR es jefe de departamento
     */
    public function create(User $user, CrmCase $case): bool
    {
        // Verificar si el usuario está asignado al caso
        $isAssigned = $case->sweetcrm_assigned_user_id === $user->sweetcrm_id;

        // Verificar si el usuario creó el caso
        $isCreator = $case->created_by === $user->id;

        // Verificar si el usuario es jefe de departamento
        $isDeptHead = $user->isDepartmentHead();

        return $isAssigned || $isCreator || $isDeptHead;
    }

    /**
     * Determinar si el usuario puede aprobar una solicitud de cierre
     * Solo usuarios de SAC asignados a la solicitud pueden aprobar, o administradores
     */
    public function approve(User $user, CaseClosureRequest $closureRequest): bool
    {
        // Admins siempre pueden aprobar
        if ($user->isAdmin()) {
            return true;
        }

        // Usuarios de SAC que están asignados a la solicitud pueden aprobar
        return $user->isSACDepartment()
            && $closureRequest->assigned_to_user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede rechazar una solicitud de cierre
     * Misma lógica que aprobar
     */
    public function reject(User $user, CaseClosureRequest $closureRequest): bool
    {
        return $this->approve($user, $closureRequest);
    }

    /**
     * Determinar si el usuario puede eliminar una solicitud de cierre
     * Solo el creador o administradores pueden eliminar
     */
    public function delete(User $user, CaseClosureRequest $closureRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Solo el que solicitó puede eliminar su propia solicitud
        return $closureRequest->requested_by_user_id === $user->id
            && $closureRequest->status === 'pending';
    }
}

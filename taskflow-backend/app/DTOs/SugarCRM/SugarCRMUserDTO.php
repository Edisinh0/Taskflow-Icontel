<?php

namespace App\DTOs\SugarCRM;

/**
 * DTO para transformar datos de usuarios desde SugarCRM API v4_1
 * al formato interno de Taskflow
 */
class SugarCRMUserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $username,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $fullName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $title,
        public readonly ?string $department,
        public readonly ?string $status,
        public readonly ?string $userType,
        public readonly bool $isAdmin,
    ) {}

    /**
     * Crear DTO desde respuesta de SugarCRM v4_1 (formato name_value_list)
     */
    public static function fromSugarCRMResponse(array $sugarCRMData): self
    {
        $nvl = $sugarCRMData['name_value_list'] ?? [];

        $firstName = $nvl['first_name']['value'] ?? '';
        $lastName = $nvl['last_name']['value'] ?? '';
        $username = $nvl['user_name']['value'] ?? null;
        $fullName = $nvl['full_name']['value'] ?? trim("$firstName $lastName") ?: $username;

        return new self(
            id: $sugarCRMData['id'] ?? throw new \InvalidArgumentException('User ID is required'),
            username: $username,
            firstName: $firstName ?: null,
            lastName: $lastName ?: null,
            fullName: $fullName,
            email: $nvl['email1']['value'] ?? null,
            phone: $nvl['phone_work']['value'] ?? null,
            title: $nvl['title']['value'] ?? null,
            department: $nvl['department']['value'] ?? null,
            status: $nvl['status']['value'] ?? null,
            userType: $nvl['user_type']['value'] ?? null,
            isAdmin: ($nvl['is_admin']['value'] ?? '0') === '1',
        );
    }

    /**
     * Crear DTO desde datos de autenticación (estructura diferente)
     */
    public static function fromAuthResponse(array $authData): self
    {
        return new self(
            id: $authData['id'] ?? throw new \InvalidArgumentException('User ID is required'),
            username: $authData['username'] ?? $authData['user_name'] ?? null,
            firstName: $authData['first_name'] ?? null,
            lastName: $authData['last_name'] ?? null,
            fullName: $authData['name'] ?? $authData['full_name'] ?? null,
            email: $authData['email'] ?? null,
            phone: $authData['phone'] ?? null,
            title: $authData['title'] ?? null,
            department: $authData['department'] ?? null,
            status: $authData['status'] ?? 'Active',
            userType: $authData['user_type'] ?? null,
            isAdmin: ($authData['is_admin'] ?? false) === true || ($authData['is_admin'] ?? '0') === '1',
        );
    }

    /**
     * Convertir a array para crear/actualizar modelo User de Taskflow
     */
    public function toUserArray(?string $fallbackUsername = null): array
    {
        return [
            'name' => $this->fullName ?? $this->username ?? $fallbackUsername ?? 'Usuario SugarCRM',
            'email' => $this->email ?? $this->generateTemporaryEmail(),
            'department' => $this->department,
            'sweetcrm_id' => $this->id,
            'sweetcrm_user_type' => $this->userType ?? ($this->isAdmin ? 'administrator' : 'regular'),
            'sweetcrm_synced_at' => now(),
            'role' => $this->mapRole(),
        ];
    }

    /**
     * Mapear rol de SugarCRM a rol de Taskflow
     */
    private function mapRole(): string
    {
        if ($this->isAdmin) {
            return 'admin';
        }

        return match ($this->userType) {
            'administrator' => 'admin',
            'manager' => 'manager',
            default => 'user',
        };
    }

    /**
     * Generar email temporal único basado en ID
     */
    private function generateTemporaryEmail(): string
    {
        return "{$this->id}@sweetcrm.local";
    }

    /**
     * Obtener nombre completo o username como fallback
     */
    public function getDisplayName(?string $fallback = null): string
    {
        return $this->fullName ?? $this->username ?? $fallback ?? 'Usuario';
    }
}

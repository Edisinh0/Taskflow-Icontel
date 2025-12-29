<?php

namespace App\DTOs\SugarCRM;

/**
 * DTO para manejar sesiones de SugarCRM
 */
class SugarCRMSessionDTO
{
    public function __construct(
        public readonly string $sessionId,
        public readonly ?string $userId = null,
        public readonly ?string $username = null,
        public readonly ?\DateTimeInterface $expiresAt = null,
    ) {}

    /**
     * Crear DTO desde respuesta de login de SugarCRM v4_1
     */
    public static function fromLoginResponse(array $loginData): self
    {
        // Calcular expiración (SugarCRM v4_1 sesiones expiran en ~1 hora por defecto)
        $expiresAt = new \DateTime();
        $expiresAt->modify('+1 hour');

        return new self(
            sessionId: $loginData['id'] ?? throw new \InvalidArgumentException('Session ID is required'),
            userId: $loginData['name_value_list']['user_id']['value'] ?? null,
            username: $loginData['name_value_list']['user_name']['value'] ?? null,
            expiresAt: $expiresAt,
        );
    }

    /**
     * Verificar si la sesión está expirada
     */
    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTime();
    }

    /**
     * Obtener tiempo restante en segundos
     */
    public function getRemainingTime(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        $now = new \DateTime();
        $diff = $this->expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }
}

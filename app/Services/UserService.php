<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @return list<User> */
    public function listAll(): array
    {
        return $this->users->findAll();
    }

    public function get(int $id): ?User
    {
        return $this->users->findById($id);
    }

    /**
     * @return array{user?: User, error?: string}
     */
    public function changeRole(int $targetUserId, string $role, int $actorUserId): array
    {
        $user = $this->users->findById($targetUserId);
        if ($user === null) {
            return ['error' => 'Użytkownik nie istnieje.'];
        }

        $role = strtolower(trim($role));
        if (!in_array($role, User::ROLES, true)) {
            return ['error' => 'Nieprawidłowa rola. Dozwolone: admin, project_manager, user.'];
        }

        $roleId = $this->users->findRoleIdByName($role);
        if ($roleId === null) {
            return ['error' => 'Rola nie istnieje w systemie.'];
        }

        if ($targetUserId === $actorUserId && $role !== 'admin' && $user->isAdmin()) {
            return ['error' => 'Nie możesz odebrać sobie roli administratora.'];
        }

        $updated = $this->users->updateRole($targetUserId, $roleId);
        if ($updated === null) {
            return ['error' => 'Nie udało się zmienić roli.'];
        }

        return ['user' => $updated];
    }

    /**
     * @return array{user?: User, error?: string}
     */
    public function changeStatus(int $targetUserId, bool $isActive, int $actorUserId): array
    {
        if ($targetUserId === $actorUserId && !$isActive) {
            return ['error' => 'Nie możesz dezaktywować własnego konta.'];
        }

        $user = $this->users->findById($targetUserId);
        if ($user === null) {
            return ['error' => 'Użytkownik nie istnieje.'];
        }

        $updated = $this->users->updateStatus($targetUserId, $isActive);
        if ($updated === null) {
            return ['error' => 'Nie udało się zmienić statusu konta.'];
        }

        return ['user' => $updated];
    }
}

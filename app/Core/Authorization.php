<?php

declare(strict_types=1);

namespace App\Core;

final class Authorization
{
    public static function isAdmin(string $role): bool
    {
        return $role === 'admin';
    }

    public static function isProjectManager(string $role): bool
    {
        return $role === 'project_manager';
    }

    public static function isUser(string $role): bool
    {
        return $role === 'user';
    }

    public static function canManageProject(string $role, int $userId, int $ownerId): bool
    {
        if (self::isAdmin($role)) {
            return true;
        }

        return self::isProjectManager($role) && $ownerId === $userId;
    }

    public static function canManageTasksInProject(string $role, int $userId, int $projectOwnerId): bool
    {
        if (self::isAdmin($role)) {
            return true;
        }

        return self::isProjectManager($role) && $projectOwnerId === $userId;
    }

    public static function canEditAssignedTask(string $role, int $userId, ?int $assigneeId): bool
    {
        return self::isUser($role)
            && $assigneeId !== null
            && $assigneeId === $userId;
    }

    public static function canDeleteTask(string $role, int $userId, int $projectOwnerId): bool
    {
        return self::canManageTasksInProject($role, $userId, $projectOwnerId);
    }
}

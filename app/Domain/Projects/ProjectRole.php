<?php

namespace App\Domain\Projects;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManage(): bool
    {
        return $this !== self::Member;
    }
}

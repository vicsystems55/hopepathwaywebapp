<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    public function test_admin_has_every_permission(): void
    {
        $user = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->hasPermission('residents.manage'));
        $this->assertTrue($user->hasPermission('users.manage'));
    }

    public function test_staff_only_has_configured_permissions(): void
    {
        $user = new User(['role' => User::ROLE_STAFF]);

        $this->assertTrue($user->hasPermission('courses.view'));
        $this->assertTrue($user->hasPermission('performance.view-own'));
        $this->assertFalse($user->hasPermission('residents.manage'));
        $this->assertFalse($user->hasPermission('users.manage'));
    }
}

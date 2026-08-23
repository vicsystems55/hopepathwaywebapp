<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_staff_can_enter_a_staff_only_area(): void
    {
        $request = $this->requestFor(User::ROLE_STAFF);

        $response = (new EnsureUserHasRole())->handle(
            $request,
            fn () => response()->json(['allowed' => true]),
            User::ROLE_STAFF
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_admin_cannot_enter_a_staff_only_area(): void
    {
        $request = $this->requestFor(User::ROLE_ADMIN);

        $response = (new EnsureUserHasRole())->handle(
            $request,
            fn () => response()->json(['allowed' => true]),
            User::ROLE_STAFF
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function requestFor(string $role): Request
    {
        $request = Request::create('/staff/profile');
        $user = new User(['role' => $role]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}

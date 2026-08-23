<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProtectedPortalRoutesTest extends TestCase
{
    /**
     * @dataProvider protectedRouteProvider
     */
    public function test_sensitive_routes_require_authentication(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertUnauthorized();
    }

    public function protectedRouteProvider(): array
    {
        return [
            'users' => ['GET', '/api/users'],
            'resident records' => ['GET', '/api/residents-management'],
            'staff records' => ['GET', '/api/staff-records'],
            'course management' => ['POST', '/api/courses'],
            'staff access overview' => ['GET', '/api/admin/staff-account-links'],
            'linked staff login creation' => ['POST', '/api/admin/staff-records/1/create-login'],
            'staff account status' => ['PATCH', '/api/admin/users/1/status'],
            'password change' => ['PUT', '/api/change-password'],
        ];
    }
}

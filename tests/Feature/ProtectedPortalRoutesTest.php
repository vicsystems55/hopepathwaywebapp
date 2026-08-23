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
            'staff credential creation' => ['POST', '/api/create-staff-credentials'],
        ];
    }
}

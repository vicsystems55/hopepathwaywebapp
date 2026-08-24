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
            'staff profile details' => ['GET', '/api/staff-records/1'],
            'staff document upload' => ['POST', '/api/staff/documents'],
            'staff document download' => ['GET', '/api/staff-documents/1/download'],
            'course management' => ['POST', '/api/courses'],
            'calendar event creation' => ['POST', '/api/calendar-events'],
            'calendar event update' => ['PUT', '/api/calendar-events/1'],
            'calendar event deletion' => ['DELETE', '/api/calendar-events/1'],
            'staff access overview' => ['GET', '/api/admin/staff-account-links'],
            'linked staff login creation' => ['POST', '/api/admin/staff-records/1/create-login'],
            'staff account status' => ['PATCH', '/api/admin/users/1/status'],
            'password change' => ['PUT', '/api/change-password'],
        ];
    }
}

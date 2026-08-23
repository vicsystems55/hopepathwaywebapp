<?php

return [
    'roles' => [
        'admin' => [
            '*',
        ],

        'staff' => [
            'dashboard.view',
            'staff.profile.view',
            'staff.profile.update',
            'staff.qualifications.view-own',
            'supervision.view-own',
            'supervision.complete-own',
            'training.view-own',
            'notifications.view',
            'calendar.manage',
            'policies.view',
            'courses.view',
            'courses.take',
            'performance.view-own',
            'files.view',
        ],
    ],
];

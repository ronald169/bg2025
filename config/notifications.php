<?php

return [
    'channels' => [
        'database',
        'mail',
        // 'broadcast' pour les notifications en temps réel (optionnel)
    ],

    'defaults' => [
        'student' => ['database', 'mail'],
        'teacher' => ['database'],
        'admin' => ['database'],
    ],

    'preferences' => [
        'course_reminder' => [
            'label' => 'Course reminders',
            'default' => ['database', 'mail'],
            'description' => 'Reminders for upcoming lessons and deadlines'
        ],
        'quiz_results' => [
            'label' => 'Quiz results',
            'default' => ['database'],
            'description' => 'Notifications when quiz results are available'
        ],
        'progress_updates' => [
            'label' => 'Progress updates',
            'default' => ['database', 'mail'],
            'description' => 'Weekly/monthly progress reports'
        ],
        'teacher_messages' => [
            'label' => 'Teacher messages',
            'default' => ['database', 'mail'],
            'description' => 'Messages from your teachers'
        ],
        'system_announcements' => [
            'label' => 'System announcements',
            'default' => ['database', 'mail'],
            'description' => 'Important platform updates and news'
        ],
        'payment_reminders' => [
            'label' => 'Payment reminders',
            'default' => ['database', 'mail'],
            'description' => 'Subscription renewal reminders'
        ],
    ],
];

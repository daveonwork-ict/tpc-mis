<?php

namespace App\Traits;

trait MobileMenuTrait
{
    protected function mobileMenus(string $userType): array
    {
        $items = [
            'student' => [
                ['Subject', 'subject', 'subject.png'],
                ['Attendance', 'attendance', 'attendance.png'],
                ['Online Exam', 'online-exam', 'online-exam.png'],
                ['Student Info', 'student-info', 'student-info.png'],
                ['Events', 'events', 'events.png'],
                ['Library', 'student-library', 'library.png'],
            ],
            'guardian' => [
                ['Subject', 'subject', 'subject.png'],
                ['Attendance', 'attendance', 'attendance.png'],
                ['Student Info', 'student-info', 'student-info.png'],
                ['Events', 'events', 'events.png'],
                ['Library', 'student-library', 'library.png'],
            ],
            'teacher' => [
                ['Students', 'students', 'student-info.png'],
                ['Attendance', 'attendance', 'attendance.png'],
                ['Live Class', 'live-class', 'online-exam.png'],
                ['Online Exam', 'online-exam', 'online-exam.png'],
                ['Report', 'report', 'marksheet.png'],
            ],
        ][$userType] ?? [];

        $menus = [];
        foreach ($items as $index => [$name, $slug, $icon]) {
            $menus[] = [
                'id'   => $index + 1,
                'name' => $name,
                'slug' => $slug,
                'icon' => asset('images/student/menu-icon/'.$icon),
            ];
        }

        return ['sliders' => [], 'menus' => $menus];
    }
}

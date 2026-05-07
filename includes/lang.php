<?php
$currentLang = 'en';
$translations = require __DIR__ . '/../lang/en.php';

function t(string $key, string $default = ''): string {
    global $translations;
    return $translations[$key] ?? ($default ?: $key);
}

function tStatus(string $status): string {
    $map = [
        'pending' => 'status_pending',
        'confirmed' => 'status_confirmed',
        'cancelled' => 'status_cancelled',
        'approved' => 'status_approved',
        'suspended' => 'status_suspended',
        'active' => 'status_active',
        'inactive' => 'status_inactive',
    ];
    return t($map[$status] ?? '', ucfirst($status));
}

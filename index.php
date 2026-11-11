<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'service' => 'ontrack-api',
    'status'  => 'running',
], JSON_UNESCAPED_SLASHES);

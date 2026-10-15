<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'API legacy dinonaktifkan. Gunakan endpoint modular di /api/auth, /api/siswa, /api/verifikator, /api/keuangan, dan /api/admin.',
], JSON_UNESCAPED_UNICODE);
exit;

<?php
header("Content-Type: application/json");
require_once '../inc/config.php';

// ==================== CORS SIMPLIFICADO ====================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->query("
        SELECT 
            id, 
            title, 
            url, 
            created_at,
            (SELECT COUNT(*) FROM comments WHERE course_id = courses.id) AS total_comments
        FROM courses
        ORDER BY created_at DESC
    ");

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar cursos: ' . $e->getMessage()]);
}
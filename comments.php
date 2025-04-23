<?php
header("Content-Type: application/json");
require_once '../inc/config.php';

// CORREÇÃO CORS (PERMITIR GET)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // <-- ADICIONADO GET
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

    // ------ MÉTODO GET (FALTANDO NO CÓDIGO ORIGINAL) ------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $courseId = $_GET['course_id'] ?? null;
        
        // VALIDAÇÃO SIMPLES
        if (!$courseId || !is_numeric($courseId)) {
            throw new Exception("ID do curso inválido");
        }

        // BUSCA COMENTÁRIOS
        $stmt = $pdo->prepare("
            SELECT * FROM comments 
            WHERE course_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([(int)$courseId]);
        $comments = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $comments]); // <-- ESSE 'data' É OBRIGATÓRIO
        exit;
    }

    // ------ MÉTODO POST (CORREÇÕES) ------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // VALIDAÇÃO DIRETA
        if (empty($data['course_id']) || !is_numeric($data['course_id'])) { // <-- SINTAXE CORRIGIDA
            throw new Exception("ID do curso inválido");
        }

        if (empty(trim($data['text']))) {
            throw new Exception("Comentário vazio");
        }

        // DADOS SIMPLIFICADOS
        $courseId = (int)$data['course_id'];
        $text = trim($data['text']);
        $author = $data['author'] ?? 'Anônimo';

        // INSERE NO BANCO
        $stmt = $pdo->prepare("
            INSERT INTO comments 
            (course_id, author, text, delete_token)
            VALUES (?, ?, ?, ?)");

        $deleteToken = bin2hex(random_bytes(8));
        $stmt->execute([$courseId, $author, $text, $deleteToken]);

        echo json_encode(['success' => true, 'delete_token' => $deleteToken]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
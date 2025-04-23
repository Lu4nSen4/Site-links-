<?php
header("Content-Type: application/json");
require_once '../inc/config.php';

// ==================== CORS SIMPLIFICADO ====================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação básica
        if (empty($data['course_id']) || !is_numeric($data['course_id'])) {
            throw new Exception("ID do curso inválido");
        }

        $courseId = (int)$data['course_id'];
        $voteType = in_array($data['type'], ['up', 'down']) ? $data['type'] : 'up';

        // Atualizar voto
        $stmt = $pdo->prepare("
            INSERT INTO votes (course_id, type, ip) 
            VALUES (:course_id, :type, :ip)
            ON DUPLICATE KEY UPDATE type = VALUES(type)
        ");
        $stmt->execute([
            ':course_id' => $courseId,
            ':type' => $voteType,
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);

        // Contar votos
        $upvotes = $pdo->query("SELECT COUNT(*) FROM votes WHERE course_id = $courseId AND type = 'up'")->fetchColumn();
        $downvotes = $pdo->query("SELECT COUNT(*) FROM votes WHERE course_id = $courseId AND type = 'down'")->fetchColumn();

        echo json_encode([
            'success' => true,
            'up' => (int)$upvotes,
            'down' => (int)$downvotes
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao processar voto: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
<?php
// Arquivo: api/callback_acionamento.php

$host = 'localhost';
$db   = 'despacho_emergencia';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $viatura = $_GET['viatura'] ?? null;
    $id_ocorrencia = $_GET['id_ocorrencia'] ?? null;

    if ($viatura && $id_ocorrencia) {
        // Atualiza o status e registra o timestamp da confirmação
        $stmt = $pdo->prepare("UPDATE ocorrencias 
                               SET status_acionamento = 'confirmado_base', 
                                   data_confirmacao_base = CURRENT_TIMESTAMP 
                               WHERE id = :id");
        
        $stmt->bindParam(':id', $id_ocorrencia, PDO::PARAM_INT);
        $stmt->execute();

        echo "Sucesso: Acionamento confirmado na base para a viatura {$viatura}.";
    } else {
        http_response_code(400);
        echo "Erro: Parâmetros viatura e id_ocorrencia são obrigatórios.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro de banco de dados: " . $e->getMessage();
}
?>

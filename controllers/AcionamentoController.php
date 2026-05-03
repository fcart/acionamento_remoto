<?php
// Arquivo: controllers/AcionamentoController.php
require '../vendor/autoload.php';

use \PhpMqtt\Client\MqttClient;
use \PhpMqtt\Client\ConnectionSettings;

// 1. Configurações de Banco de Dados
$host = 'localhost';
$db   = 'despacho_emergencia';
$user = 'root';
$pass = '';

// 2. Receber Dados do formulário
$nome = $_POST['nome'] ?? '';
$endereco = $_POST['endereco'] ?? '';
$municipio = strtolower(trim($_POST['municipio'] ?? ''));
$bairro = strtolower(trim($_POST['bairro'] ?? ''));
$tipo_emergencia = $_POST['tipo_emergencia'] ?? null;
$historico = $_POST['historico'] ?? null;

// 3. Lógica de Negócio: Determinar Tópico MQTT
$base = ($bairro == 'palmital') ? 'palmital' : 'vista_alegre';
$viatura = 'ur10101'; 
$topico = "{$municipio}/{$base}/{$viatura}";

try {
    // 4. Salvar no Banco de Dados (Model embutido para completude)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO ocorrencias (nome_solicitante, endereco, bairro, municipio, tipo_emergencia, historico, status_acionamento, topico_mqtt) 
                           VALUES (:nome, :endereco, :bairro, :municipio, :tipo, :historico, 'acionado', :topico)");
    
    $stmt->execute([
        ':nome' => $nome,
        ':endereco' => $endereco,
        ':bairro' => $bairro,
        ':municipio' => $municipio,
        ':tipo' => $tipo_emergencia,
        ':historico' => $historico,
        ':topico' => $topico
    ]);

    // Obtém o ID gerado para rastrear o tempo de resposta
    $id_ocorrencia = $pdo->lastInsertId();

    // 5. Publicar no Broker MQTT
    $server   = 'seu_broker_mqtt.com';
    $port     = 8883; // Porta com TLS
    $clientId = 'dispatcher_central_' . uniqid();

    $mqtt = new MqttClient($server, $port, $clientId);
    $settings = (new ConnectionSettings())
        ->setUsername('usuario_broker')
        ->setPassword('senha_broker')
        ->setUseTls(true); // Requisito de segurança da arquitetura

    $mqtt->connect($settings, true);
    
    // Payload JSON contendo a ação e o ID da ocorrência
    $payload = json_encode([
        'acao' => 'acionar',
        'id_ocorrencia' => (int) $id_ocorrencia,
        'viatura' => $viatura
    ]);
    
    $mqtt->publish($topico, $payload, 1); // QoS 1
    $mqtt->disconnect();
    
    echo "<h3>Ocorrência #{$id_ocorrencia} registrada. Viatura {$viatura} acionada no tópico: {$topico}</h3>";
    echo "<a href='../views/despacho.html'>Voltar</a>";

} catch (PDOException $e) {
    die("Erro no Banco de Dados: " . $e->getMessage());
} catch (\Exception $e) {
    die("Erro na comunicação MQTT: " . $e->getMessage());
}
?>

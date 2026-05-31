<?php
require_once __DIR__ . '/../models/OcorrenciaModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;

class OcorrenciaController {

    public function dashboard() {
        try {
            $model = new OcorrenciaModel();
            $stmt = $model->listarRecentes();
            $erroBanco = null;
        } catch (PDOException $e) {
            $stmt = null;
            $erroBanco = $e->getMessage();
        }
        
        // Renderiza a View
        require_once __DIR__ . '/../views/dashboard_view.php';
    }

    public function cadastrarEDespachar() {
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');

        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $viatura   = isset($_POST['viatura']) ? trim($_POST['viatura']) : '';

        if (empty($descricao) || empty($viatura)) {
            echo json_encode(["sucesso" => false, "mensagem" => "Campos obrigatórios ausentes no formulário."]);
            exit;
        }

        try {
            $model = new OcorrenciaModel();
            $novo_id = $model->criar($descricao, $viatura);
        } catch (PDOException $e) {
            echo json_encode(["sucesso" => false, "mensagem" => "Erro de persistência no banco: " . $e->getMessage()]);
            exit;
        }

        $viatura_formatada = strtolower(str_replace('-', '', $viatura));
        $topic = "sp/marilia/base01/" . $viatura_formatada; 

        $server   = 'broker.emqx.io';
        $port     = 8883; 
        $clientId = 'php_client_central_' . uniqid();

        $payload  = json_encode([
            "id" => $novo_id,
            "viatura" => $viatura,
            "acao" => "LIGAR_RELE"
        ]);

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            
            $connectionSettings = (new ConnectionSettings())
                ->setUseTls(true)
                ->setTlsVerifyPeer(false) 
                ->setKeepAliveInterval(30);

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $payload, 1); 
            $mqtt->disconnect();

            echo json_encode([
                "sucesso" => true,
                "id" => $novo_id,
                "topico_disparado" => $topic
            ]);

        } catch (MqttClientException $e) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Ocorrência gravada no banco, mas falhou o disparo da rede MQTT: " . $e->getMessage()
            ]);
        }
    }

    public function callback() {
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');

        $id_ocorrencia = isset($_GET['id_ocorrencia']) ? intval($_GET['id_ocorrencia']) : 0;
        $viatura       = isset($_GET['viatura']) ? trim($_GET['viatura']) : '';

        if ($id_ocorrencia <= 0 || empty($viatura)) {
            http_response_code(400);
            echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros 'id_ocorrencia' e 'viatura' são obrigatórios."]);
            exit;
        }

        try {
            $model = new OcorrenciaModel();
            $rowCount = $model->confirmarBase($id_ocorrencia, $viatura);

            if ($rowCount > 0) {
                http_response_code(200);
                echo json_encode(["sucesso" => true, "mensagem" => "Auditoria registrada com sucesso."]);
            } else {
                http_response_code(404);
                echo json_encode(["sucesso" => false, "mensagem" => "Falha na segurança: Ocorrência não encontrada ou prefixo da viatura não corresponde."]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["sucesso" => false, "mensagem" => "Erro de servidor: " . $e->getMessage()]);
        }
    }

    public function verificarStatus() {
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $model = new OcorrenciaModel();
            $model->marcarTimeout();
            $ocorrencias = $model->listarStatusRecentes();

            echo json_encode([
                "sucesso" => true,
                "dados" => $ocorrencias
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Erro na consulta: " . $e->getMessage()
            ]);
        }
    }

    public function despacharOcorrencia() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        header('Content-Type: application/json; charset=utf-8');

        $id_ocorrencia = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $viatura       = isset($_POST['viatura']) ? trim($_POST['viatura']) : '';

        if ($id_ocorrencia <= 0 || empty($viatura)) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Erro: Parâmetros 'id' e 'viatura' são obrigatórios via POST."
            ]);
            exit;
        }

        try {
            $model = new OcorrenciaModel();
            $model->atualizarAcionamento($id_ocorrencia, $viatura);
        } catch (PDOException $e) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Erro no Banco de Dados: " . $e->getMessage()
            ]);
            exit;
        }

        $viatura_formatada = strtolower(str_replace('-', '', $viatura));
        $topic    = 'sp/marilia/base01/' . $viatura_formatada;

        $payload  = json_encode([
            "id" => $id_ocorrencia,
            "viatura" => $viatura,
            "acao" => "LIGAR_RELE"
        ]);

        try {
            $mqtt = new MqttClient($server, $port, $clientId);

            $connectionSettings = (new ConnectionSettings())
                ->setUseTls(true)
                ->setTlsVerifyPeer(false)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('sp/marilia/base01/status')
                ->setLastWillMessage('PHP Desconectado')
                ->setLastWillQualityOfService(1);

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $payload, 1);
            $mqtt->disconnect();

            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Sucesso: Ocorrência #$id_ocorrencia atualizada e enviada ao nó IoT via MQTT Seguro!"
            ]);

        } catch (MqttClientException $e) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Ocorrência salva no banco, mas erro na emissão MQTT: " . $e->getMessage()
            ]);
        }
    }
}
?>

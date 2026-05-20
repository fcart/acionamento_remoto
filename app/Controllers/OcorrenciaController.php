<?php
namespace App\Controllers;

use App\Models\Ocorrencia;
use App\Services\MqttService;

class OcorrenciaController {
    
    // Carrega a interface visual
    public function index() {
        $ocorrenciaModel = new Ocorrencia();
        $ocorrenciasIniciais = $ocorrenciaModel->listarRecentes();
        require_once __DIR__ . '/../Views/dashboard.php';
    }

    // Antigo cadastrar_e_despachar.php
    public function cadastrar() {
        header('Content-Type: application/json; charset=utf-8');
        
        $descricao = trim($_POST['descricao'] ?? '');
        $viatura   = trim($_POST['viatura'] ?? '');

        if (empty($descricao) || empty($viatura)) {
            echo json_encode(["sucesso" => false, "mensagem" => "Campos obrigatórios ausentes."]);
            return;
        }

        $modelo = new Ocorrencia();
        $novo_id = $modelo->criar($descricao, $viatura);

        $mqtt = new MqttService();
        $resultadoMqtt = $mqtt->publicarDespacho($novo_id, $viatura);

        if ($resultadoMqtt['sucesso']) {
            echo json_encode(["sucesso" => true, "id" => $novo_id, "topico_disparado" => $resultadoMqtt['topico']]);
        } else {
            echo json_encode(["sucesso" => false, "mensagem" => "Gravado no banco, mas falha MQTT: " . $resultadoMqtt['erro']]);
        }
    }

    // Antigo callback.php
    public function callback() {
        header('Content-Type: application/json; charset=utf-8');
        
        $id_ocorrencia = intval($_GET['id_ocorrencia'] ?? 0);
        $viatura       = trim($_GET['viatura'] ?? '');

        if ($id_ocorrencia <= 0 || empty($viatura)) {
            http_response_code(400);
            echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros obrigatórios ausentes."]);
            return;
        }

        $modelo = new Ocorrencia();
        $atualizado = $modelo->confirmarBase($id_ocorrencia, $viatura);

        if ($atualizado) {
            http_response_code(200);
            echo json_encode(["sucesso" => true, "mensagem" => "Auditoria registrada."]);
        } else {
            http_response_code(404);
            echo json_encode(["sucesso" => false, "mensagem" => "Falha na segurança: Combinação não encontrada."]);
        }
    }

    // Antigo verificar_status.php
    public function status() {
        header('Content-Type: application/json; charset=utf-8');
        $modelo = new Ocorrencia();
        echo json_encode(["sucesso" => true, "dados" => $modelo->listarRecentes()]);
    }
}
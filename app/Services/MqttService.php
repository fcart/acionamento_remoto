<?php
namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;

class MqttService {
    private $server = 'broker.emqx.io';
    private $port = 8883;

    public function publicarDespacho($id, $viatura, $acao = "LIGAR_RELE") {
        $clientId = 'php_client_central_' . uniqid();
        $viatura_formatada = strtolower(str_replace('-', '', $viatura));
        $topic = "sp/marilia/base01/" . $viatura_formatada;

        $payload = json_encode([
            "id" => $id,
            "viatura" => $viatura,
            "acao" => $acao
        ]);

        try {
            $mqtt = new MqttClient($this->server, $this->port, $clientId);
            
            $connectionSettings = (new ConnectionSettings())
                ->setUseTls(true)
                ->setTlsVerifyPeer(false)
                ->setKeepAliveInterval(30);

            $mqtt->connect($connectionSettings, true);
            $mqtt->publish($topic, $payload, 2);
            $mqtt->disconnect();

            return ["sucesso" => true, "topico" => $topic];
        } catch (MqttClientException $e) {
            return ["sucesso" => false, "erro" => $e->getMessage()];
        }
    }
}

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ============================================================================
// 1. CONFIGURAÇÕES DE REDE LOCAL E CREDENCIAIS
// ============================================================================
const char* ssid     = "SSID_NAME";
const char* password = "SSID_PASS";


// ============================================================================
// 2. PARAMETRIZAÇÃO DO BROKER MQTT E DIRETRIZES DE SEGURANÇA
// ============================================================================
const char* mqtt_server = "broker.emqx.io";
const int mqtt_port     = 8883; // Porta segura com criptografia TLS/SSL
const char* mqtt_user   = "seu_usuario_mqtt"; 
const char* mqtt_pass   = "sua_senha_mqtt";

// TÓPICO EXCLUSIVO DE SUBSCRIÇÃO (Configure um prefixo correspondente para cada placa)
// Exemplos: "sp/marilia/base01/ur10101" | "sp/marilia/base01/ur10103" | "sp/marilia/base01/ur10107"
const char* topic_subscribe = "sp/marilia/base01/ur10107"; 

// ============================================================================
// 3. ENDEREÇO DA API DE RETORNO (FASE 4 - AUDITORIA DO TCC)
// ============================================================================
const String api_callback_url = "http://189.32.73.110:8080/univesp/aciona/callback.php";

// ============================================================================
// 4. MAPEAMENTO FÍSICO DE HARDWARE (GPIOs DO ESP32)
// ============================================================================
const int PINO_RELE_PORTAO = 26; // Acionamento do motor do portão basculante
const int PINO_RELE_SIRENE = 27; // Acionamento do alerta sonoro/visual


WiFiClientSecure espClient;
PubSubClient mqtt(espClient);

// ============================================================================
// FASE 4: EXECUÇÃO DO CALLBACK HTTP DE RETORNO
// ============================================================================
void enviarCallbackAuditoria(int id_ocorrencia, String viatura) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Monta a URL parametrizada exigindo a combinação exata de ID e Viatura
    String url = api_callback_url + "?id_ocorrencia=" + String(id_ocorrencia) + "&viatura=" + viatura;
    
    Serial.print("Disparando Fase 4: Enviando Callback para -> ");
    Serial.println(url);
    
    http.begin(url);
    int httpResponseCode = http.GET();
    
    if (httpResponseCode == 200) {
      Serial.println(" -> Sucesso: Resposta da API recebida. Timestamp fixado no MySQL.");
    } else {
      Serial.print(" -> Falha na auditoria técnica. Código HTTP retornado: ");
      Serial.println(httpResponseCode);
    }
    http.end();
  } else {
    Serial.println("Erro: Sem conexão Wi-Fi para emitir o callback.");
  }
}

// ============================================================================
// FASE 3: INTERCEPTAÇÃO E EXECUÇÃO FÍSICA DOS RELÉS
// ============================================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  Serial.print("\nComando interceptado no tópico: ");
  Serial.println(topic);

  // Aloca memória para a desserialização do objeto JSON
  StaticJsonDocument<250> doc;
  DeserializationError error = deserializeJson(doc, payload, length);

  if (error) {
    Serial.print("Erro crítico no processamento do payload JSON: ");
    Serial.println(error.c_str());
    return;
  }

  // Extração das variáveis transmitidas pelo PHP da central
  int id_ocorrencia = doc["id"];
  String viatura    = doc["viatura"].as<String>();
  String acao       = doc["acao"].as<String>();

  Serial.println("--- Dados extraídos do Payload ---");
  Serial.print("ID Ocorrência: "); Serial.println(id_ocorrencia);
  Serial.print("Viatura Alvo:  "); Serial.println(viatura);
  Serial.print("Comando Ação:  "); Serial.println(acao);

  if (acao == "LIGAR_RELE") {
    Serial.println("Ação Validada! Executando pulso elétrico nos módulos em paralelo...");
    
    // Comutação para nível lógico alto (Ativa os contatores físicos da base)
    digitalWrite(PINO_RELE_PORTAO, HIGH);
    digitalWrite(PINO_RELE_SIRENE, HIGH);
    
    // Janela de pulso de 2 segundos para acionamento eletromecânico seguro
    delay(2000); 
    
    // Retorna para nível lógico baixo (Cessa o pulso, mantendo o comando manual livre)
    digitalWrite(PINO_RELE_PORTAO, LOW);
    digitalWrite(PINO_RELE_SIRENE, LOW);
    Serial.println("Pulso mecânico finalizado. Infraestrutura liberada.");

    // Dispara imediatamente o fluxo de retorno de auditoria
    enviarCallbackAuditoria(id_ocorrencia, viatura);
  }
}

// ============================================================================
// MITIGAÇÃO DE RISCOS: ROTINA DE AUTO-RECONEXÃO CONTÍNUA
// ============================================================================
void reconnect() {
  while (!mqtt.connected()) {
    Serial.print("Procurando conexão com o Broker MQTT TLS...");
    
    // Gera um ID de cliente único baseado em caracteres hexadecimais aleatórios
    String clientId = "ESP32_Base_" + String(random(0xffff), HEX);
    
    if (mqtt.connect(clientId.c_str(), mqtt_user, mqtt_pass)) {
      Serial.println(" Conectado com sucesso!");
      
      // Inscreve-se no canal de escuta utilizando QoS 1 para garantia de entrega
      mqtt.subscribe(topic_subscribe, 1); 
      Serial.print("Inscrito e monitorando o tópico: ");
      Serial.println(topic_subscribe);
    } else {
      Serial.print(" Falhou. Código de estado rc=");
      Serial.print(mqtt.state());
      Serial.println(". Nova tentativa automática em 5 segundos.");
      
      delay(5000);
      yield();
    }
   
  }
}

// ============================================================================
// CONFIGURAÇÃO INICIAL DO SISTEMA
// ============================================================================
void setup() {
  Serial.begin(115200);
  
  // Configuração e inicialização das portas lógicas de saída dos relés
  pinMode(PINO_RELE_PORTAO, OUTPUT);
  pinMode(PINO_RELE_SIRENE, OUTPUT);
  digitalWrite(PINO_RELE_PORTAO, LOW);
  digitalWrite(PINO_RELE_SIRENE, LOW);

  // Inicialização e conexão da interface Wi-Fi local
  Serial.print("Conectando à rede sem fios local: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    yield();
  }
  Serial.println("\nInfraestrutura Wi-Fi conectada com sucesso.");

  // Ignora a validação estrita do certificado raiz TLS para brokers públicos
  espClient.setInsecure(); 
  
  // Configura os ponteiros do servidor de mensageria
  mqtt.setServer(mqtt_server, mqtt_port);
  mqtt.setCallback(mqttCallback);
}

// ============================================================================
// LOOP PRINCIPAL DE EXECUÇÃO
// ============================================================================
void loop() {
  // Verifica se o cliente MQTT permanece ativo, se não, inicia a recuperação de rede
  if (!mqtt.connected()) {
    reconnect();
  }
  mqtt.loop();
  
  // Reseta/Alimenta o temporizador de Watchdog para atestar a estabilidade operacional do loop
}
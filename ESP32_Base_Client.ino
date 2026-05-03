// Arquivo: ESP32_Base_Client.ino
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Configurações de Rede
const char* ssid = "WIFI_DA_BASE";
const char* password = "SENHA";

// Configurações MQTT Seguras
const char* mqtt_server = "seu_broker_mqtt.com";
const int mqtt_port = 8883;
const char* mqtt_user = "usuario_broker";
const char* mqtt_pass = "senha_broker";
const char* topico_inscricao = "marilia/palmital/ur10101";

// Configuração da API Web
const String url_api_callback = "https://SEU_DOMINIO_OU_IP/api/callback_acionamento.php";

// Pinos dos Relés
const int RELE_PORTAO = 23;
const int RELE_SIRENE = 22;

WiFiClientSecure espClient;
PubSubClient client(espClient);

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Conectando a ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi conectado.");
}

void callback_confirmacao(String viatura, int id_ocorrencia) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    // Monta a URL com os parâmetros de identificação
    String url = url_api_callback + "?viatura=" + viatura + "&id_ocorrencia=" + String(id_ocorrencia);
    
    http.begin(url);
    int httpCode = http.GET();
    
    if(httpCode > 0) {
      Serial.printf("Callback enviado. Código HTTP: %d\n", httpCode);
    } else {
      Serial.printf("Erro no envio do callback: %s\n", http.errorToString(httpCode).c_str());
    }
    http.end();
  }
}

void mqtt_callback(char* topic, byte* payload, unsigned int length) {
  Serial.print("Mensagem recebida no tópico: ");
  Serial.println(topic);

  // Deserializa o JSON
  StaticJsonDocument<256> doc;
  DeserializationError error = deserializeJson(doc, payload, length);

  if (error) {
    Serial.print("Falha ao processar o JSON: ");
    Serial.println(error.c_str());
    return;
  }

  const char* acao = doc["acao"];
  int id_ocorrencia = doc["id_ocorrencia"];
  const char* viatura = doc["viatura"];

  // Processa o acionamento
  if (String(acao) == "acionar") {
      Serial.println("Acionando portão e sirene...");
      
      // Aciona os atuadores físicos da base
      digitalWrite(RELE_PORTAO, HIGH);
      digitalWrite(RELE_SIRENE, HIGH);
      delay(2000); // Mantém o relé fechado por 2 segundos
      digitalWrite(RELE_PORTAO, LOW);
      digitalWrite(RELE_SIRENE, LOW);
      
      Serial.println("Enviando confirmação de tempo para a central...");
      callback_confirmacao(String(viatura), id_ocorrencia);
  }
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Tentando conexão MQTT...");
    String clientId = "ESP32_Base_" + String(random(0xffff), HEX);
    
    if (client.connect(clientId.c_str(), mqtt_user, mqtt_pass)) {
      Serial.println("Conectado!");
      client.subscribe(topico_inscricao);
    } else {
      Serial.print("Falha, rc=");
      Serial.print(client.state());
      Serial.println(" Tentando novamente em 5 segundos.");
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  
  pinMode(RELE_PORTAO, OUTPUT);
  pinMode(RELE_SIRENE, OUTPUT);
  digitalWrite(RELE_PORTAO, LOW);
  digitalWrite(RELE_SIRENE, LOW);
  
  setup_wifi();
  
  // Ignora validação de certificado do broker (apenas para ambiente de desenvolvimento/testes)
  // Em produção, utilize espClient.setCACert(root_ca);
  espClient.setInsecure(); 
  
  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(mqtt_callback);
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop();
}

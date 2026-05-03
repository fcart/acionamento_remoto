🔐 Segurança no Broker MQTT (Mosquitto)

A implementação de segurança no broker é um dos pilares da arquitetura, garantindo que o acionamento das viaturas não possa ser interceptado ou forjado por terceiros na rede.

O software padrão de mercado utilizado é o Eclipse Mosquitto.

A seguir, um guia prático para instalação e configuração em ambiente Linux (Ubuntu/Debian) — comum em servidores e dispositivos como Raspberry Pi.

🚀 Passo 1: Instalação do Mosquitto

Atualize os pacotes e instale o broker junto com os clientes de teste:

sudo apt update
sudo apt install mosquitto mosquitto-clients -y

Ative o serviço para iniciar automaticamente:

sudo systemctl enable mosquitto
🔑 Passo 2: Autenticação (Usuário e Senha)

Crie um arquivo de senhas com usuário autenticado:

sudo mosquitto_passwd -c /etc/mosquitto/passwd usuario_broker

⚠️ Importante:

O sistema pedirá a senha duas vezes
Use -c apenas na primeira criação
Para adicionar novos usuários depois, não use -c
🔒 Passo 3: Criptografia TLS/SSL

Para testes no TCC, você pode gerar certificados próprios (self-signed) usando OpenSSL.

📁 Criar diretório dos certificados
sudo mkdir -p /etc/mosquitto/certs
cd /etc/mosquitto/certs
1️⃣ Criar Autoridade Certificadora (CA)
sudo openssl req -new -x509 -days 3650 -extensions v3_ca -keyout ca.key -out ca.crt

💡 O campo Common Name (CN) pode ser o IP do servidor ou TCC_CA

2️⃣ Gerar chave do servidor
sudo openssl genrsa -nodes -out server.key 2048
3️⃣ Criar CSR (requisição de certificado)
sudo openssl req -new -key server.key -out server.csr

⚠️ O Common Name (CN) deve ser o IP ou domínio do broker

4️⃣ Assinar certificado com a CA
sudo openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt -days 3650
5️⃣ Ajustar permissões
sudo chown -R mosquitto:mosquitto /etc/mosquitto/certs
sudo chmod 600 /etc/mosquitto/certs/*
⚙️ Passo 4: Configuração do Mosquitto

Edite o arquivo de configuração:

sudo nano /etc/mosquitto/conf.d/default.conf

Adicione:

# 🔐 Segurança
allow_anonymous false
password_file /etc/mosquitto/passwd

# 🌐 Listener seguro (MQTTS)
listener 8883

# 🔒 Certificados TLS
cafile /etc/mosquitto/certs/ca.crt
certfile /etc/mosquitto/certs/server.crt
keyfile /etc/mosquitto/certs/server.key

# 🔐 Versão TLS
tls_version tlsv1.2

Salvar:

CTRL + O → Enter
CTRL + X
🔄 Passo 5: Reiniciar e Validar

Reinicie o serviço:

sudo systemctl restart mosquitto

Verifique o status:

sudo systemctl status mosquitto
🧪 Teste de Comunicação Segura

Abra dois terminais:

📡 Terminal 1 — Subscriber (ESP32)
mosquitto_sub -h localhost -p 8883 \
-t "marilia/palmital/ur10101" \
-u "usuario_broker" \
-P "SuaSenhaAqui" \
--cafile /etc/mosquitto/certs/ca.crt -d
📤 Terminal 2 — Publisher (Central Web)
mosquitto_pub -h localhost -p 8883 \
-t "marilia/palmital/ur10101" \
-m '{"acao": "acionar", "id_ocorrencia": 42}' \
-u "usuario_broker" \
-P "SuaSenhaAqui" \
--cafile /etc/mosquitto/certs/ca.crt \
-q 1
✅ Resultado Esperado

Se tudo estiver correto:

O subscriber receberá a mensagem imediatamente
Apenas usuários autenticados conseguem acesso
Todo o tráfego estará criptografado via TLS
🛡️ Conclusão

Com essa configuração:

❌ Conexões anônimas são bloqueadas
🔐 Autenticação obrigatória
🔒 Comunicação criptografada (TLS)
🚫 Mitigação de interceptação e falsificação

Seu broker MQTT está pronto para uso seguro em produção ou validação no TCC.

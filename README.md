A implementação de segurança no broker é um dos pilares da sua arquitetura, garantindo que o acionamento das viaturas não possa ser interceptado ou forjado por terceiros na rede.

O software padrão de mercado para isso é o Eclipse Mosquitto. Abaixo, apresento um manual prático e direto para instalação e configuração em um ambiente Linux (Ubuntu/Debian), que é o padrão para servidores em nuvem ou placas como a Raspberry Pi.

Passo 1: Instalação do Mosquitto
Primeiro, atualize a lista de pacotes e instale o broker e os clientes de teste.

Bash
sudo apt update
sudo apt install mosquitto mosquitto-clients -y
Ative o serviço para que ele inicie automaticamente caso o servidor seja reiniciado:

Bash
sudo systemctl enable mosquitto
Passo 2: Criação de Usuário e Senha (Autenticação)
O Mosquitto possui um utilitário nativo para criar senhas criptografadas. Vamos criar o arquivo de senhas e adicionar o usuário do seu projeto (ex: usuario_broker).

Bash
sudo mosquitto_passwd -c /etc/mosquitto/passwd usuario_broker
Nota: O sistema pedirá para você digitar a senha duas vezes. O parâmetro -c cria um novo arquivo. Se for adicionar mais usuários no futuro, omita o -c para não sobrescrever o arquivo existente.

Passo 3: Geração de Certificados para Criptografia (TLS/SSL)
Para o ambiente de testes e validação do seu TCC, você pode gerar seus próprios certificados (Self-Signed) usando o OpenSSL.

Crie um diretório para organizar os certificados:

Bash
sudo mkdir -p /etc/mosquitto/certs
cd /etc/mosquitto/certs
1. Gerar a Autoridade Certificadora (CA):
Isso criará a chave e o certificado "raiz" do seu servidor. O sistema fará algumas perguntas (País, Estado, etc.). Preencha como preferir, o mais importante é o Common Name (pode colocar o IP do seu servidor ou "TCC_CA").

Bash
sudo openssl req -new -x509 -days 3650 -extensions v3_ca -keyout ca.key -out ca.crt
2. Gerar a Chave do Servidor:
O parâmetro -nodes garante que a chave não terá senha, permitindo que o Mosquitto inicie sozinho sem pedir senha no terminal.

Bash
sudo openssl genrsa -nodes -out server.key 2048
3. Criar a Requisição de Assinatura (CSR):
Aqui, o Common Name DEVE ser o IP fixo ou domínio do seu servidor MQTT (ex: 192.168.1.100 ou meuboker.com).

Bash
sudo openssl req -new -key server.key -out server.csr
4. Assinar o Certificado do Servidor com a CA:

Bash
sudo openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt -days 3650
5. Ajustar as Permissões:
O Mosquitto precisa ter permissão para ler esses arquivos.

Bash
sudo chown -R mosquitto:mosquitto /etc/mosquitto/certs
sudo chmod 600 /etc/mosquitto/certs/*
Passo 4: Configurando o mosquitto.conf
Agora precisamos dizer ao Mosquitto para usar tudo o que criamos. Abra o arquivo de configuração:

Bash
sudo nano /etc/mosquitto/conf.d/default.conf
Cole a configuração abaixo. Ela desabilita conexões anônimas, exige a senha e ativa a criptografia TLS na porta 8883.

Plaintext
# ==========================================
# 1. Configurações de Segurança e Senha
# ==========================================
allow_anonymous false
password_file /etc/mosquitto/passwd

# ==========================================
# 2. Configuração de Rede e Criptografia
# ==========================================
# Porta 8883 é o padrão para MQTT seguro (MQTTS)
listener 8883

# Caminhos dos certificados que geramos no Passo 3
cafile /etc/mosquitto/certs/ca.crt
certfile /etc/mosquitto/certs/server.crt
keyfile /etc/mosquitto/certs/server.key

# Especifica a versão do protocolo TLS
tls_version tlsv1.2
Salve (CTRL+O, Enter) e saia (CTRL+X).

Passo 5: Reiniciar e Testar
Reinicie o serviço para aplicar as configurações:

Bash
sudo systemctl restart mosquitto
Verifique se ele iniciou sem erros:

Bash
sudo systemctl status mosquitto
Teste de Comunicação Segura:

Abra dois terminais no seu computador/servidor para testar o fluxo completo.

Terminal 1 (O ESP32 / Subscriber):
Vamos nos inscrever no tópico de acionamento usando o certificado CA, usuário e senha.

Bash
mosquitto_sub -h localhost -p 8883 -t "marilia/palmital/ur10101" -u "usuario_broker" -P "SuaSenhaAqui" --cafile /etc/mosquitto/certs/ca.crt -d
Terminal 2 (A Central Web / Publisher):
Vamos enviar um payload JSON simulando o despacho da API.

Bash
mosquitto_pub -h localhost -p 8883 -t "marilia/palmital/ur10101" -m '{"acao": "acionar", "id_ocorrencia": 42}' -u "usuario_broker" -P "SuaSenhaAqui" --cafile /etc/mosquitto/certs/ca.crt -q 1
Se tudo estiver correto, no Terminal 1 você verá a mensagem JSON chegar imediatamente. A partir deste momento, seu broker está blindado: ninguém publica ou escuta na rede sem as credenciais e todo o tráfego de dados trafega embaralhado pela criptografia TLS.

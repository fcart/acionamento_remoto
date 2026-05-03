CREATE TABLE ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_solicitante VARCHAR(100) NOT NULL,
    endereco VARCHAR(150) NOT NULL,
    bairro VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    tipo_emergencia VARCHAR(100),
    historico TEXT,               
    status_acionamento ENUM('pendente', 'acionado', 'confirmado_base') DEFAULT 'pendente',
    topico_mqtt VARCHAR(150),
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_confirmacao_base TIMESTAMP NULL -- Nova coluna para o log de tempo do ESP32
);

<?php
require_once __DIR__ . '/../config/Database.php';

class OcorrenciaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function criar($descricao, $viatura) {
        $stmt = $this->pdo->prepare("INSERT INTO ocorrencias (descricao, viatura, status, data_registro) VALUES (:desc, :viat, 'acionado', CURRENT_TIMESTAMP(3))");
        $stmt->execute([
            ':desc' => $descricao,
            ':viat' => $viatura
        ]);
        return $this->pdo->lastInsertId();
    }

    public function confirmarBase($id_ocorrencia, $viatura) {
        $sql = "UPDATE ocorrencias 
                SET status = 'confirmado_base', 
                    data_confirmacao_base = CURRENT_TIMESTAMP(3) 
                WHERE id = :id_ocorrencia AND viatura = :viatura";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_ocorrencia' => $id_ocorrencia,
            ':viatura' => $viatura
        ]);
        return $stmt->rowCount();
    }

    public function listarRecentes() {
        return $this->pdo->query("SELECT * FROM ocorrencias ORDER BY id DESC LIMIT 10");
    }

    public function listarStatusRecentes() {
        $stmt = $this->pdo->query("SELECT id, status FROM ocorrencias ORDER BY id DESC LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarTimeout() {
        $sql_timeout = "UPDATE ocorrencias 
                        SET status = 'falha_timeout' 
                        WHERE status = 'acionado' 
                        AND TIMESTAMPDIFF(SECOND, data_registro, CURRENT_TIMESTAMP(3)) > 30";
        $this->pdo->exec($sql_timeout);
    }

    public function atualizarAcionamento($id_ocorrencia, $viatura) {
        $stmt = $this->pdo->prepare("UPDATE ocorrencias SET status = 'acionado', viatura = :viatura WHERE id = :id");
        $stmt->execute([
            ':viatura' => $viatura,
            ':id'      => $id_ocorrencia
        ]);
    }
}
?>

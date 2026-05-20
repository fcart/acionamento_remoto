<?php
namespace App\Models;

use PDO;

class Ocorrencia {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function criar($descricao, $viatura) {
        $stmt = $this->pdo->prepare("INSERT INTO ocorrencias (descricao, viatura, status, data_registro) VALUES (:desc, :viat, 'acionado', CURRENT_TIMESTAMP(3))");
        $stmt->execute([':desc' => $descricao, ':viat' => $viatura]);
        return $this->pdo->lastInsertId();
    }

    public function confirmarBase($id, $viatura) {
        $sql = "UPDATE ocorrencias SET status = 'confirmado_base', data_confirmacao_base = CURRENT_TIMESTAMP(3) WHERE id = :id AND viatura = :viatura";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':viatura' => $viatura]);
        return $stmt->rowCount() > 0;
    }

    public function listarRecentes($limite = 10) {
        $stmt = $this->pdo->prepare("SELECT * FROM ocorrencias ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
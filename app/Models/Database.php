<?php
namespace App\Models;

use PDO;
use PDOException;

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $host = "localhost";
            $user = "root";
            $pass = "sua_senha";
            $name = "univesp";

            try {
                self::$pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die(json_encode(["sucesso" => false, "mensagem" => "Erro de conexão: " . $e->getMessage()]));
            }
        }
        return self::$pdo;
    }
}
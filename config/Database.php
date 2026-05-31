<?php
class Database {
    public static function getConnection() {
        $db_host = "localhost"; 
        $db_user = "root"; 
        $db_pass = "SUA_SENHA"; 
        $db_name = "univesp";
        
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
?>

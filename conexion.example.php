<?php
/**
 * Plantilla de Configuración de Base de Datos
 *
 * Para utilizar en tu entorno local:
 * 1. Copia este archivo y renómbralo a 'conexion.php'.
 * 2. Modifica los parámetros con las credenciales de tu servidor MySQL/MariaDB.
 */

$host = 'localhost';
$db   = 'vet_clinica';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>

<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tel = trim($_POST['telefono_principal'] ?? '');

    if (empty($nombre) || empty($tel)) {
        die("Error: El nombre y el teléfono principal son obligatorios.");
    }

    try {
        $sql = "INSERT INTO clientes (nombre, cedula_doc, telefono_principal, telefono_secundario, email, direccion, notas_contacto) 
                VALUES (:nombre, :cedula, :tel, :tel_sec, :email, :dir, :notas)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':cedula'   => trim($_POST['cedula_doc']) ?: null,
            ':tel'      => $tel,
            ':tel_sec'  => trim($_POST['telefono_secundario']) ?: null,
            ':email'    => trim($_POST['email']) ?: null,
            ':dir'      => trim($_POST['direccion']) ?: null,
            ':notas'    => trim($_POST['notas_contacto']) ?: null
        ]);

        $nuevo_cliente_id = $pdo->lastInsertId();

        // Redirigir a la ficha del nuevo propietario
        header("Location: cliente_perfil.php?id=" . $nuevo_cliente_id);
        exit;
    } catch (Exception $e) {
        die("Error al registrar el propietario: " . $e->getMessage());
    }
} else {
    header("Location: clientes.php");
    exit;
}

<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $nombre = trim($_POST['nombre'] ?? '');
    $tel = trim($_POST['telefono_principal'] ?? '');

    if ($cliente_id <= 0 || empty($nombre) || empty($tel)) {
        die("Error: Datos incompletos para actualizar el propietario.");
    }

    try {
        $sql = "UPDATE clientes SET 
                    nombre = :nombre, 
                    cedula_doc = :cedula, 
                    telefono_principal = :tel, 
                    telefono_secundario = :tel_sec, 
                    email = :email, 
                    direccion = :dir, 
                    notas_contacto = :notas 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':cedula'   => trim($_POST['cedula_doc']) ?: null,
            ':tel'      => $tel,
            ':tel_sec'  => trim($_POST['telefono_secundario']) ?: null,
            ':email'    => trim($_POST['email']) ?: null,
            ':dir'      => trim($_POST['direccion']) ?: null,
            ':notas'    => trim($_POST['notas_contacto']) ?: null,
            ':id'       => $cliente_id
        ]);

        // Redirigir al perfil actualizado del propietario
        header("Location: cliente_perfil.php?id=" . $cliente_id);
        exit;
    } catch (Exception $e) {
        die("Error al actualizar los datos del propietario: " . $e->getMessage());
    }
} else {
    header("Location: clientes.php");
    exit;
}

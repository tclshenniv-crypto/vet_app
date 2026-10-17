<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];

    try {
        $sql = "INSERT INTO pacientes (cliente_id, nombre, especie, raza, sexo, peso_actual, fecha_nacimiento, alergias, padecimientos) 
                VALUES (:cliente_id, :nombre, :especie, :raza, :sexo, :peso, :fecha_nac, :alergias, :padecimientos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id'    => $cliente_id,
            ':nombre'        => trim($_POST['paciente_nombre']),
            ':especie'       => $_POST['especie'],
            ':raza'          => trim($_POST['raza']) ?: null,
            ':sexo'          => $_POST['sexo'],
            ':peso'          => !empty($_POST['peso_actual']) ? $_POST['peso_actual'] : null,
            ':fecha_nac'     => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':alergias'      => trim($_POST['alergias']) ?: null,
            ':padecimientos' => trim($_POST['padecimientos']) ?: null
        ]);

        $nueva_mascota_id = $pdo->lastInsertId();

        // Redirigir al perfil del nuevo paciente registrado
        header("Location: paciente_perfil.php?id=" . $nueva_mascota_id);
        exit;
    } catch (Exception $e) {
        die("Error al asociar la nueva mascota: " . $e->getMessage());
    }
}
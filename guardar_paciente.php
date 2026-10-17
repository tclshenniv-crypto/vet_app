<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Insertar Cliente
        $sql_c = "INSERT INTO clientes (nombre, cedula_doc, telefono_principal, email) VALUES (:nombre, :cedula, :tel, :email)";
        $stmt_c = $pdo->prepare($sql_c);
        $stmt_c->execute([
            ':nombre' => trim($_POST['cliente_nombre']),
            ':cedula' => trim($_POST['cedula_doc']) ?: null,
            ':tel'    => trim($_POST['telefono_principal']),
            ':email'  => trim($_POST['email']) ?: null
        ]);
        $cliente_id = $pdo->lastInsertId();

        // 2. Insertar Paciente
        $sql_p = "INSERT INTO pacientes (cliente_id, nombre, especie, raza, sexo, peso_actual, fecha_nacimiento, alergias, padecimientos) 
                  VALUES (:cliente_id, :nombre, :especie, :raza, :sexo, :peso, :fecha_nac, :alergias, :padecimientos)";
        $stmt_p = $pdo->prepare($sql_p);
        $stmt_p->execute([
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
        $paciente_id = $pdo->lastInsertId();

        $pdo->commit();

        header("Location: paciente_perfil.php?id=" . $paciente_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al guardar el paciente: " . $e->getMessage());
    }
}
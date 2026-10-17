<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = (int)$_POST['paciente_id'];
    $cliente_id  = (int)$_POST['cliente_id'];

    try {
        $pdo->beginTransaction();

        // 1. Actualizar Cliente
        $sql_c = "UPDATE clientes SET 
                    nombre = :nombre, 
                    cedula_doc = :cedula, 
                    telefono_principal = :tel, 
                    email = :email 
                  WHERE id = :id";
        $stmt_c = $pdo->prepare($sql_c);
        $stmt_c->execute([
            ':nombre' => trim($_POST['cliente_nombre']),
            ':cedula' => trim($_POST['cedula_doc']) ?: null,
            ':tel'    => trim($_POST['telefono_principal']),
            ':email'  => trim($_POST['email']) ?: null,
            ':id'     => $cliente_id
        ]);

        // 2. Actualizar Paciente
        $sql_p = "UPDATE pacientes SET 
                    nombre = :nombre, 
                    especie = :especie, 
                    raza = :raza, 
                    sexo = :sexo, 
                    peso_actual = :peso, 
                    fecha_nacimiento = :fecha_nac, 
                    alergias = :alergias, 
                    padecimientos = :padecimientos 
                  WHERE id = :id";
        $stmt_p = $pdo->prepare($sql_p);
        $stmt_p->execute([
            ':nombre'        => trim($_POST['paciente_nombre']),
            ':especie'       => $_POST['especie'],
            ':raza'          => trim($_POST['raza']) ?: null,
            ':sexo'          => $_POST['sexo'],
            ':peso'          => !empty($_POST['peso_actual']) ? $_POST['peso_actual'] : null,
            ':fecha_nac'     => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':alergias'      => trim($_POST['alergias']) ?: null,
            ':padecimientos' => trim($_POST['padecimientos']) ?: null,
            ':id'            => $paciente_id
        ]);

        $pdo->commit();

        header("Location: paciente_perfil.php?id=" . $paciente_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al actualizar la información: " . $e->getMessage());
    }
}
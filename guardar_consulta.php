<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = (int)$_POST['paciente_id'];
    $peso = !empty($_POST['peso_en_consulta']) ? $_POST['peso_en_consulta'] : null;

    try {
        $pdo->beginTransaction();

        // 1. Insertar la consulta
        $sql = "INSERT INTO consultas (paciente_id, motivo, diagnostico, tratamiento, peso_en_consulta) 
                VALUES (:paciente_id, :motivo, :diagnostico, :tratamiento, :peso)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id' => $paciente_id,
            ':motivo'      => trim($_POST['motivo']),
            ':diagnostico' => trim($_POST['diagnostico']),
            ':tratamiento' => trim($_POST['tratamiento']),
            ':peso'        => $peso
        ]);

        // 2. Actualizar el peso actual del paciente si se ingresó uno nuevo
        if ($peso) {
            $stmt_peso = $pdo->prepare("UPDATE pacientes SET peso_actual = :peso WHERE id = :id");
            $stmt_peso->execute([':peso' => $peso, ':id' => $paciente_id]);
        }

        // 3. Crear el recordatorio de vacuna o refuerzo si se completaron los campos
        if (!empty($_POST['recordatorio_titulo']) && !empty($_POST['recordatorio_fecha'])) {
            $sql_rec = "INSERT INTO vacunas_recordatorios (paciente_id, tipo, titulo, fecha_aplicacion, proxima_dosis) 
                        VALUES (:paciente_id, :tipo, :titulo, CURDATE(), :proxima_dosis)";
            $stmt_rec = $pdo->prepare($sql_rec);
            $stmt_rec->execute([
                ':paciente_id'    => $paciente_id,
                ':tipo'           => $_POST['recordatorio_tipo'],
                ':titulo'         => trim($_POST['recordatorio_titulo']),
                ':proxima_dosis'  => $_POST['recordatorio_fecha']
            ]);
        }

        $pdo->commit();

        header("Location: paciente_perfil.php?id=" . $paciente_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al guardar la consulta: " . $e->getMessage());
    }
}
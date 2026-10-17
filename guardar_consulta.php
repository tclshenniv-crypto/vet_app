<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = (int)($_POST['paciente_id'] ?? 0);
    $peso = !empty($_POST['peso_en_consulta']) ? (float)$_POST['peso_en_consulta'] : null;
    $temperatura = !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null;
    $frecuencia = !empty($_POST['frecuencia_cardiaca']) ? trim($_POST['frecuencia_cardiaca']) : null;
    $mucosas = !empty($_POST['mucosas']) ? trim($_POST['mucosas']) : null;
    $veterinario = !empty($_POST['veterinario']) ? trim($_POST['veterinario']) : null;
    $motivo = trim($_POST['motivo'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $tratamiento = trim($_POST['tratamiento'] ?? '');

    if ($paciente_id <= 0 || empty($motivo) || empty($diagnostico) || empty($tratamiento)) {
        die("Error: Faltan datos obligatorios para registrar la consulta médica.");
    }

    // Procesar archivo adjunto si se subió uno
    $archivo_adjunto = null;
    $archivo_nombre_original = null;
    $archivo_tipo = !empty($_POST['archivo_tipo']) ? trim($_POST['archivo_tipo']) : null;

    if (isset($_FILES['archivo_estudio']) && $_FILES['archivo_estudio']['error'] === UPLOAD_ERR_OK) {
        $nombre_original = basename($_FILES['archivo_estudio']['name']);
        $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $extensiones_permitidas)) {
            $carpeta_destino = __DIR__ . '/uploads/examenes/';
            if (!is_dir($carpeta_destino)) {
                mkdir($carpeta_destino, 0755, true);
            }

            $nombre_guardado = 'estudio_' . $paciente_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $ruta_completa = $carpeta_destino . $nombre_guardado;

            if (move_uploaded_file($_FILES['archivo_estudio']['tmp_name'], $ruta_completa)) {
                $archivo_adjunto = 'uploads/examenes/' . $nombre_guardado;
                $archivo_nombre_original = !empty($_POST['archivo_descripcion']) ? trim($_POST['archivo_descripcion']) : $nombre_original;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Insertar la consulta con todas sus constantes y adjunto
        $sql = "INSERT INTO consultas (
                    paciente_id, 
                    motivo, 
                    diagnostico, 
                    tratamiento, 
                    peso_en_consulta, 
                    temperatura, 
                    frecuencia_cardiaca, 
                    mucosas, 
                    veterinario, 
                    archivo_adjunto, 
                    archivo_nombre_original, 
                    archivo_tipo
                ) VALUES (
                    :paciente_id, 
                    :motivo, 
                    :diagnostico, 
                    :tratamiento, 
                    :peso, 
                    :temperatura, 
                    :frecuencia, 
                    :mucosas, 
                    :veterinario, 
                    :adjunto, 
                    :nombre_orig, 
                    :tipo_estudio
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id'   => $paciente_id,
            ':motivo'        => $motivo,
            ':diagnostico'   => $diagnostico,
            ':tratamiento'   => $tratamiento,
            ':peso'          => $peso,
            ':temperatura'   => $temperatura,
            ':frecuencia'    => $frecuencia,
            ':mucosas'       => $mucosas,
            ':veterinario'   => $veterinario,
            ':adjunto'       => $archivo_adjunto,
            ':nombre_orig'   => $archivo_nombre_original,
            ':tipo_estudio'  => $archivo_tipo
        ]);

        // 2. Actualizar el peso actual del paciente si se registró uno
        if ($peso) {
            $stmt_peso = $pdo->prepare("UPDATE pacientes SET peso_actual = :peso WHERE id = :id");
            $stmt_peso->execute([':peso' => $peso, ':id' => $paciente_id]);
        }

        // 3. Crear el recordatorio de seguimiento, vacuna o revisión si se completaron los campos
        if (!empty($_POST['recordatorio_titulo']) && !empty($_POST['recordatorio_fecha'])) {
            $sql_rec = "INSERT INTO vacunas_recordatorios (paciente_id, tipo, titulo, fecha_aplicacion, proxima_dosis) 
                        VALUES (:paciente_id, :tipo, :titulo, CURDATE(), :proxima_dosis)";
            $stmt_rec = $pdo->prepare($sql_rec);
            $stmt_rec->execute([
                ':paciente_id'    => $paciente_id,
                ':tipo'           => $_POST['recordatorio_tipo'] ?? 'Revisión',
                ':titulo'         => trim($_POST['recordatorio_titulo']),
                ':proxima_dosis'  => $_POST['recordatorio_fecha']
            ]);
        }

        $pdo->commit();

        header("Location: paciente_perfil.php?id=" . $paciente_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al guardar la consulta médica: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}
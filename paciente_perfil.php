<?php
require_once 'conexion.php';
include 'header.php';

// Obtener el ID del paciente desde la URL
$paciente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paciente_id <= 0) {
    echo '<div class="text-center py-12"><p class="text-slate-500 dark:text-slate-400">Paciente no válido.</p><a href="index.php" class="text-blue-600 underline mt-2 inline-block">Volver al inicio</a></div>';
    include 'footer.php';
    exit;
}

// 1. Consultar datos del paciente y su propietario
$sql_paciente = "SELECT 
                    p.*, 
                    c.nombre AS cliente_nombre, 
                    c.cedula_doc, 
                    c.telefono_principal, 
                    c.telefono_secundario, 
                    c.email, 
                    c.direccion, 
                    c.notas_contacto
                 FROM pacientes p
                 JOIN clientes c ON p.cliente_id = c.id
                 WHERE p.id = :id";
$stmt = $pdo->prepare($sql_paciente);
$stmt->execute([':id' => $paciente_id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    echo '<div class="text-center py-12"><p class="text-slate-500 dark:text-slate-400">El paciente no existe.</p><a href="index.php" class="text-blue-600 underline mt-2 inline-block">Volver al inicio</a></div>';
    include 'footer.php';
    exit;
}

// 2. Consultar historial de consultas
$sql_consultas = "SELECT * FROM consultas WHERE paciente_id = :id ORDER BY fecha DESC";
$stmt_c = $pdo->prepare($sql_consultas);
$stmt_c->execute([':id' => $paciente_id]);
$consultas = $stmt_c->fetchAll();

// 3. Consultar vacunas y recordatorios del paciente
$sql_vacunas = "SELECT * FROM vacunas_recordatorios WHERE paciente_id = :id ORDER BY proxima_dosis ASC";
$stmt_v = $pdo->prepare($sql_vacunas);
$stmt_v->execute([':id' => $paciente_id]);
$vacunas = $stmt_v->fetchAll();

// 4. Consultar otras mascotas del mismo dueño (excluyendo la actual)
$sql_otras = "SELECT id, nombre, especie FROM pacientes WHERE cliente_id = :cliente_id AND id != :paciente_id";
$stmt_o = $pdo->prepare($sql_otras);
$stmt_o->execute([':cliente_id' => $paciente['cliente_id'], ':paciente_id' => $paciente_id]);
$otras_mascotas = $stmt_o->fetchAll();

// Formatear teléfono para WhatsApp del dueño
$tel_limpio = preg_replace('/[^0-9]/', '', $paciente['telefono_principal']);
$msg_whatsapp = "Hola " . $paciente['cliente_nombre'] . ", le escribimos de la veterinaria por el control de " . $paciente['nombre'] . ".";
$link_wsp = "https://wa.me/" . $tel_limpio . "?text=" . urlencode($msg_whatsapp);

// 5. Calcular la edad del paciente automáticamente según su fecha de nacimiento
if (!function_exists('calcularEdadMascota')) {
    function calcularEdadMascota($fecha_nacimiento) {
        if (empty($fecha_nacimiento)) {
            return ['corta' => 'Sin registrar', 'completa' => 'Sin fecha de nacimiento registrada'];
        }
        try {
            $nacimiento = new DateTime($fecha_nacimiento);
            $hoy = new DateTime();
            if ($nacimiento > $hoy) {
                return ['corta' => 'Fecha futura', 'completa' => 'La fecha de nacimiento es futura'];
            }
            $diff = $hoy->diff($nacimiento);
            $anios = $diff->y;
            $meses = $diff->m;
            $dias  = $diff->d;

            if ($anios === 0) {
                if ($meses === 0) {
                    $corta = $dias . ($dias === 1 ? ' día' : ' días');
                    $completa = $corta;
                } else {
                    $corta = $meses . ($meses === 1 ? ' mes' : ' meses');
                    $completa = $meses . ($meses === 1 ? ' mes' : ' meses') . ($dias > 0 ? " y $dias " . ($dias === 1 ? 'día' : 'días') : '');
                }
            } else {
                if ($meses === 0) {
                    $corta = $anios . ($anios === 1 ? ' año' : ' años');
                    $completa = $corta;
                } else {
                    $corta = $anios . ($anios === 1 ? ' año, ' : ' años, ') . $meses . ($meses === 1 ? ' mes' : ' meses');
                    $completa = $anios . ($anios === 1 ? ' año' : ' años') . " y $meses " . ($meses === 1 ? 'mes' : 'meses') . ($dias > 0 ? " y $dias " . ($dias === 1 ? 'día' : 'días') : '');
                }
            }
            return ['corta' => $corta, 'completa' => $completa];
        } catch (Exception $e) {
            return ['corta' => 'Sin registrar', 'completa' => 'Fecha inválida'];
        }
    }
}
$edad_info = calcularEdadMascota($paciente['fecha_nacimiento']);
?>

<!-- Botón de Retorno -->
<div class="mb-6">
    <a href="index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver al listado
    </a>
</div>

<!-- Grid Principal del Perfil -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Columna Izquierda: Ficha del Paciente y Dueño -->
    <div class="space-y-6">
        
        <!-- Tarjeta de Identificación de la Mascota -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm transition-colors">
            <!-- Cabecera de la tarjeta con especie, sexo y botón de editar -->
            <div class="flex items-center justify-between mb-4">
                <?php $badge_paciente = getMascotaBadgeInfo($paciente['especie']); ?>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg <?= $badge_paciente['badge'] ?>">
                        <span class="text-sm select-none"><?= $badge_paciente['emoji'] ?></span>
                        <span><?= htmlspecialchars($paciente['especie']) ?></span>
                    </span>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        <?= htmlspecialchars($paciente['sexo']) ?>
                    </span>
                </div>

                <!-- Botón Editar Datos -->
                <a href="editar_paciente.php?id=<?= $paciente_id ?>" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 px-3 py-1.5 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar Datos
                </a>
            </div>

            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-1">
                <?= htmlspecialchars(ucfirst($paciente['nombre'])) ?>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Raza: <strong class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars($paciente['raza'] ?? 'No especificada') ?></strong>
            </p>

            <!-- Datos Físicos Clave -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <!-- Peso Actual -->
                <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3 rounded-xl">
                    <span class="block text-xs text-slate-400 font-medium">Peso Actual</span>
                    <span class="text-lg font-bold text-slate-800 dark:text-slate-100">
                        <?= $paciente['peso_actual'] ? $paciente['peso_actual'] . ' kg' : 'Sin reg.' ?>
                    </span>
                </div>

                <!-- Edad Calculada Automáticamente -->
                <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3 rounded-xl">
                    <span class="block text-xs text-slate-400 font-medium">Edad</span>
                    <span class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 block truncate" title="<?= htmlspecialchars($edad_info['completa']) ?>">
                        <?= htmlspecialchars($edad_info['corta']) ?>
                    </span>
                </div>

                <!-- Fecha de Nacimiento -->
                <div class="col-span-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 px-3.5 py-2 rounded-xl flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Nacimiento:
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">
                        <?= $paciente['fecha_nacimiento'] ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) : 'No registrada' ?>
                    </span>
                </div>
            </div>

            <!-- SEPARACIÓN 1: Alergias -->
            <div class="mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">⚠️ Alergias (Medicamentos / Otras)</h3>
                <?php if (!empty($paciente['alergias'])): ?>
                    <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl p-3.5 text-xs text-red-700 dark:text-red-300 font-medium">
                        <?= nl2br(htmlspecialchars($paciente['alergias'])) ?>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-400 dark:text-slate-500 text-center">
                        ✓ Sin alergias registradas
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEPARACIÓN 2: Padecimientos Crónicos -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">🩺 Padecimientos Crónicos</h3>
                <?php if (!empty($paciente['padecimientos'])): ?>
                    <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 rounded-xl p-3.5 text-xs text-amber-800 dark:text-amber-300 font-medium space-y-1">
                        <?php 
                        // Permitir mostrar en forma de lista si se escriben con saltos de línea o comas
                        $padecimientos_lista = preg_split('/[\r\n]+/', $paciente['padecimientos']);
                        foreach($padecimientos_lista as $padecimiento):
                            if(trim($padecimiento) !== ''):
                        ?>
                            <div class="flex items-start gap-1.5">
                                <span class="text-amber-600 dark:text-amber-400">•</span>
                                <span><?= htmlspecialchars(trim($padecimiento)) ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-400 dark:text-slate-500 text-center">
                        ✓ Sin padecimientos crónicos registrados
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tarjeta de Información del Propietario -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm transition-colors space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Propietario</h3>
                <!-- Botón para añadir otra mascota a este dueño -->
                <a href="agregar_mascota.php?cliente_id=<?= $paciente['cliente_id'] ?>" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    + Agregar Mascota
                </a>
            </div>
            
            <div>
                <p class="font-bold text-slate-900 dark:text-white text-lg mb-0.5">
                    <?= htmlspecialchars($paciente['cliente_nombre']) ?>
                </p>
                <?php if (!empty($paciente['cedula_doc'])): ?>
                    <p class="text-xs text-slate-400">DNI / Cédula: <?= htmlspecialchars($paciente['cedula_doc']) ?></p>
                <?php endif; ?>
            </div>

            <div class="space-y-2 text-sm pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span><?= htmlspecialchars($paciente['telefono_principal']) ?></span>
                </div>
                <?php if (!empty($paciente['email'])): ?>
                    <div class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate"><?= htmlspecialchars($paciente['email']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mostrar accesos rápidos a otras mascotas del mismo dueño -->
            <?php if (!empty($otras_mascotas)): ?>
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <span class="block text-xs font-semibold text-slate-400 uppercase mb-2">Otras mascotas de este dueño:</span>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($otras_mascotas as $otra): 
                            $badge_otra = getMascotaBadgeInfo($otra['especie']);
                        ?>
                            <a href="paciente_perfil.php?id=<?= $otra['id'] ?>" 
                               title="Ir al expediente clínico de <?= htmlspecialchars(ucfirst($otra['nombre'])) ?> (<?= htmlspecialchars($otra['especie']) ?>)" 
                               class="inline-flex items-center gap-1.5 <?= $badge_otra['classes'] ?> px-2.5 py-1 rounded-full text-xs font-semibold shadow-2xs border transition-all hover:scale-105 hover:shadow-xs group/otra">
                                <span class="text-sm leading-none select-none"><?= $badge_otra['emoji'] ?></span>
                                <span class="font-bold tracking-tight"><?= htmlspecialchars(ucfirst($otra['nombre'])) ?></span>
                                <span class="text-[10px] font-normal opacity-60 ml-0.5">• <?= htmlspecialchars($otra['especie']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Botón de WhatsApp Directo -->
            <a href="<?= $link_wsp ?>" target="_blank" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-xl text-sm shadow-sm transition-all pt-2">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                Contactar por WhatsApp
            </a>
        </div>

    </div>

    <!-- Columna Derecha: Historial Clínico y Vacunas -->
    <div class="lg:col-span-2 space-y-6">

        <div class="flex items-center justify-between bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm">
            <div>
                <h2 class="font-bold text-lg text-slate-900 dark:text-white">Expediente Clínico</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Historial de consultas y tratamientos aplicados.</p>
            </div>
            <a href="nueva_consulta.php?paciente_id=<?= $paciente_id ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-xl text-sm shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nueva Consulta
            </a>
        </div>

        <!-- Listado de Consultas Previas -->
        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Bitácora de Consultas</h3>
                <?php if (!empty($consultas)): ?>
                    <span class="text-xs text-slate-400 font-medium"><?= count($consultas) ?> <?= count($consultas) === 1 ? 'consulta registrada' : 'consultas registradas' ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (empty($consultas)): ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400">
                    <span class="text-3xl block mb-2">📋</span>
                    <p class="font-medium text-slate-600 dark:text-slate-300">Este paciente aún no tiene consultas registradas.</p>
                    <p class="text-xs text-slate-400 mt-1">Haz clic en "Nueva Consulta" para registrar la primera visita.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($consultas as $con): ?>
                        <details class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs transition-all overflow-hidden hover:border-slate-300 dark:hover:border-slate-700">
                            <!-- Resumen visible siempre: Fecha + Peso + Motivo/Síntomas + Botón desplegable -->
                            <summary class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden">
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 text-xs font-bold px-2.5 py-1 rounded-lg border border-blue-100 dark:border-blue-900/40">
                                            📅 <?= date('d/m/Y - H:i', strtotime($con['fecha'])) ?>
                                        </span>
                                        <?php if (!empty($con['peso_en_consulta'])): ?>
                                            <span class="text-xs text-slate-600 dark:text-slate-300 font-semibold bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200/50 dark:border-slate-700">
                                                ⚖️ <?= $con['peso_en_consulta'] ?> kg
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($con['temperatura'])): ?>
                                            <span class="text-xs text-amber-700 dark:text-amber-400 font-semibold bg-amber-50 dark:bg-amber-950/50 px-2.5 py-1 rounded-lg border border-amber-200/50 dark:border-amber-900/40">
                                                🌡️ <?= $con['temperatura'] ?> °C
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($con['mucosas'])): ?>
                                            <span class="text-xs text-slate-600 dark:text-slate-300 font-medium bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200/50 dark:border-slate-700">
                                                👁️ <?= htmlspecialchars($con['mucosas']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($con['veterinario'])): ?>
                                            <span class="text-xs text-indigo-700 dark:text-indigo-400 font-semibold bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-lg border border-indigo-200/50 dark:border-indigo-900/40">
                                                👨‍⚕️ <?= htmlspecialchars($con['veterinario']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($con['archivo_adjunto'])): ?>
                                            <span class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-lg border border-emerald-200/50 dark:border-emerald-900/40">
                                                📎 Con estudio adjunto
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Motivo y Síntomas -->
                                    <div class="pt-0.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Motivo / Síntomas</span>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug">
                                            <?= nl2br(htmlspecialchars($con['motivo'])) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Botón de interacción para expandir/colapsar -->
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 group-open:text-slate-500 bg-blue-50/80 dark:bg-blue-950/50 group-open:bg-slate-100 dark:group-open:bg-slate-800 px-3.5 py-2 rounded-xl transition-all shrink-0 border border-blue-100 dark:border-blue-900/30 group-open:border-transparent">
                                    <span class="group-open:hidden">Ver detalle clínico</span>
                                    <span class="hidden group-open:inline">Ocultar detalle</span>
                                    <svg class="w-4 h-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </summary>

                            <!-- Contenido Desplegable Clínico -->
                            <div class="px-5 pb-5 pt-3 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-slate-50/40 dark:bg-slate-950/40">
                                <!-- Diagnóstico Clínico -->
                                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/70 dark:border-slate-800 space-y-1.5 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Diagnóstico
                                    </div>
                                    <p class="text-slate-700 dark:text-slate-200 text-sm whitespace-pre-line leading-relaxed">
                                        <?= !empty($con['diagnostico']) ? nl2br(htmlspecialchars($con['diagnostico'])) : '<span class="text-slate-400 italic text-xs">Sin diagnóstico registrado</span>' ?>
                                    </p>
                                </div>

                                <!-- Tratamiento & Receta Médica -->
                                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-200/60 dark:border-emerald-900/40 space-y-1.5 shadow-2xs">
                                    <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                        Tratamiento & Receta Médica
                                    </div>
                                    <p class="text-slate-700 dark:text-slate-200 text-sm whitespace-pre-line leading-relaxed">
                                        <?= !empty($con['tratamiento']) ? nl2br(htmlspecialchars($con['tratamiento'])) : '<span class="text-slate-400 italic text-xs">Sin tratamiento especificado</span>' ?>
                                    </p>
                                </div>

                                <!-- Examen / Estudio Adjunto si existe -->
                                <?php if (!empty($con['archivo_adjunto'])): 
                                    $ext = strtolower(pathinfo($con['archivo_adjunto'], PATHINFO_EXTENSION));
                                    $es_imagen = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                ?>
                                    <div class="col-span-1 md:col-span-2 bg-indigo-50/50 dark:bg-indigo-950/30 p-4 rounded-xl border border-indigo-200/70 dark:border-indigo-900/50 space-y-3">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-xl">📎</span>
                                                <div>
                                                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 block">
                                                        <?= htmlspecialchars($con['archivo_tipo'] ?? 'Estudio / Examen Médico') ?>
                                                    </span>
                                                    <span class="text-xs text-slate-800 dark:text-slate-100 font-semibold">
                                                        <?= htmlspecialchars($con['archivo_nombre_original'] ?? 'Archivo adjunto') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <a href="<?= htmlspecialchars($con['archivo_adjunto']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-3.5 py-1.5 rounded-lg shadow-2xs transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                <span>Ver / Descargar Documento</span>
                                            </a>
                                        </div>

                                        <?php if ($es_imagen): ?>
                                            <div class="pt-2">
                                                <a href="<?= htmlspecialchars($con['archivo_adjunto']) ?>" target="_blank" class="inline-block max-w-xs rounded-xl overflow-hidden border border-indigo-200 dark:border-indigo-800 hover:opacity-90 transition-opacity">
                                                    <img src="<?= htmlspecialchars($con['archivo_adjunto']) ?>" alt="Estudio médico" class="w-full h-36 object-cover object-center bg-slate-900">
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sección de Vacunas y Desparasitaciones -->
        <div class="space-y-4 pt-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-1">Control de Vacunas y Refuerzos</h3>
            
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <?php if (empty($vacunas)): ?>
                    <p class="text-sm text-slate-400 text-center py-6">No hay vacunas o tratamientos preventivos registrados para este paciente.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-xs text-slate-400 uppercase">
                                    <th class="pb-3">Tipo / Tratamiento</th>
                                    <th class="pb-3">Aplicación</th>
                                    <th class="pb-3">Próxima Dosis</th>
                                    <th class="pb-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach ($vacunas as $vac): ?>
                                    <tr>
                                        <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">
                                            <?= htmlspecialchars($vac['titulo']) ?>
                                            <span class="block text-xs text-slate-400 font-normal"><?= htmlspecialchars($vac['tipo']) ?></span>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-300">
                                            <?= date('d/m/Y', strtotime($vac['fecha_aplicacion'])) ?>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-300 font-medium">
                                            <?= date('d/m/Y', strtotime($vac['proxima_dosis'])) ?>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold 
                                                <?= $vac['estado'] == 'Completado' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400' ?>">
                                                <?= $vac['estado'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
<?php
require_once 'conexion.php';
include 'header.php';

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cliente_id <= 0) {
    echo '<div class="text-center py-12"><p class="text-slate-500 dark:text-slate-400">Propietario no válido.</p><a href="clientes.php" class="text-blue-600 underline mt-2 inline-block">Volver a propietarios</a></div>';
    include 'footer.php';
    exit;
}

// 1. Consultar datos del cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo '<div class="text-center py-12"><p class="text-slate-500 dark:text-slate-400">El propietario no existe.</p><a href="clientes.php" class="text-blue-600 underline mt-2 inline-block">Volver a propietarios</a></div>';
    include 'footer.php';
    exit;
}

// 2. Consultar todas las mascotas del propietario
$stmt_m = $pdo->prepare("SELECT * FROM pacientes WHERE cliente_id = :id ORDER BY id ASC");
$stmt_m->execute([':id' => $cliente_id]);
$mascotas = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

// 3. Consultar total de consultas acumuladas entre todas sus mascotas
$stmt_con = $pdo->prepare("SELECT COUNT(*) FROM consultas c JOIN pacientes p ON c.paciente_id = p.id WHERE p.cliente_id = :id");
$stmt_con->execute([':id' => $cliente_id]);
$total_consultas = (int)$stmt_con->fetchColumn();

// Formatear WhatsApp
$tel_limpio = preg_replace('/[^0-9]/', '', $cliente['telefono_principal']);
$msg_whatsapp = "Hola " . $cliente['nombre'] . ", le escribimos de la clínica veterinaria.";
$link_wsp = "https://wa.me/" . $tel_limpio . "?text=" . urlencode($msg_whatsapp);

// Función para calcular edad de mascotas
if (!function_exists('calcularEdadMascota')) {
    function calcularEdadMascota($fecha_nacimiento) {
        if (empty($fecha_nacimiento)) {
            return 'Sin registrar';
        }
        try {
            $nacimiento = new DateTime($fecha_nacimiento);
            $hoy = new DateTime();
            if ($nacimiento > $hoy) return 'Fecha futura';
            $diff = $hoy->diff($nacimiento);
            $anios = $diff->y;
            $meses = $diff->m;
            $dias  = $diff->d;

            if ($anios === 0) {
                if ($meses === 0) {
                    return $dias . ($dias === 1 ? ' día' : ' días');
                }
                return $meses . ($meses === 1 ? ' mes' : ' meses');
            } else {
                if ($meses === 0) {
                    return $anios . ($anios === 1 ? ' año' : ' años');
                }
                return $anios . ($anios === 1 ? ' año, ' : ' años, ') . $meses . ($meses === 1 ? ' mes' : ' meses');
            }
        } catch (Exception $e) {
            return 'Sin registrar';
        }
    }
}
?>

<!-- Botón de Retorno -->
<div class="mb-6 flex items-center justify-between">
    <a href="clientes.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver a Propietarios
    </a>

    <div class="flex items-center gap-2">
        <a href="editar_cliente.php?id=<?= $cliente_id ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 px-3 py-2 rounded-xl transition-colors shadow-2xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Editar Propietario
        </a>
        <a href="agregar_mascota.php?cliente_id=<?= $cliente_id ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 px-3.5 py-2 rounded-xl transition-colors shadow-xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            + Nueva Mascota
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Columna Izquierda: Información del Propietario -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ficha del Propietario</span>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">
                    <?= htmlspecialchars($cliente['nombre']) ?>
                </h1>
                <?php if (!empty($cliente['cedula_doc'])): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">DNI / Cédula: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($cliente['cedula_doc']) ?></strong></p>
                <?php endif; ?>
            </div>

            <!-- Resumen Estadístico Rápido -->
            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3 rounded-xl text-center">
                    <span class="block text-xs text-slate-400 font-medium">Mascotas</span>
                    <span class="text-xl font-black text-blue-600 dark:text-blue-400"><?= count($mascotas) ?></span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3 rounded-xl text-center">
                    <span class="block text-xs text-slate-400 font-medium">Consultas Totales</span>
                    <span class="text-xl font-black text-slate-800 dark:text-slate-100"><?= $total_consultas ?></span>
                </div>
            </div>

            <!-- Canales de Contacto -->
            <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-sm">
                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Teléfono Principal</span>
                    <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200 font-semibold">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span><?= htmlspecialchars($cliente['telefono_principal']) ?></span>
                    </div>
                </div>

                <?php if (!empty($cliente['telefono_secundario'])): ?>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Teléfono Secundario</span>
                        <p class="text-slate-600 dark:text-slate-300 text-xs font-medium"><?= htmlspecialchars($cliente['telefono_secundario']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cliente['email'])): ?>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Correo Electrónico</span>
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 text-xs">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="truncate"><?= htmlspecialchars($cliente['email']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Dirección de Domicilio</span>
                    <p class="text-slate-600 dark:text-slate-300 text-xs leading-relaxed">
                        <?= !empty($cliente['direccion']) ? nl2br(htmlspecialchars($cliente['direccion'])) : '<span class="italic text-slate-400">No registrada</span>' ?>
                    </p>
                </div>

                <?php if (!empty($cliente['notas_contacto'])): ?>
                    <div class="pt-2">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Notas de Contacto</span>
                        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 p-3 rounded-xl text-xs text-amber-800 dark:text-amber-200">
                            <?= nl2br(htmlspecialchars($cliente['notas_contacto'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botón WhatsApp -->
            <a href="<?= $link_wsp ?>" target="_blank" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-xl text-sm shadow-sm transition-all">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                Contactar por WhatsApp
            </a>
        </div>
    </div>

    <!-- Columna Derecha: Mascotas del Propietario -->
    <div class="lg:col-span-2 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Mascotas Registradas</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Animales vinculados a la cuenta de este propietario.</p>
            </div>
            <a href="agregar_mascota.php?cliente_id=<?= $cliente_id ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                + Agregar otra mascota
            </a>
        </div>

        <?php if (empty($mascotas)): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400 space-y-3">
                <span class="text-4xl block">🐾</span>
                <p class="font-semibold text-slate-700 dark:text-slate-300">Este propietario aún no tiene mascotas registradas.</p>
                <p class="text-xs text-slate-400">Registra a su primera mascota para poder llevar el historial de consultas y vacunas.</p>
                <a href="agregar_mascota.php?cliente_id=<?= $cliente_id ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-xl text-xs shadow-sm transition-all mt-2">
                    + Registrar Mascota
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($mascotas as $m): 
                    $edad = calcularEdadMascota($m['fecha_nacimiento']);
                ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <?php $b_esp = getMascotaBadgeInfo($m['especie']); ?>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md <?= $b_esp['badge'] ?>">
                                        <span class="text-xs select-none"><?= $b_esp['emoji'] ?></span>
                                        <span><?= htmlspecialchars($m['especie']) ?></span>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium"><?= htmlspecialchars($m['sexo']) ?></span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars(ucfirst($m['nombre'])) ?>
                                </h3>
                                <p class="text-xs text-slate-400">Raza: <?= htmlspecialchars($m['raza'] ?? 'No especificada') ?></p>
                            </div>

                            <a href="paciente_perfil.php?id=<?= $m['id'] ?>" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline bg-blue-50 dark:bg-blue-950/40 p-2 rounded-xl transition-colors" title="Ver Expediente">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>

                        <!-- Métricas clave -->
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="block text-slate-400 text-[10px] uppercase font-bold">Peso</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-sm">
                                    <?= $m['peso_actual'] ? $m['peso_actual'] . ' kg' : 'Sin reg.' ?>
                                </span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="block text-slate-400 text-[10px] uppercase font-bold">Edad</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100 text-sm truncate block" title="Calculada automáticamente">
                                    <?= htmlspecialchars($edad) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Alertas si existen -->
                        <?php if (!empty($m['alergias']) || !empty($m['padecimientos'])): ?>
                            <div class="space-y-1 text-[11px] pt-1">
                                <?php if (!empty($m['alergias'])): ?>
                                    <div class="text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 px-2.5 py-1 rounded-lg border border-red-200/50 dark:border-red-900/40 truncate">
                                        ⚠️ Alergia: <?= htmlspecialchars($m['alergias']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($m['padecimientos'])): ?>
                                    <div class="text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 rounded-lg border border-amber-200/50 dark:border-amber-900/40 truncate">
                                        🩺 Condición: <?= htmlspecialchars($m['padecimientos']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Acciones Rápidas -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <a href="paciente_perfil.php?id=<?= $m['id'] ?>" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-xl text-xs transition-colors shadow-2xs">
                                Ver Expediente
                            </a>
                            <a href="nueva_consulta.php?paciente_id=<?= $m['id'] ?>" class="text-center bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium px-3 py-2 rounded-xl text-xs transition-colors">
                                + Consulta
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>

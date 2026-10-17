<?php
require_once 'conexion.php';
include 'header.php';

// Contar recordatorios pendientes próximos para la burbuja de la campana
$stmt_notif = $pdo->query("SELECT COUNT(*) FROM vacunas_recordatorios WHERE estado = 'Pendiente' AND proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$num_recordatorios = $stmt_notif->fetchColumn();

// Manejar la búsqueda si el usuario ingresó un término
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

if ($busqueda !== '') {
    $sql = "SELECT 
                p.id AS paciente_id,
                p.nombre AS paciente_nombre,
                p.especie,
                p.raza,
                p.peso_actual,
                p.alergias,
                p.padecimientos,
                c.id AS cliente_id,
                c.nombre AS cliente_nombre,
                c.telefono_principal
            FROM pacientes p
            JOIN clientes c ON p.cliente_id = c.id
            WHERE p.nombre LIKE :busq OR c.nombre LIKE :busq OR c.telefono_principal LIKE :busq
            ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':busq' => "%$busqueda%"]);
} else {
    $sql = "SELECT 
                p.id AS paciente_id,
                p.nombre AS paciente_nombre,
                p.especie,
                p.raza,
                p.peso_actual,
                p.alergias,
                p.padecimientos,
                c.id AS cliente_id,
                c.nombre AS cliente_nombre,
                c.telefono_principal
            FROM pacientes p
            JOIN clientes c ON p.cliente_id = c.id
            ORDER BY p.id DESC
            LIMIT 20";
    $stmt = $pdo->query($sql);
}
$pacientes = $stmt->fetchAll();
?>

        <!-- Cabecera de la Sección y Buscador -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Expedientes de Pacientes</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Busca, consulta y gestiona las historias clínicas de las mascotas de forma rápida.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <!-- Botón Nuevo Paciente -->
                <a href="nuevo_paciente.php" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-xl text-sm shadow-sm transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nuevo Paciente
                </a>

                <!-- Formulario de Búsqueda -->
                <form action="index.php" method="GET" class="flex gap-2 w-full sm:w-80">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar mascota, dueño o tel..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl pl-9 pr-4 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <button type="submit" class="bg-slate-900 dark:bg-blue-600 hover:bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-all">
                        Buscar
                    </button>
                </form>
            </div>
        </div>

        <!-- Listado de Pacientes -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Mascota</th>
                            <th class="py-3.5 px-6">Especie / Raza</th>
                            <th class="py-3.5 px-6">Propietario</th>
                            <th class="py-3.5 px-6">Peso Actual</th>
                            <th class="py-3.5 px-6">Alergias</th>
                            <th class="py-3.5 px-6">Padecimientos Crónicos</th>
                            <th class="py-3.5 px-6 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <?php if (empty($pacientes)): ?>
                            <tr>
                                <td colspan="7" class="py-14 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-3xl">🐾</span>
                                        <p class="font-medium text-slate-600 dark:text-slate-400">No se encontraron pacientes registrados<?= $busqueda !== '' ? ' para "' . htmlspecialchars($busqueda) . '"' : '' ?>.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pacientes as $paciente): 
                                $badge_esp = getMascotaBadgeInfo($paciente['especie']);
                            ?>
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                                    <td class="py-4 px-6 font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                        <a href="paciente_perfil.php?id=<?= $paciente['paciente_id'] ?>" class="hover:underline">
                                            <?= htmlspecialchars(ucfirst($paciente['paciente_nombre'])) ?>
                                        </a>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $badge_esp['badge'] ?>">
                                                <span class="text-xs select-none"><?= $badge_esp['emoji'] ?></span>
                                                <span><?= htmlspecialchars($paciente['especie']) ?></span>
                                            </span>
                                            <span class="text-xs text-slate-400 dark:text-slate-400 font-medium">
                                                <?= htmlspecialchars(ucfirst($paciente['raza'] ?? 'No especificada')) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200 block"><?= htmlspecialchars($paciente['cliente_nombre']) ?></span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($paciente['telefono_principal']) ?></span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 dark:text-slate-300 font-medium">
                                        <?= $paciente['peso_actual'] ? $paciente['peso_actual'] . ' kg' : '<span class="text-slate-400 text-xs font-normal">Sin registrar</span>' ?>
                                    </td>
                                    <!-- Columna Alergias -->
                                    <td class="py-4 px-6">
                                        <?php if (!empty($paciente['alergias'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-950/50 text-red-700 dark:text-red-400 border border-red-100 dark:border-red-900/50" title="<?= htmlspecialchars($paciente['alergias']) ?>">
                                                ⚠️ <?= htmlspecialchars($paciente['alergias']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-500 text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full">Ninguna</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Columna Padecimientos Crónicos -->
                                    <td class="py-4 px-6">
                                        <?php if (!empty($paciente['padecimientos'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50" title="<?= htmlspecialchars($paciente['padecimientos']) ?>">
                                                🩺 <?= htmlspecialchars($paciente['padecimientos']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-500 text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full">Ninguno</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="paciente_perfil.php?id=<?= $paciente['paciente_id'] ?>" class="inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white font-medium px-4 py-2 rounded-xl text-xs shadow-xs transition-all">
                                            Ver Historial
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php include 'footer.php'; ?>
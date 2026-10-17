<?php
require_once 'conexion.php';
include 'header.php';

// Manejar la búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

if ($busqueda !== '') {
    $sql = "SELECT 
                c.*,
                COUNT(p.id) AS total_mascotas,
                GROUP_CONCAT(CONCAT(p.id, ':', p.nombre, ':', p.especie) ORDER BY p.id ASC SEPARATOR '||') AS mascotas_data
            FROM clientes c
            LEFT JOIN pacientes p ON c.id = p.cliente_id
            WHERE c.nombre LIKE :busq 
               OR c.cedula_doc LIKE :busq 
               OR c.telefono_principal LIKE :busq 
               OR c.email LIKE :busq
               OR c.direccion LIKE :busq
            GROUP BY c.id
            ORDER BY c.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':busq' => "%$busqueda%"]);
} else {
    $sql = "SELECT 
                c.*,
                COUNT(p.id) AS total_mascotas,
                GROUP_CONCAT(CONCAT(p.id, ':', p.nombre, ':', p.especie) ORDER BY p.id ASC SEPARATOR '||') AS mascotas_data
            FROM clientes c
            LEFT JOIN pacientes p ON c.id = p.cliente_id
            GROUP BY c.id
            ORDER BY c.id DESC";
    $stmt = $pdo->query($sql);
}
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">

    <!-- Cabecera de la Página y Barra de Acciones -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Directorio de Propietarios</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Consulta, gestiona y contacta a los dueños y visualiza sus mascotas asociadas.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <!-- Botón Crear Nuevo Propietario -->
            <a href="nuevo_cliente.php" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-xl text-sm shadow-sm transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Nuevo Propietario
            </a>

            <!-- Formulario de Búsqueda -->
            <form action="clientes.php" method="GET" class="flex gap-2 w-full sm:w-80">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por dueño, cédula, tel..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl pl-9 pr-4 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <button type="submit" class="bg-slate-900 dark:bg-blue-600 hover:bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-all">
                    Buscar
                </button>
            </form>
        </div>
    </div>

    <!-- Listado de Propietarios -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Propietario / Cédula</th>
                        <th class="py-3.5 px-6">Contacto Directo</th>
                        <th class="py-3.5 px-6">Dirección</th>
                        <th class="py-3.5 px-6">Mascotas Asociadas</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="5" class="py-14 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-3xl">👥</span>
                                    <p class="font-medium text-slate-600 dark:text-slate-400">No se encontraron propietarios registrados<?= $busqueda !== '' ? ' para "' . htmlspecialchars($busqueda) . '"' : '' ?>.</p>
                                    <a href="nuevo_cliente.php" class="text-blue-600 dark:text-blue-400 text-xs underline font-semibold mt-1">Registrar el primer propietario</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cli): 
                            // Preparar link de WhatsApp
                            $tel_limpio = preg_replace('/[^0-9]/', '', $cli['telefono_principal']);
                            $wsp_url = "https://wa.me/" . $tel_limpio . "?text=" . urlencode("Hola " . $cli['nombre'] . ", le escribimos de la clínica veterinaria.");
                        ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- Propietario -->
                                <td class="py-4 px-6">
                                    <a href="cliente_perfil.php?id=<?= $cli['id'] ?>" class="font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors block">
                                        <?= htmlspecialchars($cli['nombre']) ?>
                                    </a>
                                    <?php if (!empty($cli['cedula_doc'])): ?>
                                        <span class="text-xs text-slate-400 block mt-0.5">DNI/Doc: <?= htmlspecialchars($cli['cedula_doc']) ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400/70 block mt-0.5 italic">Sin cédula</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Contacto -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($cli['telefono_principal']) ?></span>
                                        <a href="<?= $wsp_url ?>" target="_blank" title="Contactar por WhatsApp" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        </a>
                                    </div>
                                    <?php if (!empty($cli['email'])): ?>
                                        <span class="text-xs text-slate-400 block mt-0.5 truncate max-w-[200px]"><?= htmlspecialchars($cli['email']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Dirección -->
                                <td class="py-4 px-6 text-slate-600 dark:text-slate-300 max-w-[220px]">
                                    <?php if (!empty($cli['direccion'])): ?>
                                        <span class="text-xs text-slate-600 dark:text-slate-300 block truncate" title="<?= htmlspecialchars($cli['direccion']) ?>">
                                            <?= htmlspecialchars($cli['direccion']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No registrada</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Mascotas Asociadas -->
                                <td class="py-4 px-6">
                                    <?php if ($cli['total_mascotas'] > 0 && !empty($cli['mascotas_data'])): ?>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php 
                                            $mascotas_array = explode('||', $cli['mascotas_data']);
                                            foreach ($mascotas_array as $m_item):
                                                $m_parts = explode(':', $m_item);
                                                if (count($m_parts) >= 2):
                                                    $m_id = $m_parts[0];
                                                    $m_nombre = $m_parts[1];
                                                    $m_esp = $m_parts[2] ?? '';
                                            ?>
                                                <a href="paciente_perfil.php?id=<?= $m_id ?>" class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 px-2 py-0.5 rounded-lg text-xs font-medium transition-colors border border-slate-200/60 dark:border-slate-700">
                                                    🐾 <?= htmlspecialchars($m_nombre) ?>
                                                </a>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                            <span class="text-[11px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-800/80 px-1.5 py-0.5 rounded">
                                                (<?= $cli['total_mascotas'] ?>)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Sin mascotas registradas</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="cliente_perfil.php?id=<?= $cli['id'] ?>" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition-colors border border-blue-100 dark:border-blue-900/40">
                                            Ver Ficha
                                        </a>
                                        <a href="agregar_mascota.php?cliente_id=<?= $cli['id'] ?>" title="Añadir mascota a este dueño" class="text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-blue-600 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 p-1.5 rounded-lg transition-colors">
                                            +🐾
                                        </a>
                                        <a href="editar_cliente.php?id=<?= $cli['id'] ?>" title="Editar datos del propietario" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

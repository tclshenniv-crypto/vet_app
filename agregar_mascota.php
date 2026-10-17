<?php
require_once 'conexion.php';
include 'header.php';

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

// Consultar el nombre del cliente
$stmt = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id");
$stmt->execute([':id' => $cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    echo '<p class="text-center text-slate-500 py-12">Propietario no encontrado.</p>';
    include 'footer.php';
    exit;
}
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Agregar Nueva Mascota</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Propietario: <strong class="text-blue-600 dark:text-blue-400"><?= htmlspecialchars($cliente['nombre']) ?></strong></p>
        </div>
        <a href="javascript:history.back()" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600">Cancelar</a>
    </div>

    <form action="guardar_nueva_mascota.php" method="POST" class="space-y-6">
        <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-2">
                🐾 Datos de la Mascota
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre de la Mascota *</label>
                    <input type="text" name="paciente_nombre" required placeholder="Ej: Toby" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Especie *</label>
                    <select name="especie" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Perro">Perro</option>
                        <option value="Gato">Gato</option>
                        <option value="Ave">Ave</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Raza</label>
                    <input type="text" name="raza" placeholder="Ej: Beagle" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Sexo *</label>
                    <select name="sexo" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Macho">Macho</option>
                        <option value="Hembra">Hembra</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Peso Inicial (kg)</label>
                    <input type="number" step="0.01" name="peso_actual" placeholder="Ej: 12.0" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-red-600 dark:text-red-400 uppercase mb-1">⚠️ Alergias (Medicamentos / Otras)</label>
                    <textarea name="alergias" rows="2" placeholder="Ej: Ninguna conocida" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase mb-1">🩺 Padecimientos Crónicos</label>
                    <textarea name="padecimientos" rows="2" placeholder="Ej: Otitis recurrente" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-xl text-sm shadow-sm transition-all">
                Guardar Mascota
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
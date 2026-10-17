<?php
require_once 'conexion.php';
include 'header.php';

$paciente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paciente_id <= 0) {
    echo '<p class="text-center text-slate-500 py-12">Paciente no válido.</p>';
    include 'footer.php';
    exit;
}

// Consultar datos actuales del paciente y del propietario
$sql = "SELECT p.*, c.nombre AS cliente_nombre, c.cedula_doc, c.telefono_principal, c.email 
        FROM pacientes p 
        JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $paciente_id]);
$datos = $stmt->fetch();

if (!$datos) {
    echo '<p class="text-center text-slate-500 py-12">Paciente no encontrado.</p>';
    include 'footer.php';
    exit;
}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Editar Datos del Paciente y Propietario</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Modifica la información general de <?= htmlspecialchars($datos['nombre']) ?> y su dueño.</p>
        </div>
        <a href="paciente_perfil.php?id=<?= $paciente_id ?>" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600">Cancelar</a>
    </div>

    <form action="actualizar_paciente.php" method="POST" class="space-y-6">
        <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
        <input type="hidden" name="cliente_id" value="<?= $datos['cliente_id'] ?>">

        <!-- Datos del Propietario -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                👤 Datos del Propietario (Cliente)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="cliente_nombre" required value="<?= htmlspecialchars($datos['cliente_nombre']) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cédula / DNI / RFC</label>
                    <input type="text" name="cedula_doc" value="<?= htmlspecialchars($datos['cedula_doc'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono Principal (WhatsApp) *</label>
                    <input type="text" name="telefono_principal" required value="<?= htmlspecialchars($datos['telefono_principal']) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($datos['email'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Datos de la Mascota -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                🐾 Datos de la Mascota
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre de la Mascota *</label>
                    <input type="text" name="paciente_nombre" required value="<?= htmlspecialchars($datos['nombre']) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Especie *</label>
                    <select name="especie" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Perro" <?= $datos['especie'] == 'Perro' ? 'selected' : '' ?>>Perro</option>
                        <option value="Gato" <?= $datos['especie'] == 'Gato' ? 'selected' : '' ?>>Gato</option>
                        <option value="Ave" <?= $datos['especie'] == 'Ave' ? 'selected' : '' ?>>Ave</option>
                        <option value="Otro" <?= $datos['especie'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Raza</label>
                    <input type="text" name="raza" value="<?= htmlspecialchars($datos['raza'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Sexo *</label>
                    <select name="sexo" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Macho" <?= $datos['sexo'] == 'Macho' ? 'selected' : '' ?>>Macho</option>
                        <option value="Hembra" <?= $datos['sexo'] == 'Hembra' ? 'selected' : '' ?>>Hembra</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Peso (kg)</label>
                    <input type="number" step="0.01" name="peso_actual" value="<?= htmlspecialchars($datos['peso_actual'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($datos['fecha_nacimiento'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-red-600 dark:text-red-400 uppercase mb-1">⚠️ Alergias (Medicamentos / Otras)</label>
                    <textarea name="alergias" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"><?= htmlspecialchars($datos['alergias'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase mb-1">🩺 Padecimientos Crónicos</label>
                    <textarea name="padecimientos" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"><?= htmlspecialchars($datos['padecimientos'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-xl text-sm shadow-sm transition-all">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
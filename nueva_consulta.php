<?php
require_once 'conexion.php';
include 'header.php';

$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;

$stmt = $pdo->prepare("SELECT p.nombre, p.especie, p.peso_actual, c.nombre AS dueno FROM pacientes p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = :id");
$stmt->execute([':id' => $paciente_id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    echo '<p class="text-center text-slate-500 py-12">Paciente no encontrado.</p>';
    include 'footer.php';
    exit;
}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Nueva Consulta Médica</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Paciente: <strong class="text-blue-600"><?= htmlspecialchars($paciente['nombre']) ?></strong> (<?= htmlspecialchars($paciente['dueno']) ?>)</p>
        </div>
        <a href="paciente_perfil.php?id=<?= $paciente_id ?>" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600">Cancelar</a>
    </div>

    <form action="guardar_consulta.php" method="POST" class="space-y-6">
        <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Peso en esta consulta (kg)</label>
                    <input type="number" step="0.01" name="peso_en_consulta" value="<?= $paciente['peso_actual'] ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Motivo de Consulta / Síntomas *</label>
                <textarea name="motivo" required rows="2" placeholder="Ej: Presenta decaimiento y falta de apetito..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Diagnóstico *</label>
                <textarea name="diagnostico" required rows="2" placeholder="Ej: Infección gastrointestinal leve..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tratamiento & Receta *</label>
                <textarea name="tratamiento" required rows="3" placeholder="Ej: Aplicar 1ml de antibiótico. Reposo y suero oral por 3 días." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
        </div>

        <!-- Opción para Agendar Refuerzo o Vacuna Próxima -->
        <div class="bg-blue-50/50 dark:bg-slate-900 border border-blue-100 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                💉 ¿Programar Próximo Refuerzo o Vacuna? (Opcional)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Título del Recordatorio</label>
                    <input type="text" name="recordatorio_titulo" placeholder="Ej: Refuerzo Parvovirus" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo</label>
                    <select name="recordatorio_tipo" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Vacuna">Vacuna</option>
                        <option value="Desparasitación">Desparasitación</option>
                        <option value="Tratamiento">Tratamiento</option>
                        <option value="Revisión">Revisión</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Próxima Dosis</label>
                    <input type="date" name="recordatorio_fecha" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-xl text-sm shadow-sm transition-all">
                Guardar Consulta
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
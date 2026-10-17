<?php
require_once 'conexion.php';
include 'header.php';

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo '<div class="text-center py-12"><p class="text-slate-500 dark:text-slate-400">Propietario no encontrado.</p><a href="clientes.php" class="text-blue-600 underline mt-2 inline-block">Volver a Propietarios</a></div>';
    include 'footer.php';
    exit;
}
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Editar Datos del Propietario</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Modifica los datos personales y de contacto de <strong class="text-blue-600 dark:text-blue-400"><?= htmlspecialchars($cliente['nombre']) ?></strong>.</p>
        </div>
        <a href="cliente_perfil.php?id=<?= $cliente_id ?>" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600">Volver al Perfil</a>
    </div>

    <form action="actualizar_cliente.php" method="POST" class="space-y-6">
        <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-2">
                👤 Información del Cliente
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="nombre" required value="<?= htmlspecialchars($cliente['nombre']) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cédula / DNI / Documento</label>
                    <input type="text" name="cedula_doc" value="<?= htmlspecialchars($cliente['cedula_doc'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono Principal (WhatsApp) *</label>
                    <input type="text" name="telefono_principal" required value="<?= htmlspecialchars($cliente['telefono_principal']) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono Secundario / Fijo</label>
                    <input type="text" name="telefono_secundario" value="<?= htmlspecialchars($cliente['telefono_secundario'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Dirección de Domicilio</label>
                    <textarea name="direccion" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Notas de Contacto</label>
                    <textarea name="notas_contacto" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($cliente['notas_contacto'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="cliente_perfil.php?id=<?= $cliente_id ?>" class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-xl text-sm shadow-sm transition-all">
                Actualizar Datos
            </button>
        </div>

    </form>
</div>

<?php include 'footer.php'; ?>

<?php
require_once 'conexion.php';
include 'header.php';

$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;

if ($paciente_id <= 0) {
    echo '<div class="text-center py-12"><p class="text-slate-500">Paciente no válido.</p><a href="index.php" class="text-blue-600 underline mt-2 inline-block">Volver al inicio</a></div>';
    include 'footer.php';
    exit;
}

// 1. Consultar datos completos del paciente y propietario
$stmt = $pdo->prepare("SELECT 
        p.*, 
        c.nombre AS dueno, 
        c.cedula_doc, 
        c.telefono_principal, 
        c.email AS dueno_email
    FROM pacientes p 
    JOIN clientes c ON p.cliente_id = c.id 
    WHERE p.id = :id");
$stmt->execute([':id' => $paciente_id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    echo '<p class="text-center text-slate-500 py-12">Paciente no encontrado.</p>';
    include 'footer.php';
    exit;
}

// 2. Consultar última consulta para comparativa clínica
$stmt_last = $pdo->prepare("SELECT * FROM consultas WHERE paciente_id = :id ORDER BY fecha DESC LIMIT 1");
$stmt_last->execute([':id' => $paciente_id]);
$ultima_consulta = $stmt_last->fetch();

$badge_paciente = getMascotaBadgeInfo($paciente['especie']);
$edad_info = calcularEdadMascota($paciente['fecha_nacimiento']);

// Formatear enlace de WhatsApp
$tel_limpio = preg_replace('/[^0-9]/', '', $paciente['telefono_principal']);
$wsp_url = "https://wa.me/" . $tel_limpio . "?text=" . urlencode("Hola " . $paciente['dueno'] . ", le escribimos de la clínica veterinaria sobre la atención de " . ucfirst($paciente['nombre']) . ".");
?>

<div class="space-y-6">

    <!-- Barra de Navegación Superior de la Consulta -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="paciente_perfil.php?id=<?= $paciente_id ?>" class="hover:text-blue-600 transition-colors">Expediente de <?= htmlspecialchars(ucfirst($paciente['nombre'])) ?></a>
                <span>/</span>
                <span class="text-slate-600 dark:text-slate-300 font-semibold">Atención Médica</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🩺 Estación de Consulta Médica</span>
            </h1>
        </div>

        <a href="paciente_perfil.php?id=<?= $paciente_id ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3.5 py-2 rounded-xl transition-all shadow-2xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Cancelar Consulta
        </a>
    </div>

    <!-- Layout Principal: 2 Columnas (Dashboard Clínico) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- ========================================== -->
        <!-- COLUMNA 1: FICHA LATERAL FLOTANTE DEL PACIENTE -->
        <!-- ========================================== -->
        <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-4">
            
            <!-- Tarjeta de Identificación Rápida -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg <?= $badge_paciente['badge'] ?>">
                        <span class="text-sm select-none"><?= $badge_paciente['emoji'] ?></span>
                        <span><?= htmlspecialchars($paciente['especie']) ?></span>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">
                        <?= htmlspecialchars($paciente['sexo']) ?>
                    </span>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= htmlspecialchars(ucfirst($paciente['nombre'])) ?>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Raza: <strong class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars(ucfirst($paciente['raza'] ?? 'No especificada')) ?></strong>
                    </p>
                </div>

                <!-- Métricas Físicas Rápidas -->
                <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Peso Base</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100" id="peso_base_display">
                            <?= $paciente['peso_actual'] ? $paciente['peso_actual'] . ' kg' : 'Sin reg.' ?>
                        </span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Edad Actual</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate block" title="<?= htmlspecialchars($edad_info['completa']) ?>">
                            <?= htmlspecialchars($edad_info['corta']) ?>
                        </span>
                    </div>
                </div>

                <!-- ALERTA DE ALERGIAS EN VIVO -->
                <?php if (!empty($paciente['alergias'])): ?>
                    <div class="bg-red-500/10 border-2 border-red-500/80 dark:border-red-500/60 rounded-xl p-3.5 text-xs space-y-1 animate-pulse">
                        <div class="flex items-center gap-1.5 font-bold text-red-600 dark:text-red-400">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>⚠️ ALERGIAS DETECTADAS</span>
                        </div>
                        <p class="text-red-700 dark:text-red-300 font-semibold leading-relaxed">
                            <?= nl2br(htmlspecialchars($paciente['alergias'])) ?>
                        </p>
                        <span class="block text-[10px] text-red-600 dark:text-red-400/80 italic font-medium">No prescribir medicamentos contraindicados.</span>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-emerald-500 font-bold">✓</span> Sin alergias conocidas
                    </div>
                <?php endif; ?>

                <!-- PADECIMIENTOS CRÓNICOS -->
                <?php if (!empty($paciente['padecimientos'])): ?>
                    <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 rounded-xl p-3 text-xs space-y-1">
                        <span class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1">
                            🩺 Condición Crónica / Preexistente:
                        </span>
                        <p class="text-amber-900 dark:text-amber-200">
                            <?= nl2br(htmlspecialchars($paciente['padecimientos'])) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Última Consulta Registrada (Historial Rápido) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-2 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Última Visita Registrada</span>
                <?php if ($ultima_consulta): ?>
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                            <span class="font-semibold">📅 <?= date('d/m/Y', strtotime($ultima_consulta['fecha'])) ?></span>
                            <?php if ($ultima_consulta['peso_en_consulta']): ?>
                                <span class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded font-bold text-slate-700 dark:text-slate-200">
                                    ⚖️ <?= $ultima_consulta['peso_en_consulta'] ?> kg
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-slate-700 dark:text-slate-200 font-medium line-clamp-2">
                            <span class="text-slate-400">Motivo:</span> <?= htmlspecialchars($ultima_consulta['motivo']) ?>
                        </p>
                        <p class="text-blue-600 dark:text-blue-400 font-medium line-clamp-2">
                            <span class="text-slate-400">Diagnóstico:</span> <?= htmlspecialchars($ultima_consulta['diagnostico']) ?>
                        </p>
                    </div>
                <?php else: ?>
                    <p class="text-slate-400 italic pt-1">Esta será la primera consulta registrada en la clínica.</p>
                <?php endif; ?>
            </div>

            <!-- Datos de Contacto del Propietario -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-3 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Propietario Responsable</span>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">
                            <?= htmlspecialchars($paciente['dueno']) ?>
                        </p>
                        <?php if (!empty($paciente['cedula_doc'])): ?>
                            <span class="text-slate-400 text-[11px] block">DNI: <?= htmlspecialchars($paciente['cedula_doc']) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= $wsp_url ?>" target="_blank" title="Contactar por WhatsApp" class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800/60 font-semibold transition-colors">
                        <span>💬 WhatsApp</span>
                    </a>
                </div>
                <div class="text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span><?= htmlspecialchars($paciente['telefono_principal']) ?></span>
                </div>
            </div>

        </aside>

        <!-- ========================================== -->
        <!-- COLUMNA 2: FORMULARIO CLÍNICO INTERACTIVO  -->
        <!-- ========================================== -->
        <main class="lg:col-span-8">
            <form action="guardar_consulta.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">

                <!-- BLOQUE 1: TRIAJE Y CONSTANTES VITALES -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">1</span>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Triaje y Constantes Vitales</h3>
                            <p class="text-xs text-slate-400">Valores fisiológicos medidos durante el examen físico.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Peso en Consulta -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                ⚖️ Peso en consulta (kg) *
                            </label>
                            <input type="number" step="0.01" id="peso_consulta" name="peso_en_consulta" value="<?= $paciente['peso_actual'] ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <!-- Comparador dinámico de peso -->
                            <div id="peso_diff_badge" class="mt-1 text-[11px] font-medium text-slate-400">
                                <?php if ($paciente['peso_actual']): ?>
                                    <span>Peso registrado previo: <?= $paciente['peso_actual'] ?> kg</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Temperatura Corporal -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                🌡️ Temperatura (°C)
                            </label>
                            <input type="number" step="0.1" name="temperatura" placeholder="Ej: 38.5" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-[11px] text-slate-400 block mt-1">Rango normal: 38.0°C - 39.2°C</span>
                        </div>

                        <!-- Frecuencia Cardíaca / Respiratoria -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                💓 Frec. Cardíaca / Resp.
                            </label>
                            <input type="text" name="frecuencia_cardiaca" placeholder="Ej: 110 lpm / 22 rpm" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-[11px] text-slate-400 block mt-1">Opcional para chequeo clínico</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <!-- Estado de Mucosas -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                👁️ Coloración de Mucosas
                            </label>
                            <select name="mucosas" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Rosadas (Normal)">Rosadas / Normales</option>
                                <option value="Pálidas">Pálidas (Posible anemia o shock)</option>
                                <option value="Ictéricas">Ictéricas / Amarillentas (Hígado/Bilis)</option>
                                <option value="Cianóticas">Cianóticas / Azuladas (Hipoxia)</option>
                                <option value="Congestivas">Congestivas / Enrojecidas</option>
                            </select>
                        </div>

                        <!-- Médico Veterinario Responsable -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                👨‍⚕️ Veterinario Responsable
                            </label>
                            <input type="text" name="veterinario" placeholder="Ej: Dr. Carlos Mendoza" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- BLOQUE 2: ANAMNESIS Y DIAGNÓSTICO -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">2</span>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Evaluación y Diagnóstico</h3>
                            <p class="text-xs text-slate-400">Motivo reportado por el propietario y dictamen profesional.</p>
                        </div>
                    </div>

                    <!-- Motivo de Consulta con sugerencias rápidas -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">
                                Motivo de Consulta / Síntomas *
                            </label>
                            <span class="text-[11px] text-slate-400">Atajos rápidos:</span>
                        </div>

                        <!-- Chips de motivos comunes -->
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <button type="button" onclick="agregarSintoma('Control rutinario y chequeo general')" class="text-[11px] bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950 text-slate-600 dark:text-slate-300 hover:text-blue-600 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 transition-colors">+ Control rutinario</button>
                            <button type="button" onclick="agregarSintoma('Vómitos y deposiciones líquidas')" class="text-[11px] bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950 text-slate-600 dark:text-slate-300 hover:text-blue-600 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 transition-colors">+ Vómitos / Diarrea</button>
                            <button type="button" onclick="agregarSintoma('Pérdida de apetito y decaimiento')" class="text-[11px] bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950 text-slate-600 dark:text-slate-300 hover:text-blue-600 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 transition-colors">+ Decaimiento</button>
                            <button type="button" onclick="agregarSintoma('Prurito intenso y lesiones dérmicas')" class="text-[11px] bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950 text-slate-600 dark:text-slate-300 hover:text-blue-600 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 transition-colors">+ Problema en Piel</button>
                            <button type="button" onclick="agregarSintoma('Dificultad al caminar / Claudicación')" class="text-[11px] bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950 text-slate-600 dark:text-slate-300 hover:text-blue-600 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 transition-colors">+ Claudicación / Cojera</button>
                        </div>

                        <textarea id="motivo_textarea" name="motivo" required rows="3" placeholder="Describe los síntomas observados, tiempo de evolución y comportamiento..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Diagnóstico Clínico -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                            Diagnóstico Clínico (Presuntivo o Definitivo) *
                        </label>
                        <textarea name="diagnostico" required rows="2" placeholder="Ej: Gastroenteritis aguda bacteriana secundaria a indiscreción alimentaria..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 leading-relaxed"></textarea>
                    </div>
                </div>

                <!-- BLOQUE 3: PLAN TERAPÉUTICO Y RECETA -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">3</span>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Tratamiento & Prescripción Médica</h3>
                            <p class="text-xs text-slate-400">Indicaciones para el tratamiento en clínica y receta para el hogar.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                            💊 Tratamiento, Medicamentos y Dosis *
                        </label>
                        <textarea name="tratamiento" required rows="4" placeholder="Ejemplo:
1. Metronidazol 250mg: 1 comprimido cada 12 horas por 7 días vía oral con comida.
2. Protector gástrico: 2ml en ayunas por 5 días.
3. Dieta blanda (pollo hervido y arroz) por 3 días." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono text-xs leading-relaxed"></textarea>
                        <span class="text-[11px] text-slate-400 block mt-1">Estas indicaciones quedarán guardadas en la bitácora del paciente y listas para consultar.</span>
                    </div>
                </div>

                <!-- BLOQUE 4: EXÁMENES Y ESTUDIOS MÉDICOS ADJUNTOS -->
                <div class="bg-white dark:bg-slate-900 border border-indigo-200/80 dark:border-indigo-900/50 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">4</span>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                                    <span>📎 Estudios y Exámenes Médicos</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800">Opcional</span>
                                </h3>
                                <p class="text-xs text-slate-400">Adjunta análisis de sangre, radiografías o ecografías para el expediente.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                Tipo de Examen o Estudio
                            </label>
                            <select name="archivo_tipo" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="Hemograma / Laboratorio">🩸 Hemograma / Laboratorio</option>
                                <option value="Radiografía (Rayos X)">🩻 Radiografía (Rayos X)</option>
                                <option value="Ecografía / Ultrasonido">🔬 Ecografía / Ultrasonido</option>
                                <option value="Urianálisis / Coprológico">🧪 Urianálisis / Coprológico</option>
                                <option value="Informe Quirúrgico / Biopsia">📋 Informe Quirúrgico / Biopsia</option>
                                <option value="Otro Examen">📄 Otro Documento Médico</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1">
                                Título o Nota del Estudio
                            </label>
                            <input type="text" name="archivo_descripcion" placeholder="Ej: Placa de tórax lateral" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Zona Drag & Drop / Input File -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">
                            Subir Archivo (PDF, JPG, PNG)
                        </label>
                        <div class="relative border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 rounded-2xl p-6 text-center cursor-pointer transition-all bg-slate-50/50 dark:bg-slate-800/30 group/drop" onclick="document.getElementById('input_archivo').click();">
                            <input type="file" id="input_archivo" name="archivo_estudio" accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden" onchange="mostrarArchivoSeleccionado(this)">
                            
                            <div id="dropzone_prompt" class="space-y-2">
                                <div class="w-12 h-12 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shadow-xs group-hover/drop:scale-110 transition-transform">
                                    📁
                                </div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    Haz clic aquí para seleccionar o arrastra el archivo
                                </p>
                                <p class="text-xs text-slate-400">
                                    Archivos permitidos: PDF de laboratorio, imágenes de Rayos X o ecografías (Máx. 10MB)
                                </p>
                            </div>

                            <!-- Preview si se selecciona un archivo -->
                            <div id="dropzone_preview" class="hidden items-center justify-center gap-3 p-2">
                                <div class="text-2xl" id="preview_icono">📄</div>
                                <div class="text-left">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate max-w-xs" id="preview_nombre"></p>
                                    <span class="text-xs text-slate-400" id="preview_tamano"></span>
                                </div>
                                <button type="button" onclick="cancelarArchivo(event)" class="text-xs text-red-500 hover:text-red-700 font-semibold ml-4 p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
                                    ✕ Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BLOQUE 5: SEGUIMIENTO Y RECORDATORIO -->
                <div class="bg-blue-50/50 dark:bg-slate-900 border border-blue-100 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-blue-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">5</span>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">Próxima Cita / Recordatorio</h3>
                                <p class="text-xs text-slate-400">Programa la fecha de revisión o la próxima dosis preventiva.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Título del Recordatorio</label>
                            <input type="text" name="recordatorio_titulo" placeholder="Ej: Revisión de evolución o Refuerzo" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo de Evento</label>
                            <select name="recordatorio_tipo" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Revisión">🩺 Revisión / Control Clínico</option>
                                <option value="Vacuna">💉 Vacunación</option>
                                <option value="Desparasitación">💊 Desparasitación</option>
                                <option value="Tratamiento">🩹 Tratamiento en Clínica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Programada</label>
                            <input type="date" name="recordatorio_fecha" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN INFERIORES -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="paciente_perfil.php?id=<?= $paciente_id ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-7 py-2.5 rounded-xl text-sm shadow-md transition-all flex items-center gap-2 hover:shadow-lg hover:scale-102">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Guardar Consulta Médica
                    </button>
                </div>
            </form>
        </main>

    </div>

</div>

<!-- SCRIPT DE INTERACTIVIDAD PARA LA CONSULTA -->
<script>
// Agregar síntomas sugeridos con un clic
function agregarSintoma(texto) {
    const textarea = document.getElementById('motivo_textarea');
    if (textarea.value.trim() === '') {
        textarea.value = texto;
    } else {
        textarea.value += ', ' + texto.toLowerCase();
    }
    textarea.focus();
}

// Comparador dinámico de peso en tiempo real
const pesoBase = <?= (float)($paciente['peso_actual'] ?? 0) ?>;
const pesoInput = document.getElementById('peso_consulta');
const pesoBadge = document.getElementById('peso_diff_badge');

pesoInput.addEventListener('input', function() {
    const nuevoPeso = parseFloat(this.value);
    if (isNaN(nuevoPeso) || pesoBase <= 0) {
        return;
    }
    const diff = (nuevoPeso - pesoBase).toFixed(2);
    if (diff > 0) {
        pesoBadge.innerHTML = `<span class="text-amber-500 font-semibold">↗️ Aumentó +${diff} kg</span> (Anterior: ${pesoBase} kg)`;
    } else if (diff < 0) {
        pesoBadge.innerHTML = `<span class="text-blue-500 font-semibold">↘️ Bajó ${diff} kg</span> (Anterior: ${pesoBase} kg)`;
    } else {
        pesoBadge.innerHTML = `<span class="text-emerald-500 font-semibold">= Mantiene su peso</span> (${pesoBase} kg)`;
    }
});

// Manejo visual de selección de archivo de estudio
function mostrarArchivoSeleccionado(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('dropzone_prompt').classList.add('hidden');
        const preview = document.getElementById('dropzone_preview');
        preview.classList.remove('hidden');
        preview.classList.add('flex');

        document.getElementById('preview_nombre').textContent = file.name;
        document.getElementById('preview_tamano').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

        const ext = file.name.split('.').pop().toLowerCase();
        const icono = document.getElementById('preview_icono');
        if (ext === 'pdf') {
            icono.textContent = '📄';
        } else if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
            icono.textContent = '🩻';
        } else {
            icono.textContent = '📎';
        }
    }
}

function cancelarArchivo(e) {
    e.stopPropagation();
    const input = document.getElementById('input_archivo');
    input.value = '';
    document.getElementById('dropzone_prompt').classList.remove('hidden');
    const preview = document.getElementById('dropzone_preview');
    preview.classList.add('hidden');
    preview.classList.remove('flex');
}
</script>

<?php include 'footer.php'; ?>
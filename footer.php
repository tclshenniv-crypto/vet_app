</main>

    <!-- Scripts de Interacción en Tiempo Real y Tema -->
    <script>
        // --- 1. MODO OSCURO ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        themeToggleBtn.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        });

        // --- 2. NAVEGACIÓN DINÁMICA AJAX (SIN RECARGAR PÁGINA) ---
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (href && !href.startsWith('http') && !href.startsWith('#') && !link.hasAttribute('target')) {
                e.preventDefault();
                loadPage(href);
            }
        });

        function loadPage(url) {
            const content = document.getElementById('app-content');
            content.style.opacity = '0.4';

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('app-content');
                    
                    if (newContent) {
                        content.innerHTML = newContent.innerHTML;
                        document.title = doc.title;
                        window.history.pushState({path: url}, '', url);
                        highlightActiveNav(url);
                    } else {
                        window.location.href = url;
                    }
                    content.style.opacity = '1';
                })
                .catch(err => {
                    console.error('Error al cargar la vista:', err);
                    window.location.href = url;
                });
        }

        window.addEventListener('popstate', function() {
            loadPage(window.location.href);
        });

        function highlightActiveNav(url) {
            let currentFile = url.split('/').pop().split('?')[0] || 'index.php';
            // Mapear subvistas al enlace principal de navegación correspondiente
            if (['paciente_perfil.php', 'nuevo_paciente.php', 'editar_paciente.php', 'nueva_consulta.php'].includes(currentFile)) {
                currentFile = 'index.php';
            } else if (['cliente_perfil.php', 'nuevo_cliente.php', 'editar_cliente.php', 'agregar_mascota.php'].includes(currentFile)) {
                currentFile = 'clientes.php';
            }

            document.querySelectorAll('.nav-link').forEach(nav => {
                const page = nav.getAttribute('data-page');
                if (page === currentFile) {
                    nav.classList.add('text-blue-600', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-slate-800');
                    nav.classList.remove('text-slate-600', 'dark:text-slate-300');
                } else {
                    nav.classList.remove('text-blue-600', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-slate-800');
                    nav.classList.add('text-slate-600', 'dark:text-slate-300');
                }
            });
        }

        highlightActiveNav(window.location.href);
    </script>
</body>
</html>
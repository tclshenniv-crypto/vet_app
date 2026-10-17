# VetApp - Sistema de Gestión Veterinaria 🐾

Sistema web para la administración de historias clínicas, consultas, pacientes (mascotas) y propietarios en clínicas veterinarias.

## 🚀 Características
- **Gestión de Clientes / Propietarios**: Registro, edición, búsqueda y fichas de contacto.
- **Gestión de Pacientes**: Registro de mascotas asociadas a propietarios, detalles de especie, raza, edad y peso.
- **Consultas Médicas**: Registro cronológico de consultas, diagnósticos, recetas y notas de evolución.
- **Interfaz Moderna**: Diseño responsivo y amigable adaptado para consultorios veterinarios.

## 🛠️ Tecnologías Utilizadas
- **PHP** (PDO para consultas seguras y preparadas)
- **MySQL / MariaDB**
- **HTML5 & CSS3**
- **JavaScript**

## ⚙️ Instalación y Configuración

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/tclshenniv-crypto/vet_app.git
   ```

2. **Mover a tu servidor local**:
   Coloca los archivos en tu carpeta de servidor local (por ejemplo en `c:/xampp/htdocs/vet_app` para XAMPP).

3. **Configuración de Base de Datos**:
   - Crea una base de datos MySQL (por ejemplo `vet_clinica`).
   - Copia el archivo `conexion.example.php` y renómbralo a `conexion.php`:
     ```bash
     cp conexion.example.php conexion.php
     ```
   - Configura tus credenciales locales en `conexion.php`.

4. **Ejecutar**:
   Abre tu navegador y entra a:
   ```
   http://localhost/vet_app/
   ```

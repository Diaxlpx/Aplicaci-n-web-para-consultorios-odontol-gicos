# Consultorio Odontológico — Historia Clínica

Aplicación web para la gestión de la **historia clínica de un consultorio
odontológico**. Permite iniciar sesión, administrar pacientes, registrar los
procedimientos realizados en cada consulta y descargar la historia clínica en
**PDF**.

Desarrollada con **PHP, MySQL, Bootstrap 5 y AJAX**, sin dependencias externas
(no requiere Composer).

---

## Funcionalidades

- **Inicio de sesión** con contraseña cifrada (`password_hash` / `password_verify`).
- **Panel principal** con indicadores: pacientes, procedimientos, consultas y
  consultas del mes, además de las últimas consultas registradas.
- **Menú de opciones** lateral (panel, pacientes, procedimientos).
- **Gestión de pacientes**: registrar, editar, buscar (AJAX) y eliminar.
- **Catálogo de procedimientos odontológicos**: limpieza, blanqueo, extracción
  de muelas, extracción de cordales, tratamiento de conductos, calzas, etc.
  Se pueden crear, editar, activar/desactivar y eliminar.
- **Historia clínica por paciente**: cada consulta registra fecha, motivo,
  diagnóstico, tratamiento, observaciones y los procedimientos realizados con su
  costo.
- **Generación de PDF** de la historia clínica completa, con un generador propio
  en PHP (sin librerías externas).
- **AJAX** en la búsqueda y en todas las operaciones de alta, edición y baja.
- Diseño responsive con **Bootstrap 5** y Bootstrap Icons.

---

## Tecnologías utilizadas

| Tecnología | Uso |
|------------|-----|
| PHP 8.x    | Lógica del servidor y generación del PDF |
| MySQL / MariaDB | Base de datos relacional |
| Bootstrap 5 | Interfaz responsive (CDN) |
| Bootstrap Icons | Iconografía (CDN) |
| JavaScript (fetch) | Peticiones AJAX |
| PDO | Acceso seguro a la base de datos (consultas preparadas) |

---

## Estructura del proyecto

```
Trabajo_web/
├── index.php                  Panel principal
├── login.php                  Inicio de sesión
├── logout.php                 Cierre de sesión
├── pacientes.php              Listado y registro de pacientes
├── paciente.php               Ficha e historia clínica del paciente
├── procedimientos.php         Catálogo de procedimientos
├── historia_pdf.php           Generación del PDF de la historia clínica
├── install.php                Instalador local (eliminar en producción)
├── config/
│   ├── app.php                Nombre, moneda y zona horaria
│   └── database.php           Credenciales de conexión MySQL
├── includes/
│   ├── auth.php               Sesión, CSRF y utilidades
│   ├── header.php             Cabecera y menú
│   ├── footer.php             Pie y scripts
│   └── SimplePDF.php          Generador de PDF sin dependencias
├── api/
│   ├── _bootstrap.php         Utilidades comunes de la API
│   ├── pacientes.php          CRUD de pacientes (AJAX)
│   ├── procedimientos.php     CRUD de procedimientos (AJAX)
│   └── consultas.php          Registro de consultas (AJAX)
├── assets/
│   ├── css/styles.css
│   └── js/
│       ├── app.js
│       ├── pacientes.js
│       ├── procedimientos.js
│       └── paciente.js
├── database/
│   └── schema.sql             Tablas y datos iniciales
└── README.md
```

---

## Requisitos

- PHP **7.4 o superior** (probado en PHP 8.2) con la extensión **pdo_mysql**.
- MySQL **5.7+** o MariaDB **10.3+**.
- Un servidor web (Apache, Nginx) o el servidor integrado de PHP.
- Navegador moderno.

---

## Instalación local (XAMPP)

1. **Copie la carpeta** `Trabajo_web` dentro de `C:\xampp\htdocs\`.
2. Inicie **Apache** y **MySQL** desde el panel de XAMPP.
3. Configure la conexión en `config/database.php` (por defecto XAMPP usa
   `127.0.0.1`, usuario `root`, contraseña vacía).
4. Abra en el navegador:

   ```
   http://localhost/Trabajo_web/install.php
   ```

   y pulse **Instalar ahora**. Esto crea la base de datos, las tablas y los datos
   iniciales.
5. Entre a `http://localhost/Trabajo_web/` e inicie sesión.

### Credenciales iniciales

| Campo | Valor |
|-------|-------|
| Correo | `admin@odontologia.com` |
| Contraseña | `admin123` |

> Cambie la contraseña en producción (puede actualizar el hash en la tabla
> `usuarios`).

### Alternativa sin instalador

Cree la base de datos `consultorio_odontologico` en phpMyAdmin y luego importe
`database/schema.sql` seleccionando esa base de datos.

### Servidor integrado de PHP

```bash
cd C:\xampp\htdocs\Trabajo_web
C:\xampp\php\php.exe -S localhost:8000
```

Luego abra `http://localhost:8000/` y ejecute `install.php` una vez.

---

## Ejemplo de uso

1. Inicie sesión.
2. En **Pacientes** → **Nuevo paciente**, registre a una persona.
3. Pulse el icono de **historia clínica** (documento) del paciente.
4. Pulse **Nueva consulta**, seleccione la fecha, agregue uno o varios
   procedimientos con su costo y guarde.
5. La consulta aparece en la historia clínica. Use **Descargar PDF** para
   obtener el reporte completo.

---

## Despliegue en un hosting gratuito (InfinityFree)

1. Cree una cuenta y un **hosting account** en <https://infinityfree.com>.
2. En el panel de control, cree una **base de datos MySQL** y anote:
   - Host (p. ej. `sqlXXX.infinityfree.com`)
   - Nombre de la base de datos
   - Usuario y contraseña
3. Edite `config/database.php` con esos datos.
4. Suba todos los archivos a la carpeta `htdocs` por **FTP** o con el
   **File Manager**.
5. En **phpMyAdmin**, seleccione su base de datos e **importe**
   `database/schema.sql`.
6. Abra su dominio, inicie sesión y verifique el funcionamiento.
7. **Elimine `install.php`** del servidor.

> **Nota sobre Vercel:** Vercel está orientado a aplicaciones Node/estáticas y
> no ejecuta PHP con MySQL de forma nativa. Para este proyecto use un hosting
> con soporte PHP + MySQL (InfinityFree, FreeHostia, AwardSpace, 000webhost) o
> un servidor propio.

### Otras opciones de hosting gratuito

- FreeHostia
- AwardSpace
- 000webhost
- Hostinger (plan con PHP)

---

## Subir el proyecto a GitHub

```bash
cd C:\Users\TU_USUARIO\Desktop\Trabajo_web
git init -b main
git add .
git commit -m "Aplicacion web de historia clinica odontologica"
git remote add origin https://github.com/TU_USUARIO/Trabajo_web.git
git push -u origin main
```

Recuerde pegar la URL del repositorio y del sitio publicado en el aula virtual.

---

## Seguridad implementada

- Contraseñas cifradas con `password_hash` (bcrypt).
- Consultas preparadas con PDO (previene inyección SQL).
- Escape de salida con `htmlspecialchars` (previene XSS).
- Tokens **CSRF** en formularios y peticiones AJAX.
- Validación de datos en el servidor.
- Carpetas `config/` e `includes/` protegidas con `.htaccess`.

---

## Solución de problemas

| Problema | Solución |
|----------|----------|
| *No se pudo conectar a la base de datos* | Revise `config/database.php` y que MySQL esté iniciado. |
| *Acceso denegado para el usuario root* | En XAMPP la contraseña suele estar vacía; ajústela. |
| El PDF no descarga | Verifique que no haya salida antes de `header()` y que `pdo_mysql` esté activo. |
| Error al importar `schema.sql` | Seleccione primero la base de datos en phpMyAdmin. |
| `install.php` no crea la base | El usuario de MySQL necesita permisos de creación; use phpMyAdmin. |

---

## Autor

Proyecto académico de desarrollo web — Consultorio Odontológico **Dr. Juan Medina**.

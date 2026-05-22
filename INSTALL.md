# 🚀 DevPanel - Guía de Instalación

DevPanel es un panel de administración para XAMPP. Gestiona fácilmente tu servidor local, proyectos y bases de datos.

## 📋 Requisitos

- XAMPP instalado
- PHP 7.4+
- Apache activado
- MySQL/MariaDB activado

## 🔧 Instalación

### 1. **Descargar y Posicionar**

```bash
# Clona o descarga el repositorio
git clone https://github.com/tuusuario/devpanel.git /opt/lampp/htdocs/devpanel

# O descargalo manualmente en:
/opt/lampp/htdocs/devpanel/
```

### 2. **Configuración Inicial**

```bash
# Abre en tu navegador
http://localhost/devpanel/setup.php
```

**En la página de setup:**
- ✅ Ingresa tu contraseña (mínimo 12 caracteres)
- ✅ Confirma la contraseña
- ✅ Haz clic en "Configurar Contraseña"

Se auto-elimina después de la primera configuración por seguridad.

Para repositorios públicos, no subas tu `config.php`. Usa `config.example.php` como plantilla y deja que cada usuario configure sus propias rutas, contraseña y datos de GitHub.

### 3. **Inicia Sesión**

```
http://localhost/devpanel/login.html
```

**Credenciales:**
- 🔑 Contraseña: La que configuraste en setup.php

## 🔐 Cambiar Contraseña

### Opción 1: Desde el Panel (Recomendado)

1. Inicia sesión en DevPanel
2. Menú lateral → **"Cambiar Contraseña"**
3. Ingresa contraseña actual + nueva contraseña
4. Haz clic en "Cambiar Contraseña"

### Opción 2: Manual (Si olvidas la contraseña)

```bash
# Genera un nuevo hash
php -r "echo password_hash('tu_nueva_contraseña', PASSWORD_BCRYPT, ['cost' => 10]);"
```

Luego edita `/opt/lampp/htdocs/devpanel/config.php` y reemplaza el valor:

```php
<?php
return [
    'DEVPANEL_PASSWORD' => '$2y$10$..tu_nuevo_hash..',
];
```

## 📁 Estructura de Archivos

```
devpanel/
├── setup.php              # 🔧 Configuración inicial (se ejecuta 1 sola vez)
├── login.html             # 🔑 Página de login
├── index.php              # 📊 Dashboard
├── change_password.php    # 🔐 Cambiar contraseña
├── config.example.php     # ⚙️ Plantilla pública de configuración
├── config.php             # 🔒 Configuración local privada, ignorada por Git
├── api/                   # 🔌 Endpoints
│   ├── login.php
│   ├── logout.php
│   ├── terminal.php
│   ├── deploy.php
│   └── ...
├── includes/
│   ├── security.php       # 🛡️ Funciones de seguridad
│   ├── services.php
│   └── ...
├── assets/
│   ├── js/app.js
│   └── css/style.css
└── logs/                  # 📝 Registro de acciones
```

## 🛡️ Seguridad

### Protecciones Implementadas

✅ **Contraseñas hasheadas** con bcrypt (PASSWORD_BCRYPT)
✅ **Rate Limiting** - Máx 5 intentos fallidos en 15 minutos
✅ **CSRF Tokens** - Validación en todos los POST
✅ **Session Security** - httponly, samesite=strict
✅ **Security Headers** - X-Frame-Options, CSP, etc.
✅ **HTTP Method Validation** - GET/POST validación
✅ **Output Sanitization** - Previene XSS
✅ **Audit Logging** - Todos los eventos se registran
✅ **File Protection** - .htaccess protege archivos sensibles

## ✅ Permisos Locales

Después de instalar, entra al dashboard y revisa **Permisos del sistema**.

Rutas importantes:

- `config.php`: escritura necesaria para guardar configuración desde la UI
- `logs/`: escritura necesaria para auditoría y notificaciones
- `tmp/`: escritura necesaria para generar ZIP
- `/opt/lampp/htdocs`: escritura necesaria para crear o clonar proyectos
- logs de XAMPP: lectura necesaria para el visor de logs

Ejemplo orientativo para un entorno local:

```bash
sudo chown -R "$USER":www-data /opt/lampp/htdocs/devpanel
chmod 775 /opt/lampp/htdocs/devpanel/logs /opt/lampp/htdocs/devpanel/tmp
chmod 664 /opt/lampp/htdocs/devpanel/config.php
```

Adapta el grupo según el usuario con el que ejecute Apache/XAMPP en tu equipo.

También puedes usar el helper local:

```bash
./scripts/fix-local-permissions.sh
FIX_HTDOCS=1 ./scripts/fix-local-permissions.sh
```

La segunda línea pide `sudo` y permite crear/clonar proyectos directamente en `/opt/lampp/htdocs`.

## 🌍 Publicar en GitHub

Antes de subir:

- No subas `config.php`
- No subas `.env`
- No subas ZIP generados
- No subas tokens, contraseñas, usuarios privados ni remotes personales
- Usa `config.example.php` como plantilla pública
- Deja que cada usuario configure GitHub desde la interfaz

Checklist rápido antes de publicar:

```bash
git status --short
find . -name '*.php' -print0 | xargs -0 -n1 /opt/lampp/bin/php -l
git diff --check
```

También revisa en el navegador:

- Login
- Dashboard
- Permisos del sistema
- File Manager
- Logs
- MariaDB
- GitHub configurado por el usuario

### Logs de Auditoría

Todos los accesos se registran en:

```
/opt/lampp/htdocs/devpanel/logs/actions.log
```

Formato:
```
[2024-05-20 10:30:45] [127.0.0.1] [authenticated] [login_success] User logged in
[2024-05-20 10:31:12] [127.0.0.1] [authenticated] [execute_command] ls -la
```

## 🚀 Características

- 📊 **Dashboard** - Estado de servicios y stats del sistema
- 🖥️ **Terminal** - Ejecuta comandos seguros desde el navegador
- 📁 **Gestor de Proyectos** - Crea, abre y despliega proyectos
- 🧭 **Actividad de Proyectos** - Archivos recientes, acciones y commits
- 🗄️ **MariaDB Manager** - Crear, importar, exportar y eliminar bases de datos
- 🎨 **Temas** - Dark, Cyber, Ubuntu y Glass
- ✅ **Diagnóstico de Permisos** - Detecta rutas sin escritura/lectura
- 📦 **Descargar ZIP** - Comprime proyectos (excluye node_modules, .git)
- 🚀 **Deploy FTP** - Sube proyectos a servidores remotos
- 🔧 **Control de Servicios** - Start/Stop/Restart Apache y MySQL
- 📝 **Ver Logs** - Acceso a logs del servidor
- 🔑 **Gestión de Contraseñas** - Cambiar contraseña seguramente

## 📝 Comandos Permitidos en Terminal

Seguridad: Solo se pueden ejecutar estos comandos:

- `ls`, `cd`, `pwd`, `cat`, `grep`, `find`
- `git`, `svn`
- `npm`, `composer`, `php`, `python`

## 🐛 Solución de Problemas

### "Error al guardar la contraseña"

```bash
# Verifica permisos de carpeta
chmod 755 /opt/lampp/htdocs/devpanel
chmod 755 /opt/lampp/htdocs/devpanel/logs
```

### "Acceso denegado a phpmyadmin"

```bash
# Reinicia los servicios
sudo /opt/lampp/lampp restart
```

### "No puedo cambiar contraseña"

```bash
# Reset manual
php -r "echo password_hash('nueva_contraseña', PASSWORD_BCRYPT, ['cost' => 10]);"
# Copia el hash en config.php
```

## 📞 Soporte

Si encuentras problemas:

1. Revisa `/logs/actions.log` para errores
2. Verifica que Apache y MySQL estén activos
3. Asegúrate de tener permisos de carpeta correctos

## 📄 Licencia

MIT

---

**¡Disfruta DevPanel!** 🎉

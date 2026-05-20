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
- ✅ Ingresa tu contraseña (mínimo 6 caracteres)
- ✅ Confirma la contraseña
- ✅ Haz clic en "Configurar Contraseña"

Se auto-elimina después de la primera configuración por seguridad.

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
├── config.php             # ⚙️ Configuración (contraseña hasheada)
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

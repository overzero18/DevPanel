# Dashboard Sections

Las secciones de esta carpeta son parciales PHP usados por `index.php`.

Reglas:

- No deben iniciar sesión ni cargar layout.
- Pueden leer variables preparadas por `index.php`.
- Mantienen HTML de una sección concreta del dashboard.
- Si una sección necesita lógica pesada, moverla antes a `includes/helpers/`.

Primer bloque extraído:

- `dashboard/system_metrics.php`
- `dashboard/local_domains.php`
- `dashboard/backups.php`
- `dashboard/docker.php`

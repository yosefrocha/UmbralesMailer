# Umbrales Mail Sender - Starter Hostinger v2

Base funcional del sistema para Hostinger y entornos similares, construida **sin Laravel, sin Node y sin Composer en servidor**.

## Stack real
- PHP 8.2+
- MySQL 5.7+ o MariaDB 10.3+
- Apache 2.4+ o LiteSpeed con `mod_rewrite`
- Bootstrap 5.3 por CDN
- Integración con Amazon SES por API v2 usando **firma AWS Signature V4 en PHP puro**
- Instalación por FTP/SFTP + `schema.sql` vía phpMyAdmin

## Módulos incluidos
- Login / logout
- Contraseña temporal generada por administrador
- Cambio de contraseña del usuario
- Dashboard con métricas base
- Usuarios (admin)
- Destinatarios + importación CSV
- Campañas
- Editor de mensaje HTML/texto
- Configuración SES desde panel
- Envío por lotes con pausa y reanudación
- Monitoreo por polling AJAX
- Desuscripción pública
- Auditoría base

## Credenciales iniciales
- Correo: `admin@umbrales.local`
- Contraseña: `Cambiar123!`

## Instalación rápida
1. Crear subdominio `mailer` en Hostinger.
2. Subir el contenido de este proyecto al directorio del subdominio.
3. Editar `config/app.php`, `config/database.php` y, si quieres, los valores por defecto de `config/ses.php`.
4. Importar `database/schema.sql` desde phpMyAdmin.
5. Entrar con el usuario administrador inicial.
6. Cambiar de inmediato la contraseña del admin.
7. Configurar Amazon SES desde **Configuración**.

## Notas operativas
- El envío masivo funciona por pasos. En la pantalla de sesión de envío el navegador va solicitando lotes pequeños para evitar timeouts del hosting.
- El sistema cuenta como "enviado" aquello que Amazon SES **acepta**. Para métricas de entrega real, aperturas y rebotes se requiere una segunda fase con SNS / webhooks.

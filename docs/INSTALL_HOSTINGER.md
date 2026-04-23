# Instalación en Hostinger para `mailer`

## 1. Subdominio
Crear el subdominio `mailer.tudominio.com` como sitio independiente.

## 2. PHP 8.2+
En la carpeta del subdominio agrega al `.htaccess` del proyecto, si hace falta, la línea de versión PHP recomendada por Hostinger para ese subdominio.

## 3. Base de datos
Crear una base exclusiva para este sistema y anotar:
- host
- base de datos
- usuario
- contraseña

## 4. Subir archivos
Subir por FTP/SFTP todo el contenido del proyecto al directorio del subdominio.

## 5. Configuración
Editar:
- `config/app.php`
- `config/database.php`
- `config/ses.php` (solo si quieres dejar defaults)

## 6. SQL
Entrar a phpMyAdmin e importar `database/schema.sql`.

## 7. Primer acceso
Abrir el subdominio y entrar con:
- correo: `admin@umbrales.local`
- contraseña: `Cambiar123!`

## 8. Endurecimiento inmediato
- cambiar la contraseña del admin
- cambiar `debug` a `false`
- cambiar `encryption_key`
- capturar datos reales de Amazon SES en `Configuración`

## 9. Flujo operativo
1. Configurar SES.
2. Crear usuarios si hace falta.
3. Importar destinatarios por CSV.
4. Crear campaña.
5. Guardar mensaje.
6. Iniciar envío.
7. Monitorear sesión hasta completarla.

# Aplicativo de gestión de envíos

Aplicación web sencilla en PHP y MySQL para registrar, listar, buscar, editar y eliminar envíos. Cada envío contiene destinatario, descripción y dirección.

## Archivos

- `index.php`: interfaz y operaciones CRUD.
- `config.php`: conexión a MySQL y creación automática de la tabla.
- `styles.css`: diseño adaptable con imagen de fondo.
- `database.sql`: SQL opcional para crear la tabla manualmente.

## Requisitos

- PHP 8.0 o superior (extensiones `pdo_mysql` y `mbstring` habilitadas).
- MySQL/MariaDB.
- Un servidor web como Apache o el alojamiento web de Alwaysdata.

## Instalación

1. Sube los archivos a la carpeta pública de tu alojamiento.
2. En `config.php` verifica los datos de conexión.
3. Abre `index.php` en el navegador.
4. Al conectar por primera vez, la aplicación ejecuta `CREATE TABLE IF NOT EXISTS envios`; no es necesario crear la tabla manualmente.

La base de datos `jetecnologia_enviosgit` debe existir previamente y el usuario configurado debe tener permisos para crear tablas y realizar operaciones `SELECT`, `INSERT`, `UPDATE` y `DELETE`.

## Funciones

- Crear un envío.
- Ver todos los envíos y el total registrado.
- Buscar por destinatario, descripción o dirección.
- Editar un envío existente.
- Eliminar un envío con confirmación.
- Validación básica, consultas preparadas y token CSRF en formularios.

## Base de datos

Tabla `envios`:

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED, AUTO_INCREMENT | Identificador |
| `destinatario` | VARCHAR(150) | Persona que recibe |
| `descripcion` | TEXT | Descripción del paquete |
| `direccion` | VARCHAR(255) | Dirección de entrega |
| `creado_en` | TIMESTAMP | Fecha de registro |

## Imagen de fondo

Se utiliza la imagen destacada del artículo de Gureak Datapost:
https://www.gureakdatapost.com/wp-content/uploads/2024/07/iStock-1204029777.jpg

La imagen se carga desde internet, por lo que el servidor/navegador necesita conexión externa para mostrarla.

## Seguridad

Este proyecto es una base funcional sencilla. Antes de usarlo en producción, protege el acceso con autenticación HTTPS, restringe permisos de base de datos y mueve las credenciales fuera de archivos públicos. Si la clave compartida es real, considera cambiarla después de publicar o compartir el proyecto.

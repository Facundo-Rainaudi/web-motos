# AGENTS.md — bike-center

## Proyecto
Sitio web de compraventa de motos. Cualquier usuario registrado puede
publicar motos nuevas o usadas y ver las de otros usuarios con filtros
de búsqueda. El comprador contacta al vendedor por un botón de WhatsApp.
Stack obligatorio: PHP (back), MySQL, HTML y CSS (front), JavaScript
mínimo y puntual (validaciones del formulario).

## Estructura
- `public/`: páginas accesibles por URL, css/, js/, uploads/
- `src/`: config, conexión PDO, auth y models/ (no accesible por URL)
- `views/`: partes repetidas (header, footer)
- `sql/schema.sql`: script de la base de datos
- `docs/`: requisitos.md, specs/, decisiones.md, changelog.md
- `docker-compose.yml`: MySQL y phpMyAdmin para desarrollo

## Entorno y comandos
- PHP 8.5 local.
- Base de datos: MySQL 8.4 en Docker, accesible en 127.0.0.1:3307.
- Levantar base: `docker compose up -d` / apagar: `docker compose stop`
- phpMyAdmin: http://localhost:8080 (root / root)
- Ejecutar el sitio: `php -S localhost:8000 -t public`
- Revisar sintaxis: `php -l <archivo>`
- Credenciales reales en `src/config.php` (no se sube a Git); el modelo
  es `src/config.example.php`.

## Estilo
- Sin frameworks ni Composer.
- Identificadores en inglés (código y base de datos); textos para el
  usuario en español.
- Un archivo = una responsabilidad; nada de SQL dentro de las vistas.
- Todos los nombres de archivos y carpetas en inglés (por ejemplo
  `docs/requirements.md`, `docs/decisions.md`, `01-register-login.md`).
  El contenido de los documentos y los textos para el usuario van en español.

## Seguridad (obligatorio)
- Toda consulta SQL con PDO y consultas preparadas, nunca concatenar.
- Contraseñas con `password_hash()` / `password_verify()`.
- Todo dato de usuario que se imprima en HTML va con `htmlspecialchars()`.
- Verificar en el servidor que el usuario sea dueño de la moto antes
  de editar o borrar.
- Subida de archivos: validar tipo real de imagen y tamaño, y renombrar
  el archivo con un nombre generado por el servidor.

## Reglas
- Leé `docs/requirements.md` y la spec activa en `docs/specs/` antes de tocar código.
- Podés redactar specs nuevas como borrador, pero no modifiques una spec
  ya aprobada sin pedido explícito.
- No agregues dependencias ni cambies el esquema de la base sin actualizar
  antes la spec y `sql/schema.sql`.
- Trabajá una tarea por vez, chica y verificable.
- Y comenta todo el codigo que escribas, explicando como funciona y para que.

## Al terminar cualquier tarea
- Corré `php -l` en los archivos que tocaste.
- Recorré los criterios de aceptación de la spec activa y reportá cuáles
  cumplen y cuáles no.
- Registrá el cambio en `docs/changelog.md` y cualquier decisión de diseño
  en `docs/decisions.md`.
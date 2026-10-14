# Plan de implementación — Spec 01 (Bike Center)

Este documento es el **cómo** de `specs/01-bike-center/spec.md`. La spec define
qué y por qué; acá quedan el stack, la estructura, el esquema de datos y el
orden de trabajo. Regla de oro: nada de código sin historia aprobada (constitución,
principio 2) y una etapa por vez, chica y verificable.

## 1. Entorno y dependencias
- PHP 8.5 local, sin frameworks ni librerías en producción (constitución, principio 1).
- MySQL 8.4 en Docker (`docker-compose.yml`), puerto 3307, credenciales root/root.
- Base de desarrollo: `bike_center`. Base de tests: `bike_center_test`, recreada
  desde `sql/schema.sql` en cada tanda de tests (constitución, principio 4).
- Composer solo como dev-dependencia: Pest + script `composer test`. `vendor/`
  entra en `.gitignore`.
- Comandos: levantar base `docker compose up -d`; sitio `php -S localhost:8000
  -t public`; tests `composer test`; sintaxis `php -l <archivo>`.

## 2. Estructura de carpetas y archivos
```
sql/
  schema.sql              # tablas completas, recreable
  seed.sql                # semilla de prueba (25 motos)
src/
  config.php              # credenciales (gitignored; ejemplo en config.example.php)
  db.php                  # conexión PDO (una por request)
  session.php             # arranque de sesión, cookie no persistente, usuario actual
  auth.php                # registro, login, logout, verificación de dueño
  csrf.php                # token para formularios con sesión
  validation.php          # reglas puras de datos (sin BD ni superglobals)
  storage.php             # subida de fotos: tipo real, tamaño, medidas, hash
  models/
    user_model.php        # SQL de usuarios
    bike_model.php        # SQL de motos (feed, por dueño, CRUD, estado)
    photo_model.php       # SQL de fotos
public/
  index.php               # vitrina (RF-4)
  bike.php                # detalle (RF-5)
  register.php login.php logout.php
  publish.php             # crear moto (RF-3)
  edit-bike.php           # editar datos y fotos (RF-6)
  my-bikes.php            # panel del dueño (RF-9)
  bike_actions.php        # POST: borrar (RF-7) y estado vendida (RF-8)
  api/bikes.php           # salida JSON para el scroll infinito (RF-3/RNF-3)
  uploads/                # fotos con nombre generado por el servidor
  css/style.css  js/feed.js
views/
  header.php footer.php flash.php
  bike_card.php bike_form.php
tests/
  Pest.php                # arranque: reconecta a bike_center_test y carga schema
  fixtures/               # imágenes chicas para tests
  Unit/ValidationTest.php StorageTest.php
  Integration/UserTest.php BikeTest.php PhotoTest.php FeedTest.php
docs/ constitution.md changelog.md decisions.md
specs/ 01-bike-center/spec.md plan.md
```

## 3. Esquema de MySQL (`sql/schema.sql`)
```sql
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,   -- guardado y comparado en minúsculas
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() de PHP
  phone         VARCHAR(20)  NOT NULL,          -- formato +54 11 + 10 dígitos
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bikes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(80)  NOT NULL,
  brand       VARCHAR(60)  NOT NULL,
  `condition` ENUM('new','used') NOT NULL,
  price       DECIMAL(12,2) NOT NULL,           -- hasta 2 decimales, > 0
  currency    ENUM('ARS','USD') NOT NULL,
  description TEXT NOT NULL,
  status      ENUM('available','sold') NOT NULL DEFAULT 'available',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_bikes_created (created_at, id),     -- orden del feed con desempate
  INDEX idx_bikes_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE photos (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bike_id  INT UNSIGNED NOT NULL,
  filename VARCHAR(190) NOT NULL,               -- nombre generado, nunca el original
  position TINYINT UNSIGNED NOT NULL DEFAULT 0, -- orden de muestra
  FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE,
  INDEX idx_photos_bike (bike_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Notas: `condition` y `status` son palabras reservadas → siempre con acentos
inversos. Borrar una moto borra sus fotos en cascada (RF-7). No hay tabla de
sesiones: se usa la sesión nativa de PHP.

## 4. Módulos de `src/` (todo testeable sin servidor web)
- `validation.php`: funciones puras que devuelven error o valor normalizado.
  - `normalize_email()` → minúsculas y sin espacios.
  - `validate_phone()` → quita espacios y guiones, exige 10 dígitos, devuelve
    `+54 11` + los 10 dígitos; rechaza letras, "+" y longitud distinta.
  - `validate_password()` (≥ 8), `validate_full_name()` (≥ 3),
    `validate_price()` (coma o punto, hasta 2 decimales, > 0, sin separador de
    miles), `validate_title()` (≤ 80), `validate_description()` (≤ 2000),
    `validate_currency()` (ARS|USD), `validate_condition()` (new|used).
- `storage.php`: `save_uploaded_photo($tmp)` valida con `finfo` el tipo real
  (JPEG/PNG/WEBP), `<= 2 MB`, medidas `>= 200×200` vía `getimagesize`, genera
  nombre con `random_bytes` + extensión real, calcula `sha1` del contenido
  (para el anti-repetidas) y mueve el archivo. `delete_photo_file()` lo elimina.
- `auth.php`: `register_user()`, `login_user()`, `current_user_id()`,
  `require_login()`, `require_bike_owner($bike)` (verificación de dueño en el
  servidor, además de no mostrar la opción).
- `csrf.php`: `csrf_token()` en la sesión y `csrf_check()` en cada POST con
  `hash_equals`.
- `models/*`: único lugar con SQL, siempre con consultas preparadas de PDO.

## 5. Pantallas y rutas (flujo por RF)
- `index.php` (RF-4): listado resumido (título, marca, precio con moneda,
  etiqueta de estado), orden `created_at DESC, id DESC`, primeras 20 motos.
  Estado vacío invita a publicar. Cualquiera puede verlo.
- `js/feed.js` + `api/bikes.php` (RNF-3): al llegar al final se piden 20 más.
  Paginado por clave (`id` menor al último de la tanda anterior), de modo que
  una moto nueva publicada mientras se baja no duplica ni expulsa motos ya
  vistas. Devuelve JSON `{ bikes: [...], has_more: bool }`.
- `bike.php?id=` (RF-5): completo solo con sesión (nombre del vendedor, fecha,
  fotos); sin sesión → pedido de registro sin filtrar datos; inexistente o
  borrada → "no disponible". Salida siempre con `htmlspecialchars`.
- `register.php` / `login.php` / `logout.php` (RF-1, RF-2): POST + redirect
  (patrón PRG) y mensajes de error en español.
- `publish.php` (RF-3): formulario de datos + hasta 4 fotos. Fotos válidas
  parciales → se publica y se avisa el rechazo; datos inválidos → no se guarda
  nada.
- `my-bikes.php` (RF-9): solo las motos del dueño con estado y acciones;
  estado vacío.
- `edit-bike.php?id=` (RF-6): mismas validaciones que publicar; agregar y quitar
  fotos de a una (1 a 4); editable también si está vendida.
- `bike_actions.php` (RF-7, RF-8): POST con CSRF; borrar (moto + fotos + archivo
  en disco) y alternar disponible/vendida. Ambas acciones exigen dueño.
- Sesión (RF-2): `session_set_cookie_params` con cookie `httponly`, `samesite=Lax`
  y sin `expires` → la cookie muere al cerrar la aplicación del navegador.
- Seguridad transversal: `password_hash`/`password_verify`, consultas
  preparadas, `htmlspecialchars` en toda salida, y en `uploads/` solo entran
  extensiones de imagen generadas por el servidor (nunca el nombre original).

## 6. Tests (Pest, constitución principio 4)
- `tests/Pest.php`: antes de cada tanda apunta a `bike_center_test` y la recrea
  desde `sql/schema.sql`.
- Unit (sin servidor): teléfono (10 dígitos, espacios y guiones ignorados,
  rechaza "+", letras y longitudes), correo en minúsculas, contraseña ≥ 8,
  nombre ≥ 3, precio (coma/punto, 2 decimales, negativo, cero), y reglas de
  fotos (formatos, 2 MB, 200×200, hash de repetida).
- Integración (contra `bike_center_test`):
  - `UserTest`: registro duplicado rechazado sin importar mayúsculas; teléfono
    guardado como `+54 11` + 10 dígitos.
  - `BikeTest`: crear, editar sin alterar la posición en el feed, borrar en
    cascada, alternar vendida, edición de vendida, y que un usuario ajeno no
    logra editar/borrar/vender.
  - `PhotoTest`: máximo 4, mínimo 1, repetida rechazada, borrado en cascada.
  - `FeedTest`: tandas de 20 sin duplicados ni pérdidas con publicaciones
    nuevas en paralelo, orden por creación.
- Lo manual (RNF-2 y RNF-3): checklist de la etapa 5.

## 7. Semilla de prueba (`sql/seed.sql`)
- 1 usuario de prueba (`vendedor@demo.com` / contraseña conocida) y 2 motos a
  su nombre (una vendida) para tener dueño desde el arranque.
- 25 motos repartidas entre 3 usuarios ficticios, con títulos y precios variados
  y fotos copiadas de `tests/fixtures/`, para probar a mano el scroll infinito,
  el orden y la etiqueta de vendida.

## 8. Orden de trabajo y definición de "etapa terminada"
| Etapa | Contenido | Historias/RF |
|---|---|---|
| 0 | Composer + Pest, schema, seed, `db.php`, `session.php`, `validation.php` con tests unitarios | base |
| 1 | Registro, login, logout | H2, RF-1, RF-2 |
| 2 | Publicar con fotos | H4, RF-3 |
| 3 | Vitrina + scroll infinito + detalle | H1, H5, RF-4, RF-5 |
| 4 | Mis publicaciones, editar, borrar, vendida | H6–H9, RF-6 a RF-10 |
| 5 | Responsive + checklist manual de RNF-2 y RNF-3 | H10 |
| 6 | Recorrido final de la spec y registro en docs | cierre |

Una etapa está terminada cuando: sus tests Pest están en verde, `php -l` pasó
en cada archivo PHP tocado, sus criterios de aceptación se recorrieron a mano
sobre la app en local, y el cambio quedó registrado en `docs/changelog.md`
(decisiones nuevas en `docs/decisions.md`).
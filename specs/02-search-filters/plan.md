# Plan de implementación — Spec 02 (Búsqueda y filtros)

Este documento es el **cómo** de `specs/02-search-filters/spec.md` (aprobada
el 2026-10-20). La spec define qué y por qué; acá quedan los módulos, el
contrato de parámetros, los cambios de esquema y el orden de trabajo. Las
reglas de oro no cambian: nada de código sin spec aprobada (constitución,
principio 2), una etapa por vez, chica y verificable, y una tarea por vez.

## 1. Entorno y dependencias
- Sin dependencias nuevas: PHP 8.5, MySQL 8.4 en Docker (puerto 3307), Pest
  como única dev-dependencia (todo igual que la spec 01).
- Comandos habituales: `docker compose up -d`, `php -S localhost:8000 -t
  public`, `composer test`, `php -l <archivo>`.
- La base de tests (`bike_center_test`) se sigue recreando desde
  `sql/schema.sql` en cada tanda: los índices nuevos llegan solos a los tests.

## 2. Archivos que toca esta spec
```
src/
  filters.php                 # NUEVO: parseo puro de parámetros de la URL
  models/bike_model.php       # AMPLÍA: filtros en el feed + contador
public/
  index.php                   # AMPLÍA: lee $_GET, formulario y estados
  api/bikes.php               # AMPLÍA: mismos filtros por tanda del scroll
  js/feed.js                  # AMPLÍA: reenvía los parámetros activos
views/
  filter_bar.php              # NUEVO: formulario GET de búsqueda y filtros
public/css/style.css          # AMPLÍA: estilos de la barra (tokens existentes)
sql/schema.sql                # AMPLÍA: índices nuevos de filtros
tests/
  Unit/FiltersTest.php        # NUEVO: parseo de parámetros
  Integration/SearchTest.php  # NUEVO: consultas filtradas y paginación
```
No se crea ninguna pantalla nueva: todo vive dentro de la vitrina (RF-4).

## 3. Contrato de parámetros (RNF-7)
La selección vive en la URL de `index.php` y de `/api/bikes.php` con los
mismos nombres en ambos lados:

| Parámetro | Valores válidos | Significado |
|---|---|---|
| `q` | texto (≤ 80 caracteres) | contiene título o marca |
| `condition` | `new` \| `used` | condición (valores de la base) |
| `currency` | `ARS` \| `USD` | moneda del precio |
| `min` | número ≥ 0, hasta 2 decimales | precio mínimo (coma o punto) |
| `max` | número ≥ 0, hasta 2 decimales | precio máximo (coma o punto) |
| `status` | `available` | solo motos disponibles |
| `before` | id (solo en la API) | cursor del scroll, como hoy |

Reglas de robustez (RF-12 y RNF-7): cada parámetro se valida **por separado**
en el servidor con lista blanca; el inválido se descarta y el resto sigue
aplicado. `min > max` descarta los dos. Valores fuera de la tabla → ignorados.

## 4. Módulos de `src/` (testeables sin servidor)
- `filters.php` — `parse_feed_filters(array $get): array`: devuelve un array
  normalizado `['q' => ?string, 'condition' => ?string, 'currency' => ?string,
  'min' => ?string, 'max' => ?string, 'status' => ?string]` con `null` en lo
  ausente o inválido. Reglas: `q` con `trim`, sin `%`/`_`/`\` (comodines de
  LIKE, no son texto que un usuario busque; `\` además es el comodín por
  defecto de MySQL) y recortado a 80 caracteres con
  `mb_substr`; precios con **`validate_price()`** del validador existente
  (misma coma/punto y mismos topes que al publicar; lo inválido → `null`);
  enums por lista blanca estricta. Función pura, sin BD ni superglobals.
- `models/bike_model.php`:
  - `list_bikes_feed()` **gana un parámetro opcional** al frente:
    `list_bikes_feed(PDO $pdo, array $filters = [], int $limit = 20,
    ?int $beforeId = null)`. Con `$filters = []` el SQL es byte a byte el
    actual (regresión del RF-4 gratis); con filtros se agregan `WHERE` con
    consultas preparadas: `title LIKE :q OR brand LIKE :q`, `condition =`,
    `currency =`, `price >=`, `price <=`, `status = 'available'`. El orden
    (`created_at DESC, id DESC`) y el paginado por clave no cambian.
    Los dos llamados existentes (`index.php` y `api/bikes.php`) se ajustan a
    la nueva firma.
  - `count_bikes_matching(PDO $pdo, array $filters): int`: mismo `WHERE`,
    `COUNT(*)`, para el contador del RF-13.
- SQL: ningún valor se concatena; los LIKE llevan `ESCAPE` o llegan ya sin
  comodines (viene resuelto del parseo) y todos los parámetros van atados.

## 5. Pantallas y rutas
- `index.php` (RF-11 a RF-13): lee `$_GET` una sola vez con
  `parse_feed_filters()`; con selección activa pide el listado filtrado y el
  contador; renderiza `views/filter_bar.php` siempre (con los valores
  re-poblados) y, debajo, el mismo `bike_card` de siempre. Estados: sin
  resultados → mensaje propio + limpiar (prioridad: sitio vacío → mensaje del
  RF-4). El centinela del scroll solo se emite si `has_more`.
- `views/filter_bar.php`: formulario **GET** hacia `/` (sin JS obligatorio:
  botón "Aplicar filtros"; con JS mejora nada más). Campos: texto `q`,
  selects de condición y moneda, dos inputs numéricos de precio, checkbox
  "Solo disponibles" (`status=available`) y link/botón "Limpiar filtros" hacia
  `/` sin parámetros. Labels con `for`/`id`, inputs con `name` del contrato,
  y el contador N motos encontradas" (singular/plural) sobre la grilla.
- `api/bikes.php`: aplica el **mismo** `parse_feed_filters()` a `$_GET`
  (sin `status`... no: también con él) y llama a `list_bikes_feed` con esos
  filtros; la respuesta JSON no cambia de forma.
- `js/feed.js`: al armar la URL de "ver más" conserva los parámetros
  actuales de la página (`URLSearchParams` de `location.search`) y agrega
  `before`; mejora progresiva: sin JS la primera tanda filtrada ya se ve
  server-side.
- `css/style.css`: barra de filtros mobile-first (375 px: campos apilados;
  ≥ 640 px: fila), con los tokens existentes; foco visible y estados de
  error/vacío con las clases ya creadas. Se aplica la skill `web-design` en
  la etapa correspondiente.

## 6. Esquema (`sql/schema.sql`)
Índices nuevos sobre `bikes` (RNF-9), sumados a los existentes:
```sql
-- en CREATE TABLE bikes, junto a los ya definidos:
KEY idx_bikes_condition (`condition`, currency),  -- filtros de condición/moneda
KEY idx_bikes_price (currency, price),            -- rango de precio por moneda
KEY idx_bikes_status (status)                     -- filtro "solo disponibles"
```
⚠️ Aplicación en desarrollo: `sql/schema.sql` recrea las tablas **vacías**, así
que en `bike_center` NO se recarga; los índices se aplican con `ALTER TABLE`
directo sobre la base viva (los datos de prueba quedan intactos). Los tests
usan el esquema recreado y no requieren acción manual.

## 7. Tests (Pest, constitución principio 4)
- `tests/Unit/FiltersTest.php` (parseo puro): `q` vacío/solo espacios →
  `null`; recorte a 80 con multibyte; `%`, `_` y `\` eliminados; `condition` y
  `currency` fuera de lista → `null`; precios con coma y punto aceptados;
  precio no numérico, negativo o con más de 2 decimales → `null`;
  `min > max` → ambos `null`; `status=available` → `available`; cualquier
  otro valor de `status` → `null`; parámetros desconocidos → ignorados.
- `tests/Integration/SearchTest.php` (sobre `bike_center_test` con fixtures
  creadas en el propio test): búsqueda por título, por marca, combinando
  mayúsculas/acentos ("guzzi" vs "Moto Guzzi"); sin coincidencias → lista
  vacía; cada filtro por separado y todos combinados; vendidas presentes por
  defecto y ausentes con `status=available`; paginación con filtros activos
  (tandas sin repetir ni perder, aún con publicaciones nuevas en el medio);
  `count_bikes_matching` coincide con el listado; llamada sin filtros da el
  mismo resultado que antes (los tests de `FeedTest` siguen intactos como
  red de regresión del RF-4).
- Lo manual: barra a 375 px y 1280 px sin scroll horizontal (skill
  `web-design`), scroll filtrado real en navegador y recorrido por curl de la
  API con parámetros.

## 8. Orden de trabajo y definición de "etapa terminada"
| Etapa | Contenido | RF |
|---|---|---|
| 0 | `filters.php` + `list_bikes_feed` con filtros + `count_bikes_matching` + índices (schema.sql y ALTER en dev) con tests | RF-11, RF-12 (motor) |
| 1 | Barra de filtros en la vitrina: `filter_bar.php`, index.php, estados vacíos, contador, CSS con skill web-design | RF-11 a RF-13 (pantalla) |
| 2 | Scroll filtrado: `api/bikes.php` + `feed.js` + tests de paginación con filtros | RF-13 |
| 3 | Recorrido RF-11 a RF-13 y RNF-7 a RNF-9, `php -l`, registro en docs | cierre |

Una etapa está terminada cuando: sus tests Pest están en verde, `php -l` pasó
en cada archivo PHP tocado, sus criterios de aceptación se recorrieron a mano
sobre la app en local (con curl y en navegador), y el cambio quedó registrado
en `docs/changelog.md` (decisiones nuevas en `docs/decisions.md`).

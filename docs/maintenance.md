# Mantenimiento — pendientes de refactor y diseño

Tareas de mantenimiento **sin historia de usuario**: no son features, por eso
no viven en `specs/`. Se registraron el 2026-10-21 por pedido del usuario y
se ejecutan **después de cerrar la spec 02**, de a una por vez, con la
política de la constitución: tests primero donde aplique, `php -l` en los PHP
tocados, suite completa en verde al cerrar cada tarea, y registro en
`docs/changelog.md`.

Orden sugerido: **A → B → C**, y **D** en paralelo cuando se quiera (el CSS
no se pisa con las otras tres).

## A. Partir `src/models/bike_model.php` (328 líneas)

**Qué hacer**

- Crear `src/models/feed_model.php` con las funciones del feed/vitrina:
  - `bikes_feed_where()`
  - `list_bikes_feed()`
  - `count_bikes_matching()`
- `src/models/bike_model.php` se queda con lo del dueño: `create_bike`,
  `find_bike_with_owner`, `list_bikes_by_user`, `update_bike`,
  `update_bike_with_photos`, `delete_bike_as_owner`,
  `toggle_bike_status_as_owner`.
- Sumar `require_once __DIR__ . '/models/feed_model.php';` en
  `src/bootstrap.php` (después de bike_model no importa: son declaraciones)
  y también en `tests/Pest.php`, que carga los modelos a mano (líneas 17-18).

**Por qué**: es el archivo con más motivos para seguir creciendo (CRUD del
dueño + listados + contador + filtros en un solo archivo); partirlo por rol
lo mantiene acotado.

**Hecho cuando**: los nombres de las funciones no cambian, ningún test se
tocó, la suite queda en verde idéntica (130 tests / 315 aserciones o lo que
haya al momento) y `php -l` limpio. Cero cambio de comportamiento.

## B. Sacar la lógica de `public/edit-bike.php` a `src/` (384 líneas, 215 de manejo de POST)

**Qué hacer — tests primero**

1. Nuevo test de integración (patrón `include` de `VitrinaTest`: poner
   `$_POST`, `$_SERVER['REQUEST_METHOD']` y `$_FILES` de mentira, con
   archivos temporales copiados de `tests/fixtures/`, e incluir
   `public/edit-bike.php` apuntando a `bike_center_test`) que cubra los
   flujos que hoy se verifican sólo con curl:
   - guardar datos + fotos nuevas en el mismo "Guardar cambios";
   - guardar con reemplazo de foto única desde "Guardar cambios";
   - ✕ (quitar foto) y "Reemplazar" como botones del mismo form;
   - datos inválidos → no se guarda nada y quedan los `old`;
   - CSRF inválido → no se guarda;
   - foto repetida por hash, foto inválida (tipo/peso/medidas), cupo de 4;
   - moto inexistente/borrada → redirect a "Mis publicaciones";
   - moto ajena → redirect al detalle (RF-10).
   Ese test nace en **rojo**.
2. Crear `src/bike_edit_actions.php` con la orquestación del POST, p. ej.
   `apply_edit_bike_post(PDO $pdo, int $bikeId, int $userId, string $photoDir): array`
   que devuelve `['errors' => ..., 'old' => ...]` cuando la validación falla
   y hace el PRG (`header()` + `exit`) cuando todo se guardó — ya hay
   precedente de redirects dentro de `src/` (`require_login_or_redirect()`).
3. `public/edit-bike.php` queda con sólo: arranque, carga y control de
   dueño, llamada al módulo, y el HTML (~170 líneas).

**Por qué**: es el archivo más grande del proyecto y su lógica (el flujo de
fotos con el bug del 2026-10-19) es la única que todavía se verifica a mano;
la constitución pide toda historia con su test.

**Hecho cuando**: el test nuevo pasa a verde, `edit-bike.php` quedó en
~170 líneas sin lógica de fotos, `php -l` limpio y la suite completa verde.

## C. Arranque de página estándar (`src/page.php`)

**Qué hacer**

- Nuevo `src/page.php` con
  `page_start(bool $requireLogin = false): void` = `session_start_secure()`
  + `require_login_or_redirect()` cuando `$requireLogin` es true.
- Reemplazar en las páginas (cargar `page.php` desde `bootstrap.php`):
  - con login: `bike.php`, `bike_actions.php`, `edit-bike.php`,
    `my-bikes.php`, `publish.php` → `page_start(requireLogin: true);`
  - sólo sesión: `index.php`, `login.php`, `register.php`, `logout.php` →
    `page_start();`
- `public/api/bikes.php` queda fuera: no es una página con sesión.
- `session_start_secure()` y `require_login_or_redirect()` siguen existiendo
  por separado (los usan los tests y puede haber usos puntuales).

**Por qué**: una sola convención de arranque en vez de dos líneas que hay
que recordar y copiar; al crear una página nueva no se puede olvidar el
chequeo de login.

**Hecho cuando**: ninguna página HTML llama `session_start_secure()` a mano,
la suite verde sin tocar tests (VitrinaTest/FeedTest incluyen páginas) y
`php -l` limpio.

## D. Partir `public/css/style.css` (1.006 líneas) en 5 archivos

**Qué hacer — movimiento puro, sin cambiar una regla de estilo**

| Archivo nuevo | Qué lleva (secciones actuales de style.css) |
|---|---|
| `css/base.css` | 1 tokens `:root`, 2 base (tipografía, foco, body), 3 header/nav, 4 estructural (`.site-main`, `.card`, `.site-footer`), 8 flash |
| `css/components.css` | 5 formularios (campos, botones, `.error/.notice/.muted`), badges, `.empty-state` |
| `css/bikes.css` | 4 `.bike-card*` y 6 detalle (galería, meta, descripción) |
| `css/owner.css` | 7 acciones del dueño y recuadros de fotos + 9 (ajuste móvil de `.slot-remove`, **colocado junto a su componente**) |
| `css/vitrina.css` | 10 panel de filtros + 11 layout de 2 columnas y catálogo + centinela/estado del scroll |

- `views/header.php`: una etiqueta `<link>` por archivo, **en este orden**
  (el orden de los links es el orden de la cascada):
  `base → components → bikes → owner → vitrina`.
- Los tokens `:root` quedan **sólo** en `base.css` (una única fuente de
  verdad). Los `@media` viajan con su componente (mobile-first).
- `docs/design.md`: actualizar la frase "lo aplica `public/css/style.css`"
  con la lista de archivos.
- **No usar `@import`** (tren de espera de descargas que retrasa el
  pintado); múltiples `<link>` es la vía correcta sin build.
- `style.css` desaparece (los 5 archivos nuevos suman sus líneas).

**Por qué**: 1.006 líneas en un solo archivo ya cuesta de navegar; con
capas nombradas cada edición toca sólo su archivo y la caché del navegador
no invalida todo el CSS. Sin dependencias ni build: sólo etiquetas `<link>`.

**Hecho cuando**: la suma de líneas de los 5 archivos es 1.006 (movimiento
puro), el recorrido visual 375 px y 1280 px queda idéntico (sin scroll
horizontal), la suite sigue verde (no se assertea CSS) y `docs/design.md`
queda actualizado.

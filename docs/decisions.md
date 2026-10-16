# Decisiones de diseño

## 2026-10-15 — Etapa 3: decisiones de implementación
- **Paginado por id sobre orden por fecha**: el feed ordena `created_at DESC,
  id DESC` (como pide la spec) pero el cursor compara solo `id < último`; como
  los ids crecen junto con la fecha de creación, es equivalente y más simple.
  El test de 3 tandas lo prueba, y la semilla respeta esa correlación.
- **`db_connect()` con variable de entorno `BIKE_CENTER_DB`**: las páginas y el
  endpoint usan la conexión de la app; los tests las prueban apuntando esa
  variable a `bike_center_test` solo durante la llamada, sin tocar config.php.
- **Foto principal en la tarjeta de la vitrina** (pedido explícito del usuario
  tras completar la etapa; actualiza RF-4, H1 y tarea 3.3): la foto es la de
  posición 0 de la moto, decorativa (`alt=""`, el título identifica), con
  `loading="lazy"` para el H10 y `object-fit: cover` en 4:3 para no deformar.
  Las motos sin fotos (solo posible a mano) muestran la tarjeta sin imagen:
  misma guarda en PHP (`views/bike_card.php`) y en `feed.js`.
- **Carrusel de fotos en el detalle** (pedido del usuario; actualiza RF-5 y la
  tarea 3.5): una foto a la vez con botones ◀ ▶ y contador, navegación cíclica
  (de la última vuelve a la primera). Degradación sin JS: el servidor pinta
  todas las fotos visibles y `gallery.js` recién entonces oculta las inactivas
  y habilita los controles con la clase `.js`; con una sola foto no hay
  botones ni script. Teclado no incluido a propósito (alcance: botones).
- **`src/format.php`**: `format_price()` centraliza el formato de precio
  (punto de miles, dos decimales solo si hay fracción) y `feed.js` repite las
  mismas reglas para que las tarjetas por scroll coincidan con las de PHP.
- **Tarjetas por scroll con `textContent`**: `feed.js` arma las tarjetas con
  `createElement`/`textContent`, nunca `innerHTML`, para que un título con
  etiquetas HTML se muestre como texto literal también en el infinito.
- **Detalle sin sesión redirige a `/login.php`**, igual que publicar: el login
  ofrece crear cuenta, que es lo que pide el RF-5 ("registrarse o iniciar
  sesión") sin filtrar ningún dato.
- **`SET NAMES utf8mb4` al inicio de `sql/seed.sql`**: el cliente `mysql` de
  Docker se conecta en latin1 por defecto y los acentos se doble-codificaban
  al cargar; declararlo en el archivo hace la carga independiente del cliente.
- **La publicación redirige al detalle** de la moto creada (decisión quedada
  de la etapa 2, posible recién ahora que existe `bike.php`); el aviso de
  fotos rechazadas viaja en flash y se ve ahí.
- **Semilla con fechas crecientes por id**: `sql/seed.sql` asigna `created_at`
  horario consecutivo en el mismo orden de los ids para que el orden de la
  vitrina sea determinista y coincida con la lógica del cursor.

## 2026-10-15 — Etapa 2: decisiones de implementación
- **Orden del guardado en `publish.php`**: primero los datos, después las fotos
  y recién al final la transacción. Si los datos fallan no se toca ningún
  archivo; si una foto resulta inválida se publica con las válidas y se avisa;
  y si la transacción falla se borran del disco las fotos ya movidas. Así se
  cumple "datos inválidos → no se guarda nada" sin archivos huérfanos.
- **Flash de mensajes en la sesión**: como las acciones terminan en redirect
  (PRG), los avisos (por ejemplo, las fotos rechazadas) se guardan con
  `flash_set()` y se consumen una sola vez en `views/flash.php`, incluido por
  el encabezado de cada página.
- **Fotos repetidas por hash dentro de la publicación**: la comparación es por
  `sha1` del contenido entre las fotos del mismo envío; en la edición
  (etapa 4) se sumará la comparación contra las fotos ya guardadas de la moto.
- **Nombre de archivo generado por el servidor**: 32 hexadecimales aleatorios
  más la extensión del tipo real; el nombre que eligió el usuario nunca llega
  al disco (seguridad) y coleccionar nombres no permite adivinar rutas.
- **`save_photo()` acepta archivos no subidos por HTTP**: usa
  `move_uploaded_file()` en web y un `rename()` de fallback solo para los
  tests desde la línea de comandos; la validación con `finfo` y las reglas de
  peso y medidas son las mismas en ambos caminos.
- **Marca como texto libre**: la spec no la enumera, así que se validó como
  texto obligatorio de hasta 60 caracteres, el límite de la columna `brand`.
- **Redirección de la publicación a `/index.php` por ahora**: hasta que exista
  la página de detalle (etapa 3), el éxito del formulario manda a la portada
  con el aviso de flash; después se redirigirá al detalle de la moto creada.
- **`create_bike()` propaga la excepción**: el modelo deshace la transacción y
  relanza el error; decidir qué mostrar al usuario (y borrar los archivos del
  disco) le corresponde a la página, no al modelo.

## 2026-10-14 — Etapa 1: decisiones de implementación
- **`src/bootstrap.php`**: las páginas de `public/` arrancan con un único
  `require` que carga db, validation, session, csrf y auth; el orden de
  inclusión vive en un solo lugar y las páginas quedan de dos líneas de arranque.
- **`index.php` como placeholder**: hasta la etapa 3 la portada solo muestra
  quién está conectado; reemplaza a la vitrina cuando exista (RF-4).
- **Logout solo por POST con CSRF**: el botón del encabezado envía el token; un
  GET a `/logout.php` se ignora, así nadie puede cerrar la sesión de otro con un
  enlace.
- **`test_db()` y `beforeEach` en `tests/Pest.php`**: los tests de integración
  comparten la conexión a `bike_center_test` y cada test arranca con las tablas
  truncadas, sin recrear el esquema entero por test.
- **Encabezado sin consulta a la base**: la navegación depende solo de
  `current_user_id()`; el nombre del usuario se mostrará cuando haya una
  pantalla que lo necesite.

## 2026-10-14 — Etapa 0: decisiones de implementación
- **Composer sin autoload de producción**: `composer.json` no declara
  autoload; los archivos de `src/` se incluyen con `require_once` (los hace
  `tests/Pest.php`). Así la app corre sin `vendor/` y se respeta el principio 1.
- **Plugin de Pest autorizado**: `composer config allow-plugins.pestphp/pest-plugin true`,
  requisito de Composer 2.10 para instalar Pest.
- **`db_execute_schema()` parte el SQL por ";"**: PDO no corre varios SQL de una;
  el helper asume que no hay punto y coma dentro de comillas (el schema actual
  no lo tiene).
- **PHP 8.5 y la cookie de sesión**: `session_get_cookie_params()` ya no trae la
  clave `expires` cuando no hay vencimiento; el test asegura `lifetime = 0`,
  que expresa "la cookie muere al cerrar el navegador" (RF-2).

## 2026-10-14 — Revisión de QA de la spec 01
- **Protección CSRF**: aunque la spec no la pide, todos los formularios con
  sesión llevan un token simple (generado en la sesión y comparado con
  `hash_equals`); es defensa extra y buenas prácticas para un proyecto
  educativo.
- **Semilla de prueba**: `sql/seed.sql` con 1 usuario de prueba y 25 motos de
  mentira para verificar a mano el scroll infinito, el orden y la etiqueta de
  vendida.
- **Paginado por clave en el feed**: el scroll infinito pagina por `id` menor
  al último de la tanda, de modo que motos nuevas no duplican ni expulsan las
  ya vistas (cumple el RNF-3 sin perder estabilidad).
- **Teléfono en formato internacional**: en el formulario se piden 10 dígitos
  (ignorando espacios y guiones, sin "+" ni código de país) y en la base se
  guarda como "+54 11" seguido de esos 10 dígitos, el formato que usará el
  contacto por WhatsApp en una spec posterior.
- **Panel "Mis publicaciones"**: el dueño gestiona sus motos desde su propio
  listado; es el requisito que faltaba para que editar, borrar y vender tengan
  pantalla propia.
- **Fotos parciales**: si los campos están bien y hay al menos una foto
  válida, la publicación se crea con las válidas y se avisa el rechazo de las
  demás; en cambio, un fallo de conexión durante la subida no guarda nada.
- **Motos vendidas**: siguen en el listado con la etiqueta "vendida" y se
  pueden editar; el orden del listado es por fecha de creación y no cambia al
  editar.
- **Manejo de texto**: los títulos y descripciones se muestran como texto
  literal; si contienen HTML o scripts no se ejecutan.
- **Verificación manual**: los requisitos de dispositivos y rendimiento se
  comprueban con recorrido manual; los automatizados cubren lógica y datos.

## 2026-10-14 — Spec 01: decisiones de producto
- **Teléfono en el registro**: se pide un número de 10 dígitos desde la creación
  de la cuenta aunque no se muestre todavía; será la base del contacto entre
  comprador y vendedor en una spec posterior.
- **Propiedad sin mensajes**: las opciones de editar, borrar y cambiar el estado
  no existen para quien no es el dueño; no hay mensajes de "no tiene permiso".
- **Moneda por publicación**: cada moto declara si su precio está en pesos o en
  dólares; no hay conversión automática entre monedas.
- **Búsqueda y filtros pospuestos**: quedan fuera de la spec 01 para mantener el
  corte chico; entran en una funcionalidad siguiente.
- **Verificación de correo y recuperación de contraseña pospuestas**: se
  confirmará que existan en el producto, pero mandar correos agranda el primer
  corte.
- **Sesión corta**: la sesión termina al cerrar el navegador, además del cierre
  explícito.
- **Listado paginado con scroll infinito**: 20 motos por pantalla y más al
  llegar al final, sin recargar la página.

## 2026-10-14 — Framework de tests: Pest
Se eligió Pest en lugar de PHPUnit porque su sintaxis mínima es más legible para un desarrollador junior y, al correr sobre PHPUnit, el conocimiento transferible.

## 2026-10-14 — Excepción al stack simple: Composer
La app no usa frameworks ni librerías en runtime, pero Composer queda permitido como dependencia de desarrollo exclusiva (`require-dev`), con `vendor/` fuera de Git. Es la única excepción al principio 1.

## 2026-10-14 — Base de datos de tests
Los tests que tocan la BD usarán una base separada `bike_center_test`, recreada desde el schema en cada tanda, para no pisar los datos de desarrollo (`bike_center`).

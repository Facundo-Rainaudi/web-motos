# Decisiones de diseño

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

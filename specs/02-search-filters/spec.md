# Spec 02 — Bike Center: búsqueda y filtros en la vitrina

## 1. Contexto y objetivo
La spec 01 construyó la vitrina mostrando todo el listado ordenado por fecha y
dejó explícitamente afuera "la búsqueda y los filtros por marca, estado o
precio" (spec 01, sección 7), aunque la descripción del proyecto (AGENTS.md)
siempre los prometió. Esta funcionalidad los incorpora.
Objetivo: que un comprador acote el listado público de motos (RF-4 de la spec
01) por texto (título o marca) y por atributos (condición, moneda, rango de
precio y disponibilidad), y que esa selección sea visible, limpiable de un
click y compartible por URL, sin romper el cargado por scroll (RNF-3).
La búsqueda y los filtros aplican **solo a la vitrina pública**: no tocan "Mis
publicaciones" (RF-9), el detalle (RF-5) ni ninguna otra regla de la spec 01,
que sigue vigente tal cual. El orden del listado también se mantiene: por
fecha de creación, más reciente primero (RF-4); no se agrega ningún orden
alternativo (decisión del 2026-10-20).

## 2. Usuarios
Los mismos que la spec 01. La búsqueda y los filtros son públicos: los usa
cualquier visitante, con o sin sesión, desde la vitrina.

## 3. Historias de usuario
- H1: Como comprador quiero buscar por título o marca para encontrar rápido la
  moto que me interesa.
- H2: Como comprador quiero filtrar por estado, moneda, rango de precio y
  disponibilidad para acotar el listado a lo que me sirve.
- H3: Como comprador quiero ver cuántos resultados hay, limpiar los filtros de
  un click y que la carga por scroll siga respetando mi selección.

## 4. Requisitos funcionales (criterios en notación EARS)

### RF-11 Búsqueda por texto
- DADO cualquier visitante, CUANDO escribe un texto en el campo de búsqueda de
  la vitrina y lo aplica, ENTONCES el listado muestra solo las motos cuyo
  título o marca contengan ese texto, sin distinguir mayúsculas ni acentos (por
  ejemplo, "guzzi" encuentra "Moto Guzzi").
- DADO un texto vacío o compuesto solo por espacios, ENTONCES no altera el
  listado: se muestra el listado completo del RF-4.
- DADA una búsqueda sin coincidencias, ENTONCES el sistema muestra un mensaje
  específico de "sin resultados" junto con la opción de limpiar la búsqueda,
  distinto del mensaje de "no hay publicaciones" del RF-4.
- DADO un texto de búsqueda, EL SISTEMA DEBE usarlo como está en la URL
  (compartible y marcable) y devolverlo al campo de búsqueda como texto
  literal, sin interpretar etiquetas HTML ni scripts.
- DADO un texto de más de 80 caracteres, ENTONCES se recorta a 80 y se aplica
  igual.

### RF-12 Filtros
- DADO cualquier visitante, EL SISTEMA DEBE poder filtrar la vitrina por
  condición (nueva o usada), moneda (pesos o dólares), precio mínimo, precio
  máximo y disponibilidad (todas las motos o solo las disponibles), combinables
  entre sí y con la búsqueda del RF-11.
- DADO el filtro de disponibilidad sin elegir, ENTONCES el listado se comporta
  como hasta ahora: se muestran todas las motos y las vendidas llevan su
  etiqueta (RF-4 intacto).
- DADO un valor de filtro fuera de las opciones válidas (por ejemplo,
  condition=banana), un precio mínimo o máximo no numérico, negativo o con más
  de dos decimales, o un rango invertido (mínimo mayor que máximo), ENTONCES el
  sistema **ignora ese filtro** y muestra el listado sin él; una URL con basura
  nunca rompe la pantalla ni muestra un error.
- DADOS precios cargados con coma o punto como separador decimal, ENTONCES se
  aceptan igual que al publicar la moto (RF-3).
- DADO un resultado filtrado, ENTONCES cada tarjeta se ve igual que en el RF-4:
  foto principal, título, marca, precio con su moneda y estado.

### RF-13 Resultado, limpieza y scroll
- DADO un listado con búsqueda o filtros activos, ENTONCES se muestra un
  contador de resultados ("N motos encontradas"), con singular y plural
  correctos; sin selección activa no se muestra contador.
- DADO un listado con selección activa, ENTONCES se ofrece un botón "Limpiar
  filtros" que vuelve al listado completo del RF-4 y limpia la URL.
- DADA una selección activa, CUANDO se recarga la página o se comparte la URL,
  ENTONCES la vitrina se abre con esa misma selección aplicada.
- DADA una selección activa, MIENTRAS el visitante llega al final del listado,
  EL SISTEMA DEBE cargar por scroll solo motos que cumplan la misma selección,
  manteniendo la garantía del RNF-3: ninguna moto se repite ni se pierde.
- DADO un resultado sin motos, ENTONCES no se muestra el centinela de scroll ni
  se piden más tandas.
- DADA una vitrina sin ninguna publicación (sitio recién creado), ENTONCES se
  mantiene el mensaje del RF-4 que invita a publicar la primera, con prioridad
  sobre el estado vacío de "sin resultados".

## 5. Requisitos no funcionales (se suman a los de la spec 01)
- RNF-7 Selección por URL: los criterios viajan en la URL como parámetros
  legibles y con valores en los mismos términos que la base (por ejemplo,
  condition=new|used, currency=ARS|USD, status=available); el servidor los
  valida con lista blanca y topes antes de usarlos. Ningún parámetro controla
  la cantidad de motos por tanda, que sigue siendo 20 de código (RNF-3).
- RNF-8 Seguridad de la búsqueda: el texto y los filtros llegan por GET, se
  validan en el servidor y toda consulta usa PDO con consultas preparadas,
  nunca concatenando valores; lo que se vuelve a mostrar en la página va
  escapado.
- RNF-9 Rendimiento de los filtros: filtrar usa índices en la tabla bikes
  (se agregan a sql/schema.sql sobre los ya existentes); cada tanda filtrada se
  resuelve en el mismo margen del RNF-3 (20 motos en menos de 2 segundos). En
  desarrollo los índices nuevos se aplican con ALTER sobre la base viva, sin
  recargar el esquema (recargar sql/schema.sql vacía las tablas); el esquema de
  tests se recarga entero como hasta ahora.

## 6. Casos límite
- Texto vacío o solo espacios → se ignora y se ve el listado completo.
- Texto de más de 80 caracteres → se recorta a 80.
- Texto con etiquetas HTML o scripts → se busca como texto y se muestra
  escapado.
- Búsqueda sin coincidencias → mensaje específico de "sin resultados" con
  limpiar (no es el de "no hay publicaciones").
- Cualquier valor inválido de filtro (fuera de lista blanca, precio no
  numérico, negativo o con más de 2 decimales, mínimo mayor que máximo) → se
  ignora ese filtro y el resto de la selección sigue aplicada.
- Combinar búsqueda con todos los filtros a la vez → funciona y el contador
  refleja el resultado final.
- Filtro "solo disponibles" → excluye las vendidas; sin él, las vendidas se
  siguen mostrando con su etiqueta.
- Filtrar en un sitio sin ninguna publicación → se ve el mensaje del RF-4, no
  el de "sin resultados".
- Resultado filtrado corto (menos de 20 motos) → el scroll no pide tandas de
  más y el contador no cambia al bajar.
- Publicar una moto nueva mientras alguien recorre un listado filtrado → no
  duplica ni expulsa a las motos ya vistas; el listado filtrado baja igual de
  ordenado (más reciente primero).
- Vitrina abierta sin parámetros → se ve exactamente igual que hoy (regresión
  del RF-4).

## 7. Fuera de alcance (de esta funcionalidad)
- Orden por precio u otro criterio: el listado siempre va por fecha de
  creación (decisión del 2026-10-20; el orden por precio habría exigido
  paginar por clave sobre la tupla (precio, id) y se descartó por ahora).
- Contacto comprador-vendedor (botón de WhatsApp): sigue pospuesto a una spec
  posterior, con el teléfono ya recolectado desde la spec 01.
- Búsqueda dentro de la descripción, autocompletado, sugerencias, búsquedas
  guardadas y alertas.
- Filtros por año, cilindrada, kilometraje u otros atributos: esos campos no
  existen en la publicación.
- Filtrar o buscar dentro de "Mis publicaciones" (RF-9).
- Perfil editable, recuperación de contraseña, administrador/moderación y
  valoraciones (pendientes desde la spec 01).

## 8. Criterios de finalización
- Los criterios del RF-11, RF-12 y RF-13 están recorridos y en verde sobre la
  app corriendo en local, incluida la combinación filtros + scroll.
- La política de tests de la constitución se cumple: el parseo de parámetros y
  la consulta filtrada tienen sus tests automatizados en verde sobre
  bike_center_test; lo visual (375 px y 1280 px, sin scroll horizontal) se
  verifica con recorrido manual siguiendo la skill web-design.
- La constitución se respeta: ninguna vista contiene SQL, la cantidad de motos
  por tanda no la controla el usuario y toda consulta filtra con PDO
  preparado.
- El RF-4 original no se altera: sin parámetros en la URL, la vitrina se ve
  exactamente igual que hoy.
- El cambio queda registrado en docs/changelog.md y las decisiones en
  docs/decisions.md.

## 9. Dudas abiertas
Sin dudas abiertas. Resueltas el 2026-10-20 con el usuario:
- Filtro de disponibilidad incluido, con "todas" por defecto para no alterar
  el RF-4.
- Orden del listado: solo por más nuevo; el orden por precio (y su paginado
  por tupla (precio, id)) queda descartado por ahora.
- Contador de resultados visible, solo cuando hay selección activa.
- Esquema: se agregan índices para los filtros; en desarrollo se aplican con
  ALTER para no borrar los datos existentes.

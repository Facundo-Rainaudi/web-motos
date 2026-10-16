# Spec 01 — Bike Center: publicar y vender motos

## 1. Contexto y objetivo
Bike Center es un sitio de compra y venta de motos entre personas. Esta primera
funcionalidad construye la base del producto: cualquier persona se registra,
publica las motos que vende con datos y fotos, y cualquier visitante (aún sin
cuenta) recorre la vitrina para decidir si le interesa algo.
Objetivo: que un vendedor publique una moto de punta a punta y que un comprador
descubra y evalúe las motos publicadas. El contacto entre comprador y vendedor
no forma parte de esta funcionalidad, pero el registro ya pide el teléfono del
vendedor, que se guarda en formato internacional (+54 11 seguido de los 10
dígitos) como dato de partida para el contacto por WhatsApp de una spec
posterior.

## 2. Usuarios
- Visitante: sin cuenta. Ve el listado resumido de motos. Para ver el detalle
  completo o publicar, debe registrarse.
- Usuario registrado: publica, edita, borra y marca como vendidas sus motos, ve
  el detalle completo de cualquier moto y tiene su propio listado de motos
  propias ("Mis publicaciones") para gestionarlas.
- Dueño de una publicación: el registrado que la creó; es el único que puede
  modificarla, eliminarla o venderla.
No existe el rol de administrador ni moderación en esta funcionalidad.

## 3. Historias de usuario
- H1: Como visitante quiero ver el listado de motos con foto, título, marca y
  precio para decidir si me interesa alguna y si vale la pena registrarme.
- H2: Como visitante quiero crear una cuenta con nombre completo, correo,
  contraseña y teléfono para poder publicar y ver el detalle de las motos.
- H3: Como usuario registrado quiero iniciar y cerrar sesión para trabajar con
  mi cuenta.
- H4: Como vendedor quiero publicar una moto con título, marca, estado (nueva o
  usada), precio, moneda, descripción y hasta 4 fotos para que los interesados
  la conozcan completa.
- H5: Como comprador registrado quiero ver el detalle completo de una moto, con
  todas sus fotos, para evaluar si me interesa.
- H6: Como vendedor quiero editar datos y fotos de mi publicación para corregir
  errores o actualizar la información.
- H7: Como vendedor quiero marcar mi moto como vendida y poder volver a dejarla
  disponible para gestionar su estado.
- H8: Como vendedor quiero borrar mi publicación para retirarla del sitio.
- H9: Como vendedor quiero ver el listado de mis publicaciones para editarlas,
  borrarlas o gestionar su estado desde un solo lugar.
- H10: Como visitante o comprador quiero recorrer la vitrina desde el celular y
  que las motos se carguen rápido y de a poco, para evaluarlas sin esperas.

## 4. Requisitos funcionales (criterios en notación EARS)

### RF-1 Registro
- DADA la pantalla de registro, CUANDO el visitante envía un nombre completo de
  al menos 3 caracteres, un correo, una contraseña de al menos 8 caracteres y
  un teléfono de exactamente 10 dígitos, ENTONCES el sistema crea la cuenta,
  guarda el teléfono en formato internacional (+54 11 seguido de los 10
  dígitos informados) y lo deja con sesión iniciada.
- DADA una dirección de correo ya registrada, SI el visitante intenta
  registrarse con ese correo, ENTONCES el sistema no crea la cuenta y le
  informa el motivo. El correo se guarda y se compara en minúsculas y sin
  espacios, de modo que "Usuario@x.com" y "usuario@x.com" son la misma cuenta.
- DADO un correo con formato inválido, una contraseña menor a 8 caracteres o un
  nombre completo de menos de 3 caracteres, CUANDO el visitante envía el
  formulario, ENTONCES el sistema no crea la cuenta y le indica qué dato
  corregir.
- DADO un teléfono que no tenga exactamente 10 dígitos numéricos, SI el
  visitante envía el formulario, ENTONCES el sistema no crea la cuenta y le
  indica que el teléfono debe tener 10 dígitos. Al tipear se ignoran espacios y
  guiones; no se admite "+" ni código de país.

### RF-2 Sesión
- DADO un correo y una contraseña correctos, CUANDO el usuario inicia sesión,
  ENTONCES el sistema lo deja identificado para publicar y ver detalles.
- DADO un correo o una contraseña incorrectos, CUANDO el usuario intenta
  iniciar sesión, ENTONCES el sistema no lo deja entrar y muestra un mensaje
  que no revele cuál de los dos datos está mal (a diferencia del registro, que
  sí informa si el correo ya existe, por comodidad del usuario legítimo).
- MIENTRAS el usuario tiene sesión iniciada, EL SISTEMA DEBE permitirle
  publicar, gestionar sus motos y ver el detalle de cualquier moto.
- DADA una sesión iniciada, CUANDO el usuario cierra sesión, ENTONCES deja de
  tener acceso a esas acciones y el detalle completo vuelve a estar reservado.
- DADA una sesión iniciada, CUANDO el usuario cierra la aplicación del
  navegador, ENTONCES la sesión termina y debe volver a iniciar sesión para
  continuar; cerrar una sola pestaña no termina la sesión.
- DADA una sesión terminada, SI el usuario vuelve a una página que ya tenía
  abierta, ENTONCES cualquier acción que intente ejecutar pide iniciar sesión
  de nuevo.
- DADA una sesión abierta en varios dispositivos, CUANDO el usuario cierra
  sesión en uno de ellos, ENTONCES las demás sesiones siguen activas.

### RF-3 Publicar una moto
- DADA una sesión iniciada, CUANDO completa el formulario con título, marca,
  estado, precio, moneda (pesos o dólares), descripción y al menos una foto,
  ENTONCES la moto queda publicada a nombre de su cuenta y aparece en la
  vitrina. El precio admite coma o punto como separador decimal y hasta 2
  decimales; no se admite el separador de miles.
- DADO un campo obligatorio vacío, un precio menor o igual a cero, un precio
  con más de 2 decimales, un título de más de 80 caracteres o una descripción
  de más de 2.000 caracteres, SI el vendedor intenta publicar, ENTONCES no se
  guarda nada y se le indica qué corregir.
- DADA una sesión iniciada, SI intenta subir más de 4 fotos o una que no sea
  una imagen válida (solo JPEG, PNG o WEBP de hasta 2 MB inclusive y al menos
  200×200 px) o repetida dentro de la misma publicación (mismo contenido de
  archivo, incluidas las fotos ya guardadas en ediciones anteriores), ENTONCES
  el sistema crea la publicación con las fotos válidas, rechaza las demás y le
  informa el motivo.
- DADA una sesión iniciada, SI la subida de una foto falla por un problema de
  conexión, ENTONCES no se guarda la publicación y se le pide reintentar.
- DADA una publicación creada, SU DUEÑO DEBE ser la única cuenta que pueda
  editarla, borrarla o venderla.

### RF-4 Listado público (vitrina)
- DADO cualquier visitante, EL SISTEMA DEBE mostrar el listado con la foto
  principal, título, marca, precio (con su moneda) y estado de las motos
  publicadas, ordenado por fecha de creación, más reciente primero; editar una
  publicación no altera su posición.
- DADA una moto marcada como vendida, ENTONCES el listado la sigue mostrando,
  con la etiqueta "vendida" visible en lugar de la de disponible.
- DADA una moto borrada por su dueño, ENTONCES desaparece del listado y su
  detalle deja de estar disponible.
- DADA una vitrina sin motos publicadas, EL SISTEMA DEBE mostrar un mensaje
  que invite a publicar la primera.

### RF-5 Detalle de la moto
- DADA una sesión iniciada, CUANDO abre el detalle de una moto, ENTONCES ve
  título, marca, estado, precio con su moneda, descripción, todas sus fotos
  (una a la vez, con botones para pasar de una a otra), el nombre del vendedor
  y la fecha de publicación.
- DADO un visitante sin sesión, SI intenta abrir el detalle de una moto,
  ENTONCES el sistema le pide registrarse o iniciar sesión sin filtrar ningún
  dato completo.
- DADA una moto inexistente o borrada, SI un usuario intenta abrirla, ENTONCES
  el sistema muestra un mensaje de "no disponible" sin filtrar ningún dato.
- DADO un título o una descripción que contenga etiquetas HTML o scripts,
  CUANDO el sitio los muestra, ENTONCES los presenta como texto literal, sin
  ejecutarlos.

### RF-6 Editar la publicación
- DADO el dueño con sesión iniciada, CUANDO edita su publicación y guarda,
  ENTONCES los cambios quedan reflejados en la vitrina.
- MIENTRAS se edita una publicación, EL SISTEMA DEBE aplicar las mismas reglas
  de validación que al publicar.
- DADO el dueño, MIENTRAS edita, EL SISTEMA DEBE permitirle agregar y quitar
  fotos de a una, sin bajar de una foto ni pasar de cuatro.
- DADO el dueño, EL SISTEMA DEBE permitirle editar también una moto que ya
  está marcada como vendida.
- DADA una publicación que no es propia, EL SISTEMA NO DEBE ofrecer la opción
  de editarla.

### RF-7 Borrar la publicación
- DADO el dueño, CUANDO borra su publicación, ENTONCES la moto y sus fotos
  desaparecen del listado y del detalle para todos los usuarios, y él vuelve a
  su listado de "Mis publicaciones".
- DADA una publicación que no es propia, EL SISTEMA NO DEBE ofrecer la opción
  de borrarla.

### RF-8 Marcar como vendida
- DADO el dueño, CUANDO marca su moto como vendida, ENTONCES la vitrina la
  muestra con la etiqueta "vendida" y deja de aparecer como disponible.
- DADO el dueño, CUANDO quita el estado de vendida de su moto, ENTONCES la
  vitrina vuelve a mostrarla como disponible.
- DADO el dueño, SI vuelve a marcar como vendida una moto ya vendida, ENTONCES
  el sistema no produce ningún cambio ni muestra un error.
- DADA una publicación que no es propia, EL SISTEMA NO DEBE ofrecer la opción
  de cambiar su estado.

### RF-9 Mis publicaciones
- DADA una sesión iniciada, CUANDO el usuario abre su listado de motos,
  ENTONCES ve solo las publicaciones que él creó, con sus datos resumidos y su
  estado, y desde ahí puede llegar a editar, borrar o cambiar el estado de
  cada una.
- DADA una sesión iniciada sin motos publicadas, CUANDO abre su listado,
  ENTONCES ve un mensaje que lo invite a publicar la primera.
- DADO un visitante sin sesión, SI intenta abrir la URL de "Mis publicaciones",
  ENTONCES el sistema le pide registrarse o iniciar sesión.

### RF-10 Regla de propiedad
- DADA cualquier publicación, EL SISTEMA DEBE permitir editar, borrar y cambiar
  el estado solo al dueño que la creó; para cualquier otra cuenta, esas opciones
  no existen y no hay mensajes de permisos.

## 5. Requisitos no funcionales
- RNF-1 Idioma: toda la interfaz y los mensajes al usuario están en español.
  Este requisito es transversal: aplica a todas las pantallas.
- RNF-2 Dispositivos: vitrina, detalle, "Mis publicaciones" y formulario de
  publicación son usables desde celular y escritorio, sin instalar nada.
- RNF-3 Rendimiento y paginado: el listado muestra 20 motos por pantalla y
  carga 20 más automáticamente al llegar al final (scroll infinito), sin
  recargar la página; cada tanda se muestra en menos de 2 segundos. Una moto no
  puede aparecer dos veces ni perderse aunque se publiquen nuevas mientras se
  baja.
- RNF-4 Cuenta y privacidad: la contraseña nunca se muestra en pantalla ni
  puede leerse desde el sitio; el teléfono del vendedor se guarda desde el
  registro en formato +54 11 seguido de los 10 dígitos, pero no se muestra en
  ninguna parte de esta funcionalidad.
- RNF-5 Imágenes: se aceptan solo JPEG, PNG y WEBP de hasta 2 MB inclusive por
  foto, con al menos 200×200 px, un máximo de 4 y un mínimo de 1; otro archivo
  se rechaza sin afectar el resto de la publicación.
- RNF-6 Moneda: cada publicación declara la moneda de su precio (pesos o
  dólares) y el precio se muestra siempre en esa moneda, sin conversión.

## 6. Casos límite
- Correo ya registrado al crear cuenta → no se crea y se avisa; se compara en
  minúsculas y sin espacios.
- Contraseña menor a 8 caracteres, o vacía o solo espacios → se rechaza.
- Nombre completo de menos de 3 caracteres, o título/descripción en blanco →
  se rechaza indicando el dato.
- Título de más de 80 caracteres o descripción de más de 2.000 → no se guarda.
- Teléfono con menos o más de 10 dígitos, con letras o con "+" → no se crea la
  cuenta; espacios y guiones se ignoran; se guarda como +54 11 seguido de los
  10 dígitos.
- Precio 0, negativo, no numérico o con más de 2 decimales → no se guarda.
- Subir más de 4 fotos, un formato no admitido, una menor a 200×200 o una
  repetida en la misma publicación → se rechaza esa foto y se publica con las
  válidas, avisando el motivo.
- Fallo de conexión durante la subida de fotos → no se guarda la publicación y
  se pide reintentar.
- Intentar dejar una moto sin fotos → no se permite; siempre tiene al menos una.
- Editar, borrar o vender una moto ajena → la opción no existe para esa cuenta.
- Editar una moto vendida → permitido.
- Borrar una publicación → sus fotos también desaparecen.
- Dos gestiones simultáneas sobre la misma moto → queda el último cambio
  guardado; marcar vendida dos veces deja un solo estado.
- Visitante que abre un detalle o la URL de "Mis publicaciones" sin sesión → se
  le pide registrarse; ningún dato completo se filtra.
- Detalle de una moto inexistente o borrada → mensaje de "no disponible".
- Texto con etiquetas HTML o scripts en título o descripción → se muestra como
  texto literal.
- Sesión perdida al cerrar el navegador mientras se completa el formulario →
  se avisa que no se guardó y se pide iniciar sesión de nuevo.
- Botón "atrás" después de cerrar sesión → la página puede verse, pero
  cualquier acción pide iniciar sesión de nuevo.
- Sesión abierta en dos dispositivos → cerrarla en uno no la cierra en el otro.
- "Mis publicaciones" sin motos → mensaje que invite a publicar la primera.
- Llegar al final del listado sin más motos → el scroll infinito se detiene.
- Motos nuevas publicadas mientras el visitante baja → no duplican ni expulsan
  motos ya vistas.

## 7. Fuera de alcance (de esta funcionalidad; van en specs siguientes)
- Búsqueda y filtros por marca, estado o precio: se posponen a una spec
  posterior por decisión del 2026-10-14.
- Contacto comprador-vendedor: botón de WhatsApp, mensajes internos y muestra
  del teléfono del vendedor (el dato ya se recolecta y guarda en formato
  internacional en RF-1).
- Recuperación de contraseña y verificación de correo: confirmado que existirán
  en el producto, en una funcionalidad posterior.
- Administrador, moderación y bloqueo de usuarios.
- Control de publicaciones masivas (spam): un usuario puede publicar sin
  límite en esta funcionalidad.
- Perfil editable (avatar, bio, cambiar contraseña o teléfono).
- Valoraciones, opiniones, favoritos y comparador.
- Regla anti-duplicados entre publicaciones de usuarios distintos.
- Publicar más de una unidad de la misma moto: cada publicación es una moto.
- Aplicación móvil, notificaciones y envío de correos.

## 8. Criterios de finalización
- Todos los RF tienen sus criterios de aceptación recorridos y en verde sobre
  la app corriendo en local.
- La política de tests de la constitución se cumple: toda la lógica y los
  datos tienen sus tests automatizados en verde (validaciones, propiedad,
  paginado de 20 en 20 y reglas de imágenes); RNF-2 y RNF-3 se verifican con
  recorrido manual sobre celular y escritorio.
- La constitución se respeta: ninguna vista contiene SQL, y la pertenencia de
  una publicación se verifica antes de cualquier cambio, aun cuando la opción
  no se muestre en la interfaz.
- No hay código sin historia de usuario que lo respalde, y el código coincide
  con esta spec.
- Ninguna acción sobre una moto ajena es posible, aun intentándola por la
  interfaz directamente.

## 9. Dudas abiertas
Sin dudas abiertas. Además de las resueltas el 2026-10-14 (búsqueda y filtros
pospuestos; moneda por publicación sin conversión; verificación de correo y
recuperación de contraseña para una spec posterior; sesión que se corta al
cerrar la aplicación del navegador; imágenes repetidas no admitidas; moto
vendida que vuelve a estar disponible; límites de fotos confirmados; listado de
20 motos con scroll infinito), la revisión de QA del mismo día agregó: panel
"Mis publicaciones", reglas de longitud y formato de todos los campos, teléfono
de 10 dígitos guardado como +54 11 para el contacto futuro, fotos que se borran
con su publicación, texto HTML mostrado como literal y verificación manual de
los requisitos visuales y de rendimiento.

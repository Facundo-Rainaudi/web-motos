# Requisitos - bike-center

## Qué es
Sitio web donde cualquier usuario registrado puede publicar motos nuevas
o usadas para vender, y ver las de otros usuarios con filtros de búsqueda.
El comprador contacta al vendedor por WhatsApp.

## Tipos de usuario
- Visitante: ve el listado, filtra y ve el detalle de cada moto. Pero no puede interactuar con las publicaciones, debe iniciar sesion si o si.
- Usuario: publica, edita y borra sus propias motos, y puede
  contactar a los vendedores.
- Admin: elimina publicaciones inapropiadas y bloquea usuarios.

## Funcionalidades (obligatorias)
- [ ] Registro e inicio de sesión (el teléfono se valida y se guarda
      en formato internacional)
- [ ] Listado de motos con filtros (tipo, marca, condición, rango de precio)
- [ ] Orden del listado por precio
- [ ] Detalle de una moto
- [ ] Publicar moto (fotos, características, precio y descripción del vendedor)
- [ ] Editar, borrar y marcar como vendida las propias motos
- [ ] Contacto: el detalle de la moto tiene un botón "Contactar por WhatsApp"
      que abre un chat directo a WhatsApp del vendedor (solo para usuarios logueados).
- [ ] Panel de admin (eliminar publicaciones, bloquear usuarios)
- [ ] Paginación del listado

## Fuera de alcance
- Pagos
- Favoritos
- Mensajería privada dentro del sitio
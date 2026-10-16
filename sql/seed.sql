-- sql/seed.sql — datos de prueba de la vitrina (etapa 3, soporte de RF-4).
--
-- Cómo cargarlo (en este orden):
--   1) docker compose exec -T db mysql -uroot -proot bike_center < sql/schema.sql
--   2) cp tests/fixtures/valid_200x200.jpg public/uploads/seed_01.jpg
--      cp tests/fixtures/valid_300x300.png public/uploads/seed_02.png
--      cp tests/fixtures/valid_250x250.webp public/uploads/seed_03.webp
--      cp tests/fixtures/valid_400x400.jpg public/uploads/seed_04.jpg
--   3) docker compose exec -T db mysql -uroot -proot bike_center < sql/seed.sql
--
-- Contenido: 4 usuarios y 27 motos (26 disponibles y 1 vendida, la id 9),
-- todas las fotos apuntando a los archivos copiados del paso 2. Las fechas de
-- creación son crecientes respecto de los ids, igual que en producción.
-- Contraseña de todos los usuarios: moto2026

-- Carga en UTF-8: sin esto, un cliente que se conecte en latin1 (el default
-- del mysql CLI sin locale) doble-codificaría los acentos de nombres y títulos.
SET NAMES utf8mb4;

INSERT INTO users (id, full_name, email, password_hash, phone) VALUES
    (1, 'Ana Torres', 'ana@bike.test', '$2y$12$dQ6ZcI1Zyq9Tc/DujLR2G.D4mviNHjxCLVKM2uh4dCtwYFtHOfS8G', '+54 1155112233'),
    (2, 'Bruno Giménez', 'bruno@bike.test', '$2y$12$dQ6ZcI1Zyq9Tc/DujLR2G.D4mviNHjxCLVKM2uh4dCtwYFtHOfS8G', '+54 1166223344'),
    (3, 'Carla Ríos', 'carla@bike.test', '$2y$12$dQ6ZcI1Zyq9Tc/DujLR2G.D4mviNHjxCLVKM2uh4dCtwYFtHOfS8G', '+54 1177334455'),
    (4, 'Diego Santos', 'diego@bike.test', '$2y$12$dQ6ZcI1Zyq9Tc/DujLR2G.D4mviNHjxCLVKM2uh4dCtwYFtHOfS8G', '+54 1188445566');

INSERT INTO bikes (id, user_id, title, brand, `condition`, price, currency, description, status, created_at) VALUES
    (1, 1, 'Yamaha FZ 2.0', 'Yamaha', 'used', 2400.00, 'USD', 'Moto urbana, poco kilómetro, lista para rodar.', 'available', '2026-09-20 10:00:00'),
    (2, 1, 'Honda CB 190R', 'Honda', 'used', 2100.00, 'USD', 'Titular original, service al día.', 'available', '2026-09-20 11:00:00'),
    (3, 1, 'Zanella ZR 200', 'Zanella', 'new', 1800000.00, 'ARS', 'Cero kilómetro con garantía de fábrica.', 'available', '2026-09-20 12:00:00'),
    (4, 1, 'Suzuki Gixxer 250', 'Suzuki', 'used', 3200.00, 'USD', 'Muy bien cuidada, cubiertas nuevas.', 'available', '2026-09-20 13:00:00'),
    (5, 1, 'Kawasaki Ninja 300', 'Kawasaki', 'used', 4500.00, 'USD', 'Seminueva, ideal para ciudad y ruta.', 'available', '2026-09-20 14:00:00'),
    (6, 1, 'Gilera Smash 110', 'Gilera', 'used', 750000.00, 'ARS', 'Económica y confiable, muy poco consumo.', 'available', '2026-09-20 15:00:00'),
    (7, 1, 'Bajaj Pulsar NS 200', 'Bajaj', 'used', 1900.00, 'USD', 'Se entrega con llantas trasera y delanteras nuevas.', 'available', '2026-09-20 16:00:00'),
    (8, 2, 'Yamaha NMAX 160', 'Yamaha', 'new', 5800.00, 'USD', 'Maxi scooter cero kilómetro, con freno ABS.', 'available', '2026-09-20 17:00:00'),
    (9, 2, 'Honda Titan 160', 'Honda', 'used', 1450000.00, 'ARS', 'Vendida rápido: publicada como ejemplo de estado vendida.', 'sold', '2026-09-20 18:00:00'),
    (10, 2, 'Royal Enfield Himalayan', 'Royal Enfield', 'used', 5200.00, 'USD', 'Aventurera, lista para viajar con accesorios puestos.', 'available', '2026-09-20 19:00:00'),
    (11, 2, 'KTM Duke 200', 'KTM', 'used', 3300.00, 'USD', 'Ágil y divertida, poco uso.', 'available', '2026-09-20 20:00:00'),
    (12, 2, 'Yamaha FZ 2.0 Full', 'Yamaha', 'used', 2300.00, 'USD', 'Financiación <b>en 6 cuotas</b>. Prueba de HTML: <script>alert("xss")</script> debe verse como texto.', 'available', '2026-09-20 21:00:00'),
    (13, 2, 'Zanella Patagonian 250', 'Zanella', 'used', 1650000.00, 'ARS', 'Clásica y robusta, ideal para ruta.', 'available', '2026-09-20 22:00:00'),
    (14, 2, 'Suzuki AX 100', 'Suzuki', 'new', 1250000.00, 'ARS', 'Nuevo modelo, stock disponible.', 'available', '2026-09-20 23:00:00'),
    (15, 3, 'Honda Wave 110', 'Honda', 'used', 890000.00, 'ARS', 'Práctica para ciudad, anduvo poco.', 'available', '2026-09-21 00:00:00'),
    (16, 3, 'Kawasaki Versys 300', 'Kawasaki', 'used', 5400.00, 'USD', 'Touring liviana, con baúl incluido.', 'available', '2026-09-21 01:00:00'),
    (17, 3, 'Triumph Street 400', 'Triumph', 'new', 7900.00, 'USD', 'Estreno de modelo, con garantía.', 'available', '2026-09-21 02:00:00'),
    (18, 3, 'Bajaj Boxer 150', 'Bajaj', 'used', 980000.00, 'ARS', 'Moto de trabajo, excelente estado mecánico.', 'available', '2026-09-21 03:00:00'),
    (19, 3, 'Yamaha XTZ 250', 'Yamaha', 'used', 3600.00, 'USD', 'Doble uso, comoda en ripio.', 'available', '2026-09-21 04:00:00'),
    (20, 3, 'Gilera Sierra 200', 'Gilera', 'used', 1150000.00, 'ARS', 'Recién service de 10.000 km.', 'available', '2026-09-21 05:00:00'),
    (21, 3, 'Honda CRF 300', 'Honda', 'new', 6800.00, 'USD', 'Off road nueva, lista para el campo.', 'available', '2026-09-21 06:00:00'),
    (22, 4, 'Zanella Ceccato 175', 'Zanella', 'used', 1050000.00, 'ARS', 'Motor recién reconstruido, anda perfecto.', 'available', '2026-09-21 07:00:00'),
    (23, 4, 'Suzuki V-Strom 250', 'Suzuki', 'used', 4100.00, 'USD', 'De viaje, con protector de motor.', 'available', '2026-09-21 08:00:00'),
    (24, 4, 'KTM RC 200', 'KTM', 'used', 3900.00, 'USD', 'Deportiva poco usada, cubiertas con poco desgaste.', 'available', '2026-09-21 09:00:00'),
    (25, 4, 'Yamaha MT-03', 'Yamaha', 'new', 6200.00, 'USD', 'Naked nueva, últimos modelos.', 'available', '2026-09-21 10:00:00'),
    (26, 4, 'Honda XR 125', 'Honda', 'used', 1350000.00, 'ARS', 'Muy liviana, perfecta para empezar.', 'available', '2026-09-21 11:00:00'),
    (27, 4, 'Royal Enfield Bullet 350', 'Royal Enfield', 'used', 5900.00, 'USD', 'Motor clásico, impecable y con papeles en regla.', 'available', '2026-09-21 12:00:00');

INSERT INTO photos (bike_id, filename, position) VALUES
    (1, 'seed_01.jpg', 0),
    (1, 'seed_02.png', 1),
    (2, 'seed_02.png', 0),
    (2, 'seed_03.webp', 1),
    (3, 'seed_03.webp', 0),
    (3, 'seed_04.jpg', 1),
    (4, 'seed_04.jpg', 0),
    (4, 'seed_01.jpg', 1),
    (5, 'seed_01.jpg', 0),
    (5, 'seed_02.png', 1),
    (6, 'seed_02.png', 0),
    (6, 'seed_03.webp', 1),
    (7, 'seed_03.webp', 0),
    (7, 'seed_04.jpg', 1),
    (8, 'seed_04.jpg', 0),
    (8, 'seed_01.jpg', 1),
    (9, 'seed_01.jpg', 0),
    (9, 'seed_02.png', 1),
    (10, 'seed_02.png', 0),
    (10, 'seed_03.webp', 1),
    (11, 'seed_03.webp', 0),
    (11, 'seed_04.jpg', 1),
    (12, 'seed_04.jpg', 0),
    (12, 'seed_01.jpg', 1),
    (13, 'seed_01.jpg', 0),
    (13, 'seed_02.png', 1),
    (14, 'seed_02.png', 0),
    (14, 'seed_03.webp', 1),
    (15, 'seed_03.webp', 0),
    (15, 'seed_04.jpg', 1),
    (16, 'seed_04.jpg', 0),
    (16, 'seed_01.jpg', 1),
    (17, 'seed_01.jpg', 0),
    (17, 'seed_02.png', 1),
    (18, 'seed_02.png', 0),
    (18, 'seed_03.webp', 1),
    (19, 'seed_03.webp', 0),
    (19, 'seed_04.jpg', 1),
    (20, 'seed_04.jpg', 0),
    (20, 'seed_01.jpg', 1),
    (21, 'seed_01.jpg', 0),
    (21, 'seed_02.png', 1),
    (22, 'seed_02.png', 0),
    (22, 'seed_03.webp', 1),
    (23, 'seed_03.webp', 0),
    (23, 'seed_04.jpg', 1),
    (24, 'seed_04.jpg', 0),
    (24, 'seed_01.jpg', 1),
    (25, 'seed_01.jpg', 0),
    (25, 'seed_02.png', 1),
    (26, 'seed_02.png', 0),
    (26, 'seed_03.webp', 1),
    (27, 'seed_03.webp', 0),
    (27, 'seed_04.jpg', 1);

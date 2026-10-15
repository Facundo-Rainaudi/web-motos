<?php
// tests/Integration/UserTest.php
// Pruebas de integración del registro y del login (RF-1 y RF-2) contra la base
// bike_center_test. Cada test arranca con las tablas vacías (ver tests/Pest.php).

declare(strict_types=1);

describe('register_user (RF-1)', function () {
    it('crea la cuenta con los datos normalizados y la contraseña hasheada', function () {
        $result = register_user(test_db(), '  Ana Perez ', 'ANA@X.COM ', 'moto2026', '11 5555-5555');

        expect($result['ok'])->toBeTrue();

        // Se busca el usuario recién creado para chequear cómo quedó guardado.
        $stmt = test_db()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $result['user_id']]);
        $user = $stmt->fetch();

        expect($user['full_name'])->toBe('Ana Perez')
            ->and($user['email'])->toBe('ana@x.com')                 // minúsculas (RF-1)
            ->and($user['phone'])->toBe('+54 11 1155555555')         // formato internacional (RF-1)
            ->and($user['password_hash'])->not->toBe('moto2026')     // nunca el texto plano
            ->and(password_verify('moto2026', $user['password_hash']))->toBeTrue();
    });

    it('rechaza un correo ya registrado sin importar mayúsculas', function () {
        register_user(test_db(), 'Ana Perez', 'ana@x.com', 'moto2026', '1155555555');

        $second = register_user(test_db(), 'Otro Usuario', 'AnA@X.COM', 'otraclave1', '1155555556');

        expect($second['ok'])->toBeFalse()
            ->and($second['errors']['email'])->toBe('Ese correo ya está registrado.')
            ->and((int) test_db()->query('SELECT COUNT(*) FROM users')->fetchColumn())->toBe(1);
    });

    it('devuelve el error de cada campo y no toca la base', function () {
        $result = register_user(test_db(), 'An', 'mal-email', 'corta', '123');

        expect($result['ok'])->toBeFalse()
            ->and(array_keys($result['errors']))->toBe(['full_name', 'email', 'password', 'phone'])
            ->and((int) test_db()->query('SELECT COUNT(*) FROM users')->fetchColumn())->toBe(0);
    });
});

describe('authenticate_user (RF-2)', function () {
    beforeEach(function () {
        register_user(test_db(), 'Ana Perez', 'ana@x.com', 'moto2026', '1155555555');
    });

    it('devuelve el usuario con correo y contraseña correctos', function () {
        // El correo se compara en minúsculas aunque el usuario lo tipee distinto.
        $result = authenticate_user(test_db(), 'ANA@x.com', 'moto2026');

        expect($result['ok'])->toBeTrue()
            ->and($result['user_id'])->toBeInt();
    });

    it('rechaza la contraseña equivocada sin indicar qué dato falló', function () {
        $result = authenticate_user(test_db(), 'ana@x.com', 'contrasena-mala');

        expect($result['ok'])->toBeFalse()
            ->and($result)->not->toHaveKey('field');
    });

    it('rechaza un correo que no existe', function () {
        $result = authenticate_user(test_db(), 'nadie@x.com', 'moto2026');

        expect($result['ok'])->toBeFalse();
    });
});

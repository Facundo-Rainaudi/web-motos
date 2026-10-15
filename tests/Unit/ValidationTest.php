<?php
// tests/Unit/ValidationTest.php
// Pruebas de las reglas puras de datos de src/validation.php (RF-1 y RF-3).
// Cada validador devuelve ['ok' => bool, 'value' => mixed, 'error' => string|null]:
// si 'ok' es true, 'value' trae el valor normalizado listo para guardar.

declare(strict_types=1);

describe('normalize_email (RF-1)', function () {
    it('convierte el correo a minúsculas y le quita los espacios', function () {
        $result = normalize_email('  Usuario@X.COM ');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('usuario@x.com');
    });

    it('rechaza un correo con formato inválido', function () {
        $result = normalize_email('no-es-un-correo');

        expect($result['ok'])->toBeFalse()
            ->and($result['value'])->toBeNull()
            ->and($result['error'])->toBe('El correo no tiene un formato válido.');
    });
});

describe('validate_password (RF-1)', function () {
    it('acepta una contraseña de 8 caracteres o más', function () {
        $result = validate_password('moto2026');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('moto2026');
    });

    it('rechaza una contraseña más corta que 8 caracteres', function () {
        $result = validate_password('moto26');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('La contraseña debe tener al menos 8 caracteres.');
    });

    it('rechaza una contraseña que está formada solo por espacios', function () {
        $result = validate_password('        ');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('La contraseña no puede estar vacía.');
    });
});

describe('validate_full_name (RF-1)', function () {
    it('acepta un nombre completo de 3 caracteres o más', function () {
        $result = validate_full_name('  Ana Perez  ');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('Ana Perez');
    });

    it('rechaza un nombre más corto que 3 caracteres', function () {
        $result = validate_full_name('An');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('El nombre completo debe tener al menos 3 caracteres.');
    });
});

describe('validate_phone (RF-1)', function () {
    it('devuelve el teléfono en formato +54 11 ignorando espacios y guiones', function () {
        $result = validate_phone('11 5555-5555');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('+54 11 1155555555');
    });

    it('rechaza un teléfono con letras', function () {
        $result = validate_phone('11555a5555');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('El teléfono debe tener exactamente 10 dígitos, sin letras ni signos.');
    });

    it('rechaza un teléfono con signo más (no se admite código de país)', function () {
        $result = validate_phone('+54 911 5555 5555');

        expect($result['ok'])->toBeFalse();
    });

    it('rechaza un teléfono con 9 dígitos', function () {
        $result = validate_phone('115555555');

        expect($result['ok'])->toBeFalse();
    });

    it('rechaza un teléfono con 11 dígitos', function () {
        $result = validate_phone('11555555555');

        expect($result['ok'])->toBeFalse();
    });

    it('rechaza un teléfono vacío', function () {
        $result = validate_phone('');

        expect($result['ok'])->toBeFalse();
    });
});

describe('validate_price (RF-3)', function () {
    it('acepta un precio entero y lo devuelve con dos decimales', function () {
        $result = validate_price('1500');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('1500.00');
    });

    it('acepta coma o punto como separador decimal', function () {
        expect(validate_price('1500,50')['value'])->toBe('1500.50')
            ->and(validate_price('1500.5')['value'])->toBe('1500.50');
    });

    it('rechaza un precio con más de dos decimales', function () {
        $result = validate_price('1500.505');

        expect($result['ok'])->toBeFalse();
    });

    it('rechaza un precio negativo, cero o no numérico', function () {
        expect(validate_price('-5')['ok'])->toBeFalse()
            ->and(validate_price('0')['ok'])->toBeFalse()
            ->and(validate_price('moto')['ok'])->toBeFalse();
    });

    it('rechaza el separador de miles', function () {
        $result = validate_price('1,500.00');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('El precio debe ser un número mayor a cero, con hasta 2 decimales y sin separador de miles.');
    });
});

describe('validate_brand (RF-3)', function () {
    it('acepta una marca de hasta 60 caracteres', function () {
        $result = validate_brand('  Yamaha  ');

        expect($result['ok'])->toBeTrue()
            ->and($result['value'])->toBe('Yamaha');
    });

    it('rechaza una marca vacía o de más de 60 caracteres', function () {
        expect(validate_brand('')['ok'])->toBeFalse()
            ->and(validate_brand('   ')['ok'])->toBeFalse()
            ->and(validate_brand(str_repeat('m', 61))['ok'])->toBeFalse();
    });
});

describe('validate_title (RF-3)', function () {
    it('acepta un título de hasta 80 caracteres', function () {
        $result = validate_title(str_repeat('m', 80));

        expect($result['ok'])->toBeTrue();
    });

    it('rechaza un título de más de 80 caracteres', function () {
        $result = validate_title(str_repeat('m', 81));

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('El título no puede superar los 80 caracteres.');
    });

    it('rechaza un título vacío o en blanco', function () {
        expect(validate_title('')['ok'])->toBeFalse()
            ->and(validate_title('   ')['ok'])->toBeFalse();
    });
});

describe('validate_description (RF-3)', function () {
    it('acepta una descripción de hasta 2000 caracteres', function () {
        $result = validate_description(str_repeat('m', 2000));

        expect($result['ok'])->toBeTrue();
    });

    it('rechaza una descripción de más de 2000 caracteres', function () {
        $result = validate_description(str_repeat('m', 2001));

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('La descripción no puede superar los 2000 caracteres.');
    });

    it('rechaza una descripción vacía', function () {
        $result = validate_description('');

        expect($result['ok'])->toBeFalse();
    });
});

describe('validate_currency (RF-3)', function () {
    it('acepta pesos y dólares', function () {
        expect(validate_currency('ARS')['ok'])->toBeTrue()
            ->and(validate_currency('USD')['ok'])->toBeTrue();
    });

    it('rechaza otra moneda', function () {
        $result = validate_currency('EUR');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('La moneda debe ser pesos (ARS) o dólares (USD).');
    });
});

describe('validate_condition (RF-3)', function () {
    it('acepta nueva y usada', function () {
        expect(validate_condition('new')['ok'])->toBeTrue()
            ->and(validate_condition('used')['ok'])->toBeTrue();
    });

    it('rechaza otro estado', function () {
        $result = validate_condition('crap');

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('El estado debe ser nueva (new) o usada (used).');
    });
});

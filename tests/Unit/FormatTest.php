<?php
// tests/Unit/FormatTest.php
// Formato de salida del precio para el usuario (RF-4 y RF-5): punto de miles
// y dos decimales solo cuando hace falta; la vitrina en JS repite estas reglas.

declare(strict_types=1);

describe('format_price (RF-4, RF-5)', function () {
    it('sin decimales cuando el precio es entero', function () {
        expect(format_price('2500.00'))->toBe('2.500')
            ->and(format_price('900.00'))->toBe('900')
            ->and(format_price('2000000.00'))->toBe('2.000.000');
    });

    it('con dos decimales cuando tiene fracción', function () {
        expect(format_price('2500.50'))->toBe('2.500,50')
            ->and(format_price('1234.10'))->toBe('1.234,10');
    });
});

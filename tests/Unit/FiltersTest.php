<?php
// tests/Unit/FiltersTest.php
// Pruebas del parseo puro de parámetros de la vitrina (spec 02: RF-11,
// RF-12 y RNF-7). parse_feed_filters() valida cada parámetro por separado:
// el inválido queda en null sin tocar al resto, así una URL con basura nunca
// rompe el listado. Sin BD: son funciones puras.

declare(strict_types=1);

describe('parse_feed_filters (RF-11, RF-12)', function () {
    it('devuelve todo en null cuando no llega ningún parámetro', function () {
        $filters = parse_feed_filters([]);

        expect($filters['q'])->toBeNull()
            ->and($filters['condition'])->toBeNull()
            ->and($filters['currency'])->toBeNull()
            ->and($filters['min'])->toBeNull()
            ->and($filters['max'])->toBeNull()
            ->and($filters['status'])->toBeNull();
    });

    it('quita los espacios sobrantes del texto de búsqueda', function () {
        expect(parse_feed_filters(['q' => '  ninja  '])['q'])->toBe('ninja');
    });

    it('ignora un texto vacío o compuesto solo por espacios', function () {
        expect(parse_feed_filters(['q' => '   '])['q'])->toBeNull()
            ->and(parse_feed_filters(['q' => ''])['q'])->toBeNull();
    });

    it('recorta el texto de búsqueda a 80 caracteres', function () {
        $filters = parse_feed_filters(['q' => str_repeat('a', 100)]);

        expect($filters['q'])->toBe(str_repeat('a', 80));
    });

    it('recorta el texto respetando los caracteres multibyte', function () {
        $filters = parse_feed_filters(['q' => str_repeat('ñ', 100)]);

        expect(mb_strlen($filters['q']))->toBe(80);
    });

    it('quita los comodines %, _ y \ del texto de búsqueda', function () {
        expect(parse_feed_filters(['q' => '100%_de\\potencia'])['q'])->toBe('100depotencia');
    });

    it('ignora un texto que llegue como arreglo', function () {
        expect(parse_feed_filters(['q' => ['ninja']])['q'])->toBeNull();
    });

    it('acepta condition new y used y descarta cualquier otro valor', function () {
        expect(parse_feed_filters(['condition' => 'new'])['condition'])->toBe('new')
            ->and(parse_feed_filters(['condition' => 'used'])['condition'])->toBe('used')
            ->and(parse_feed_filters(['condition' => 'banana'])['condition'])->toBeNull();
    });

    it('acepta currency ARS y USD y descarta cualquier otra', function () {
        expect(parse_feed_filters(['currency' => 'ARS'])['currency'])->toBe('ARS')
            ->and(parse_feed_filters(['currency' => 'USD'])['currency'])->toBe('USD')
            ->and(parse_feed_filters(['currency' => 'EUR'])['currency'])->toBeNull();
    });

    it('normaliza los precios con coma o punto como al publicar', function () {
        expect(parse_feed_filters(['min' => '1000'])['min'])->toBe('1000.00')
            ->and(parse_feed_filters(['min' => '1.5'])['min'])->toBe('1.50')
            ->and(parse_feed_filters(['max' => '2,75'])['max'])->toBe('2.75');
    });

    it('descarta precios no numéricos, negativos o con más de 2 decimales', function () {
        expect(parse_feed_filters(['min' => 'abc'])['min'])->toBeNull()
            ->and(parse_feed_filters(['min' => '-5'])['min'])->toBeNull()
            ->and(parse_feed_filters(['min' => '1.234'])['min'])->toBeNull()
            ->and(parse_feed_filters(['max' => '0'])['max'])->toBeNull();
    });

    it('descarta el rango invertido sin tocar los demás filtros', function () {
        $filters = parse_feed_filters(['min' => '5000', 'max' => '1000', 'condition' => 'used']);

        expect($filters['min'])->toBeNull()
            ->and($filters['max'])->toBeNull()
            ->and($filters['condition'])->toBe('used');
    });

    it('si solo un lado del precio es inválido, el otro sigue aplicado', function () {
        $filters = parse_feed_filters(['min' => 'abc', 'max' => '2000']);

        expect($filters['min'])->toBeNull()
            ->and($filters['max'])->toBe('2000.00');
    });

    it('acepta status=available y descarta cualquier otro valor', function () {
        expect(parse_feed_filters(['status' => 'available'])['status'])->toBe('available')
            ->and(parse_feed_filters(['status' => 'sold'])['status'])->toBeNull()
            ->and(parse_feed_filters(['status' => ''])['status'])->toBeNull();
    });

    it('ignora parámetros desconocidos sin afectar la selección', function () {
        $filters = parse_feed_filters(['q' => 'r1', 'orden' => 'precio', 'pagina' => '3']);

        expect($filters['q'])->toBe('r1')
            ->and(array_keys($filters))->toBe(['q', 'condition', 'currency', 'min', 'max', 'status']);
    });

    it('arma una selección completa combinando todos los parámetros', function () {
        $filters = parse_feed_filters([
            'q' => ' fz 25 ',
            'condition' => 'used',
            'currency' => 'USD',
            'min' => '1000',
            'max' => '3000.50',
            'status' => 'available',
        ]);

        expect($filters)->toBe([
            'q' => 'fz 25',
            'condition' => 'used',
            'currency' => 'USD',
            'min' => '1000.00',
            'max' => '3000.50',
            'status' => 'available',
        ]);
    });
});

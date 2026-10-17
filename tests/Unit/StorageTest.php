<?php
// tests/Unit/StorageTest.php
// Reglas de las fotos subidas (RF-3 y RNF-5): tipo real detectado con finfo
// (JPEG/PNG/WEBP), hasta 2 MB inclusive, al menos 200×200, máximo de 4 por
// publicación y ninguna repetida dentro de la misma publicación por hash.
// Se arman arreglos con la forma de $_FILES para poder probar sin servidor.

declare(strict_types=1);

/**
 * Arma la estructura mínima de $_FILES apuntando a un archivo del disco.
 */
function fixture_file(string $name): array
{
    $path = __DIR__ . '/../fixtures/' . $name;

    return [
        'name' => $name,
        'tmp_name' => $path,
        'size' => filesize($path),
        'error' => UPLOAD_ERR_OK,
    ];
}

/**
 * Crea un JPEG válido de 200×200 pesando más de 2 MB (le pedimos al disco que
 * agregue relleno al final: el encabezado sigue siendo JPEG, así que se aísla
 * la regla de tamaño sin depender de fixtures pesados en el repositorio).
 */
function oversized_jpeg(): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'bigjpg');
    copy(__DIR__ . '/../fixtures/valid_200x200.jpg', $tmp);
    file_put_contents($tmp, str_repeat('A', 3 * 1024 * 1024), FILE_APPEND);

    return $tmp;
}

describe('validate_photo (RF-3, RNF-5)', function () {
    it('acepta un JPEG real de 200×200 y devuelve su hash', function () {
        $result = validate_photo(fixture_file('valid_200x200.jpg'));

        expect($result['ok'])->toBeTrue()
            ->and($result['mime'])->toBe('image/jpeg')
            ->and($result['sha1'])->toMatch('/^[0-9a-f]{40}$/');
    });

    it('acepta los tres formatos admitidos: JPEG, PNG y WEBP', function () {
        expect(validate_photo(fixture_file('valid_200x200.jpg'))['ok'])->toBeTrue()
            ->and(validate_photo(fixture_file('valid_300x300.png'))['ok'])->toBeTrue()
            ->and(validate_photo(fixture_file('valid_250x250.webp'))['ok'])->toBeTrue();
    });

    it('rechaza un PDF renombrado a .jpg por el tipo real', function () {
        $fake = tempnam(sys_get_temp_dir(), 'fakejpg');
        file_put_contents($fake, "%PDF-1.4\n% contenido que no es una imagen\n");
        $result = validate_photo(['name' => 'foto.jpg', 'tmp_name' => $fake, 'size' => filesize($fake), 'error' => UPLOAD_ERR_OK]);
        unlink($fake);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('JPEG, PNG o WEBP');
    });

    it('rechaza un archivo de más de 2 MB', function () {
        $big = oversized_jpeg();
        $result = validate_photo(['name' => 'foto.jpg', 'tmp_name' => $big, 'size' => filesize($big), 'error' => UPLOAD_ERR_OK]);
        unlink($big);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('2 MB');
    });

    it('rechaza una foto más chica que 200×200', function () {
        $result = validate_photo(fixture_file('small_100x100.jpg'));

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('200×200');
    });

    it('rechaza un archivo que vino con error de subida', function () {
        $result = validate_photo(['name' => 'x.jpg', 'tmp_name' => '', 'size' => 0, 'error' => UPLOAD_ERR_PARTIAL]);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->not->toBeNull();
    });
});

describe('photo_batch (RF-3)', function () {
    /**
     * Arma la estructura de $_FILES con varios archivos (lo que genera
     * <input type="file" name="photos[]">).
     */
    $batch_of = static function (array $paths): array {
        return [
            'name' => array_map('basename', $paths),
            'tmp_name' => $paths,
            'size' => array_map('filesize', $paths),
            'error' => array_fill(0, count($paths), UPLOAD_ERR_OK),
        ];
    };

    it('acepta hasta 4 fotos y rechaza la quinta por cantidad', function () use ($batch_of) {
        $jpeg = __DIR__ . '/../fixtures/valid_200x200.jpg';
        $png = __DIR__ . '/../fixtures/valid_300x300.png';
        $webp = __DIR__ . '/../fixtures/valid_250x250.webp';
        // Cuarta foto válida: copia con contenido distinto para que no caiga
        // en la regla de repetida.
        $other = tempnam(sys_get_temp_dir(), 'othjpg');
        copy($jpeg, $other);
        file_put_contents($other, 'relleno distinto', FILE_APPEND);

        $result = photo_batch($batch_of([$jpeg, $png, $webp, $other, $jpeg]));
        unlink($other);

        expect(count($result['valid']))->toBe(4)
            ->and(implode(' ', $result['errors']))->toContain('máximo 4');
    });

    it('detecta la repetida dentro de la misma publicación por hash', function () use ($batch_of) {
        $jpeg = __DIR__ . '/../fixtures/valid_200x200.jpg';

        $result = photo_batch($batch_of([$jpeg, $jpeg]));

        expect(count($result['valid']))->toBe(1)
            ->and(implode(' ', $result['errors']))->toContain('repetida');
    });

    it('rechaza una foto igual a una ya guardada en la publicación (edición, RF-6)', function () use ($batch_of) {
        $jpeg = __DIR__ . '/../fixtures/valid_200x200.jpg';

        // Al editar, el lote viene con los hashes de las fotos ya guardadas:
        // una foto idéntica a una existente se rechaza por repetida.
        $result = photo_batch($batch_of([$jpeg]), 4, [sha1_file($jpeg)]);

        expect($result['valid'])->toBe([])
            ->and(implode(' ', $result['errors']))->toContain('ya está guardada');
    });

    it('devuelve los motivos de cada foto rechazada y las válidas del resto', function () use ($batch_of) {
        $result = photo_batch($batch_of([
            __DIR__ . '/../fixtures/valid_200x200.jpg',
            __DIR__ . '/../fixtures/small_100x100.jpg',
        ]));

        expect(count($result['valid']))->toBe(1)
            ->and(count($result['errors']))->toBe(1)
            ->and($result['errors'][0])->toContain('Foto 2');
    });

    it('devuelve listas vacías si no se envió ninguna foto', function () {
        $result = photo_batch([]);

        expect($result['valid'])->toBe([])
            ->and($result['errors'])->toBe([]);
    });
});

describe('save_photo y delete_photo_file (RNF-5)', function () {
    it('guarda la foto con nombre generado por el servidor y extensión real', function () {
        // Se copia el fixture a un temporal para que save_photo no mueva el
        // archivo original del repositorio.
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        copy(__DIR__ . '/../fixtures/valid_200x200.jpg', $tmp);
        $validated = validate_photo(['name' => 'foto.jpg', 'tmp_name' => $tmp, 'size' => filesize($tmp), 'error' => UPLOAD_ERR_OK]);

        $dir = sys_get_temp_dir() . '/bc_uploads_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $saved = save_photo($validated, $dir);

        expect($saved['ok'])->toBeTrue()
            ->and($saved['filename'])->toMatch('/^[0-9a-f]{32}\.jpg$/')
            ->and(is_file($dir . '/' . $saved['filename']))->toBeTrue();

        delete_photo_file($saved['filename'], $dir);
        rmdir($dir);
    });

    it('borra la foto del disco y una ruta rara no alcanza archivos de afuera', function () {
        $dir = sys_get_temp_dir() . '/bc_del_' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/a.jpg', 'x');

        // Un archivo en otra carpeta: aunque se pida con una ruta relativa,
        // basename() confina el borrado a $dir y no lo toca.
        $afuera = sys_get_temp_dir() . '/bc_out_' . bin2hex(random_bytes(6)) . '.jpg';
        file_put_contents($afuera, 'y');
        delete_photo_file('../' . basename($afuera), $dir);

        expect(is_file($afuera))->toBeTrue();

        delete_photo_file('a.jpg', $dir);

        expect(is_file($dir . '/a.jpg'))->toBeFalse();

        unlink($afuera);
        rmdir($dir);
    });
});

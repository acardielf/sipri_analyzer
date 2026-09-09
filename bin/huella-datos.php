<?php

declare(strict_types=1);

/*
 * Resume el estado de la base de datos en una línea, para decidir si el sitio
 * hay que regenerarlo. Lo usa ci.yml a ambos lados de `sipri:last`: si la
 * huella no cambia, no ha entrado ningún dato y el HTML generado sería el mismo
 * salvo la fecha «Datos actualizados a», así que no hay nada que publicar.
 *
 * Contar filas basta porque el pipeline solo inserta: sipri:ext omite las
 * plazas que ya existen (hash SHA-256) y sipri:adj sale sin hacer nada si la
 * convocatoria ya tiene adjudicaciones. max(rowid) cubre además el caso de
 * insertar y borrar lo mismo, que dejaría el count igual.
 *
 * Las tablas se leen del esquema en vez de listarlas aquí: una tabla nueva
 * entra en la huella sola. doctrine_migration_versions queda fuera a propósito
 * —una migración llega siempre con un push, y un push regenera de todas formas.
 *
 * Uso: php bin/huella-datos.php [ruta/a/la.db]
 */

$ruta = $argv[1] ?? 'var/data_prod.db';

if (!is_file($ruta)) {
    fwrite(STDERR, "No existe la base de datos: {$ruta}\n");
    exit(1);
}

try {
    $db = new PDO('sqlite:' . $ruta);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tablas = $db->query(
        "SELECT name FROM sqlite_master
         WHERE type = 'table'
           AND name NOT LIKE 'sqlite_%'
           AND name <> 'doctrine_migration_versions'
         ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);

    if ([] === $tablas) {
        fwrite(STDERR, "La base de datos {$ruta} no tiene tablas.\n");
        exit(1);
    }

    $partes = [];
    foreach ($tablas as $tabla) {
        [$filas, $ultimo] = $db
            ->query(sprintf('SELECT count(*), coalesce(max(rowid), 0) FROM "%s"', $tabla))
            ->fetch(PDO::FETCH_NUM);

        $partes[] = sprintf('%s:%d/%d', $tabla, $filas, $ultimo);
    }

    echo implode(' ', $partes), "\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Error leyendo {$ruta}: {$e->getMessage()}\n");
    exit(1);
}

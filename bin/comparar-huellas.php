<?php

declare(strict_types=1);

/*
 * Compara dos huellas de `bin/huella-datos.php` y falla si alguna tabla ha
 * perdido filas. El pipeline solo inserta —sipri:ext omite las plazas ya
 * conocidas por su hash y sipri:adj sale sin hacer nada si la convocatoria ya
 * tiene adjudicaciones—, así que una tabla que encoge no es un caso normal:
 * significa que la base de datos con la que se ha trabajado no era la que
 * tocaba, y publicarla dejaría el sitio peor que antes.
 *
 * Es la red por debajo de la elección del artefacto: al salir con error antes
 * de «Upload artifacts», el artefacto bueno sigue siendo el del run anterior y
 * la siguiente ejecución vuelve a partir de él.
 *
 * Uso: php bin/comparar-huellas.php "<huella antes>" "<huella después>"
 */

if ($argc < 3) {
    fwrite(STDERR, "Uso: php bin/comparar-huellas.php \"<antes>\" \"<después>\"\n");
    exit(2);
}

/** @return array<string, int> */
$parsear = static function (string $huella): array {
    $filas = [];
    foreach (preg_split('/\s+/', trim($huella), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $parte) {
        if (preg_match('/^(?<tabla>[^:]+):(?<filas>\d+)\/\d+$/', $parte, $m) === 1) {
            $filas[$m['tabla']] = (int) $m['filas'];
        }
    }

    return $filas;
};

$antes = $parsear($argv[1]);
$despues = $parsear($argv[2]);

if ([] === $antes) {
    echo "Sin huella previa con la que comparar: no se comprueba nada.\n";
    exit(0);
}

if ([] === $despues) {
    fwrite(STDERR, "La huella posterior está vacía o no se ha podido leer.\n");
    exit(1);
}

$perdidas = [];
foreach ($antes as $tabla => $filasAntes) {
    // Una tabla que desaparece cuenta como pérdida: el esquema lo gestionan las
    // migraciones, y una migración llega con un push, no a mitad del cron.
    $filasDespues = $despues[$tabla] ?? 0;

    if ($filasDespues < $filasAntes) {
        $perdidas[] = sprintf('%s: %d → %d (%d menos)', $tabla, $filasAntes, $filasDespues, $filasAntes - $filasDespues);
    }
}

if ([] !== $perdidas) {
    fwrite(STDERR, "La base de datos ha perdido filas:\n  " . implode("\n  ", $perdidas) . "\n");
    exit(1);
}

echo "Ninguna tabla ha perdido filas.\n";
exit(0);

<?php
declare(strict_types=1);

/**
 * Maakt verbinding met de SQLite-database en maakt de tabel aan als die nog niet bestaat.
 * Fouten worden als PDOException gegooid (ERRMODE_EXCEPTION), zodat we ze kunnen afhandelen.
 */
function getDatabase(string $pad = __DIR__ . '/../gebruikers.sqlite'): PDO
{
    $pdo = new PDO('sqlite:' . $pad);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // UNIQUE op e-mail: de database weigert dubbele e-mailadressen, ook als de PHP-controle gemist wordt.
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS gebruikers (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            email           TEXT NOT NULL UNIQUE,
            wachtwoord_hash TEXT NOT NULL,
            aangemaakt_op   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    return $pdo;
}

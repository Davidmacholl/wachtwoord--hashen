<?php
declare(strict_types=1);

require __DIR__ . '/src/database.php';
require __DIR__ . '/src/auth.php';

const MAX_POGINGEN = 3;

/** Leest één regel invoer. Stopt netjes als er geen invoer meer is (Ctrl+D / einde van pipe). */
function vraag(string $tekst): string
{
    echo $tekst;
    $regel = fgets(STDIN);

    if ($regel === false) {
        echo "\nGeen invoer meer beschikbaar. Programma stopt.\n";
        exit(0);
    }

    return rtrim($regel, "\r\n");
}

/** Vraagt een wachtwoord. Op Linux/macOS in een echte terminal wordt de invoer verborgen. */
function vraagWachtwoord(string $tekst): string
{
    $verbergen = DIRECTORY_SEPARATOR === '/' && function_exists('stream_isatty') && stream_isatty(STDIN);

    if ($verbergen) {
        shell_exec('stty -echo');
    }

    try {
        $wachtwoord = vraag($tekst);
    } finally {
        if ($verbergen) {
            shell_exec('stty echo');
            echo "\n";
        }
    }

    return $wachtwoord;
}

function toonFout(string $bericht): void
{
    echo "  [FOUT] $bericht\n";
}

function doeRegistratie(PDO $pdo): void
{
    echo "\n--- Registreren ---\n";
    $email = vraag('E-mailadres: ');
    $wachtwoord = vraagWachtwoord('Wachtwoord (min. ' . MIN_LENGTE . ' tekens): ');
    $herhaling = vraagWachtwoord('Herhaal wachtwoord: ');

    try {
        $hash = registreer($pdo, $email, $wachtwoord, $herhaling);
        echo "  [OK] Account aangemaakt. Opgeslagen hash:\n  $hash\n";
    } catch (GebruikersFout $e) {
        toonFout($e->getMessage());
    } catch (RuntimeException $e) {
        toonFout('Het wachtwoord kon niet veilig worden opgeslagen. Probeer het later opnieuw.');
    } catch (PDOException $e) {
        toonFout('Er ging iets mis met de database. Probeer het later opnieuw.');
    }
}

function doeLogin(PDO $pdo): void
{
    echo "\n--- Inloggen ---\n";
    $email = vraag('E-mailadres: ');

    for ($poging = 1; $poging <= MAX_POGINGEN; $poging++) {
        $wachtwoord = vraagWachtwoord('Wachtwoord: ');

        try {
            if (login($pdo, $email, $wachtwoord)) {
                echo "  [OK] Wachtwoord is correct! Je bent ingelogd als " . strtolower(trim($email)) . ".\n";
                return;
            }

            $over = MAX_POGINGEN - $poging;
            toonFout('E-mailadres of wachtwoord is onjuist.' . ($over > 0 ? " Nog $over poging(en)." : ''));
        } catch (GebruikersFout $e) {
            // Ongeldige invoer (bv. leeg e-mailadres): opnieuw proberen heeft geen zin, terug naar het menu.
            toonFout($e->getMessage());
            if ($e->getMessage() !== 'Wachtwoord mag niet leeg zijn.') {
                return;
            }
        } catch (PDOException $e) {
            toonFout('Er ging iets mis met de database. Probeer het later opnieuw.');
            return;
        }
    }

    toonFout('Te veel mislukte pogingen. Terug naar het menu.');
}

// ---------- Hoofdprogramma ----------

try {
    $pdo = getDatabase();
} catch (PDOException $e) {
    fwrite(STDERR, "Kan de database niet openen: " . $e->getMessage() . "\n");
    exit(1);
}

while (true) {
    echo "\n=== Login- en registratiesysteem ===\n";
    echo "1. Registreren\n2. Inloggen\n3. Afsluiten\n";
    $keuze = trim(vraag('Keuze: '));

    switch ($keuze) {
        case '1':
            doeRegistratie($pdo);
            break;
        case '2':
            doeLogin($pdo);
            break;
        case '3':
            echo "Tot ziens!\n";
            exit(0);
        default:
            toonFout('Ongeldige keuze, typ 1, 2 of 3.');
    }
}

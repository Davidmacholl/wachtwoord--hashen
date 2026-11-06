<?php
declare(strict_types=1);

/** Fout met een bericht dat veilig aan de gebruiker getoond mag worden. */
class GebruikersFout extends Exception
{
}

const MIN_LENGTE = 8;
const MAX_BYTES  = 72; // bcrypt gebruikt maximaal 72 bytes; de rest zou stil genegeerd worden

/** Controleert het e-mailadres en geeft het genormaliseerd (kleine letters, getrimd) terug. */
function valideerEmail(string $email): string
{
    $email = strtolower(trim($email));

    if ($email === '') {
        throw new GebruikersFout('E-mailadres mag niet leeg zijn.');
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new GebruikersFout('Dit is geen geldig e-mailadres.');
    }

    return $email;
}

/** Controleert of het wachtwoord aan de eisen voldoet. Wachtwoorden worden bewust NIET getrimd. */
function valideerWachtwoord(string $wachtwoord): void
{
    if ($wachtwoord === '') {
        throw new GebruikersFout('Wachtwoord mag niet leeg zijn.');
    }
    if (strlen($wachtwoord) < MIN_LENGTE) {
        throw new GebruikersFout('Wachtwoord moet minimaal ' . MIN_LENGTE . ' tekens bevatten.');
    }
    if (strlen($wachtwoord) > MAX_BYTES) {
        throw new GebruikersFout('Wachtwoord mag maximaal ' . MAX_BYTES . ' tekens bevatten.');
    }
}

/** Hasht het wachtwoord met bcrypt. password_hash() maakt zelf een willekeurige salt aan. */
function hashWachtwoord(string $wachtwoord): string
{
    try {
        $hash = password_hash($wachtwoord, PASSWORD_BCRYPT, ['cost' => 12]);
    } catch (Throwable $e) {
        throw new RuntimeException('Hashen mislukt: ' . $e->getMessage(), 0, $e);
    }

    if ($hash === false) {
        throw new RuntimeException('Hashen mislukt.');
    }

    return $hash;
}

function emailBestaat(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM gebruikers WHERE email = :email');
    $stmt->execute([':email' => $email]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Registreert een nieuwe gebruiker en geeft de opgeslagen hash terug.
 * Gooit GebruikersFout bij ongeldige invoer, een niet-overeenkomend herhaald wachtwoord of een dubbel e-mailadres.
 */
function registreer(PDO $pdo, string $email, string $wachtwoord, string $herhaling): string
{
    $email = valideerEmail($email);
    valideerWachtwoord($wachtwoord);

    if ($herhaling === '') {
        throw new GebruikersFout('Herhaal je wachtwoord.');
    }
    if (!hash_equals($wachtwoord, $herhaling)) {
        throw new GebruikersFout('De twee wachtwoorden komen niet overeen.');
    }
    if (emailBestaat($pdo, $email)) {
        throw new GebruikersFout('Dit e-mailadres is al geregistreerd.');
    }

    $hash = hashWachtwoord($wachtwoord);

    try {
        $stmt = $pdo->prepare('INSERT INTO gebruikers (email, wachtwoord_hash) VALUES (:email, :hash)');
        $stmt->execute([':email' => $email, ':hash' => $hash]);
    } catch (PDOException $e) {
        // 23000 = unique constraint: iemand anders was ons net voor met hetzelfde e-mailadres
        if ($e->getCode() === '23000') {
            throw new GebruikersFout('Dit e-mailadres is al geregistreerd.');
        }
        throw $e;
    }

    return $hash;
}

/**
 * Controleert e-mail + wachtwoord. Geeft true/false terug.
 * Bij een onbekend e-mailadres geven we bewust hetzelfde resultaat als bij een fout wachtwoord,
 * zodat niemand kan uitzoeken welke e-mailadressen geregistreerd zijn.
 */
function login(PDO $pdo, string $email, string $wachtwoord): bool
{
    $email = valideerEmail($email);

    if ($wachtwoord === '') {
        throw new GebruikersFout('Wachtwoord mag niet leeg zijn.');
    }

    $stmt = $pdo->prepare('SELECT wachtwoord_hash FROM gebruikers WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $hash = $stmt->fetchColumn();

    if ($hash === false) {
        return false;
    }

    return password_verify($wachtwoord, $hash);
}

# Login- en registratiesysteem (PHP)

Een terminalprogramma waarmee je een account kunt registreren en kunt inloggen. Wachtwoorden worden nooit leesbaar opgeslagen: ze worden gehasht met bcrypt via `password_hash()` en gecontroleerd met `password_verify()`. Gebruikers staan in een SQLite-database (via PDO).

## Wat doet het programma?

1. **Wachtwoord invoeren**: bij registreren vraagt het programma om e-mail, wachtwoord en een herhaling van het wachtwoord.
2. **Wachtwoord hashen**: het wachtwoord wordt gehasht met `password_hash($wachtwoord, PASSWORD_BCRYPT, ['cost' => 12])`. Alleen de hash wordt opgeslagen.
3. **Wachtwoord verifiëren**: bij inloggen vraagt het programma opnieuw om het wachtwoord en vergelijkt dat met de opgeslagen hash via `password_verify()`. Je hebt maximaal 3 pogingen.

## Installatie

Vereisten: PHP 8.0 of hoger met de extensie `pdo_sqlite` (staat standaard aan in de meeste PHP-installaties).

```bash
git clone <link-naar-deze-repository>
cd login-systeem
php app.php
```

Controleren of `pdo_sqlite` aan staat: `php -m | grep -i sqlite` (op Windows: `php -m | findstr sqlite`).

De database `gebruikers.sqlite` wordt bij de eerste keer starten automatisch aangemaakt. Dit bestand staat in `.gitignore` en komt dus niet op GitHub.

## Gebruik

```
=== Login- en registratiesysteem ===
1. Registreren
2. Inloggen
3. Afsluiten
```

- Kies `1` om een account aan te maken (e-mail, wachtwoord, wachtwoord herhalen).
- Kies `2` om in te loggen met een bestaand account.
- Op Linux/macOS wordt het wachtwoord tijdens het typen verborgen. Op Windows is dit niet mogelijk in de gewone PHP-CLI, daar is het wachtwoord zichtbaar tijdens het typen.

Extra: met `php demo_salt.php` zie je dat hetzelfde wachtwoord twee keer een andere hash geeft (bewijs van de salt).

## Projectstructuur

| Bestand | Doel |
|---|---|
| `app.php` | Menu en invoer/uitvoer in de terminal |
| `src/auth.php` | Validatie, hashen, registreren en inloggen |
| `src/database.php` | Databaseverbinding en aanmaken van de tabel |
| `demo_salt.php` | Demonstratie dat de salt per hash verschilt |

## Fouten en uitzonderingen

Alle fouten worden aan de gebruiker getoond als `[FOUT] ...` en het programma blijft draaien.

| Situatie | Afhandeling |
|---|---|
| Lege invoer (e-mail of wachtwoord) | Foutmelding, er wordt niets opgeslagen |
| Ongeldig e-mailadres | Gecontroleerd met `filter_var(..., FILTER_VALIDATE_EMAIL)` |
| Wachtwoord te kort (< 8) of te lang (> 72 tekens) | Foutmelding. bcrypt gebruikt maximaal 72 bytes, daarom wordt langer geweigerd in plaats van stil afgekapt |
| Herhaald wachtwoord komt niet overeen | Foutmelding bij registratie |
| E-mailadres bestaat al | Eerst een controle in PHP, daarnaast een `UNIQUE`-constraint in de database (vangt ook het geval dat twee gebruikers tegelijk registreren). E-mailadressen worden in kleine letters opgeslagen |
| Hashfout | `password_hash()` zit in een `try`-`catch`; ook de returnwaarde `false` wordt als fout behandeld |
| Databasefout | `PDOException` wordt afgevangen met een nette melding, zonder interne details te tonen |
| Verkeerd wachtwoord of onbekend e-mailadres | Dezelfde melding ("E-mailadres of wachtwoord is onjuist"), zodat niemand kan nagaan welke e-mailadressen bestaan. Na 3 pogingen terug naar het menu |

## Antwoorden op de vragen

### Kun je het oorspronkelijke wachtwoord uit de hash achterhalen?

**Nee, niet door de hash om te draaien.** Een hash is een eenrichtingsfunctie: je kunt van wachtwoord naar hash rekenen, maar niet terug. `password_verify()` "ontsleutelt" dus niets. Het hasht het ingevoerde wachtwoord opnieuw (met dezelfde salt en cost uit de opgeslagen hash) en kijkt of de uitkomst gelijk is.

Wat een aanvaller wel kan doen is **raden**: heel veel wachtwoorden proberen en elk hashen tot er een gelijke hash uitkomt (brute force of een woordenlijst). Daarom is bcrypt bewust traag (de *cost*-factor) en is een sterk wachtwoord belangrijk. Een zwak wachtwoord als `geheim123` is snel te raden, ook al is de hash zelf niet om te draaien.

### Maken `password_hash()` / `bcrypt` gebruik van een salt?

**Ja.** `password_hash()` genereert bij elke aanroep automatisch een willekeurige salt van 16 bytes en bewaart die in de hash zelf. Een bcrypt-hash heeft de opbouw:

```
$2y$12$ 22 tekens salt  31 tekens hash
```

Het voorbeeld hieronder komt uit `php demo_salt.php`: hetzelfde wachtwoord geeft twee verschillende hashes, omdat de salt verschilt. `password_verify()` leest de salt uit de hash, dus er hoeft geen aparte salt opgeslagen te worden.

Omdat er al een salt wordt gebruikt, **hoef ik zelf geen salt te implementeren**. Een eigen salt meegeven wordt zelfs afgeraden: de `salt`-optie is sinds PHP 8.0 uitgefaseerd en wordt genegeerd. Het nut van een salt is dat twee gebruikers met hetzelfde wachtwoord toch een andere hash krijgen, en dat vooraf berekende tabellen (rainbow tables) niet werken.

## Bronnen

- https://www.php.net/manual/en/function.password-hash.php
- https://auth0.com/blog/hashing-in-action-understanding-bcrypt/
- https://www.geeksforgeeks.org/hashing-passwords-in-python-with-bcrypt/
- https://stackoverflow.com/questions/14973642/how-using-try-catch-for-exception-handling-is-best-practice

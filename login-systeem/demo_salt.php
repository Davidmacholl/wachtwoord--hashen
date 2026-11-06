<?php
declare(strict_types=1);

// Bewijs dat password_hash() een salt gebruikt: hetzelfde wachtwoord, twee verschillende hashes.
$wachtwoord = 'geheim123';

$hash1 = password_hash($wachtwoord, PASSWORD_BCRYPT, ['cost' => 12]);
$hash2 = password_hash($wachtwoord, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Hash 1: $hash1\n";
echo "Hash 2: $hash2\n\n";

// Opbouw van een bcrypt-hash: $2y$ + cost + $ + 22 tekens salt + 31 tekens hash
echo "Algoritme + cost : " . substr($hash1, 0, 7) . "\n";
echo "Salt (22 tekens) : " . substr($hash1, 7, 22) . "\n";
echo "Hash (31 tekens) : " . substr($hash1, 29) . "\n\n";

echo "Zijn de hashes gelijk? " . ($hash1 === $hash2 ? 'ja' : 'nee') . "\n";
echo "Verifieert hash 1?     " . (password_verify($wachtwoord, $hash1) ? 'ja' : 'nee') . "\n";
echo "Verifieert hash 2?     " . (password_verify($wachtwoord, $hash2) ? 'ja' : 'nee') . "\n";

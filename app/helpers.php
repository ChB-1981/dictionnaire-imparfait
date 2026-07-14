<?php
/**
 * helpers.php — Fonctions utilitaires du Dictionnaire imparfait
 */

// ── Sécurité ──────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Requête invalide.');
    }
}

function truncate(string $s, int $max): string {
    return mb_substr(trim($s), 0, $max, 'UTF-8');
}

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function type_options(): array {
    return [
        'nom commun',
        'adjectif',
        'verbe transitif',
        'verbe intransitif',
        'verbe pronominal',
        'adverbe',
        'locution nominale',
        'locution verbale',
        'interjection',
    ];
}

function style_options(): array {
    return [
        'Courant', 'Littéraire', 'Familier', 'Poétique',
        'Philosophique', 'Technique', 'Juridique', 'Administratif',
        'Ironique', 'Humoristique', 'Intime', 'Oral',
    ];
}

function style_abbr(?string $style): string {
    $map = [
        'Courant'       => 'Cour.',
        'Littéraire'    => 'Littér.',
        'Familier'      => 'Fam.',
        'Poétique'      => 'Poét.',
        'Philosophique' => 'Philos.',
        'Technique'     => 'Techn.',
        'Juridique'     => 'Jur.',
        'Administratif' => 'Admin.',
        'Ironique'      => 'Iron.',
        'Humoristique'  => 'Humor.',
        'Intime'        => 'Intim.',
        'Oral'          => 'Oral',
    ];
    return $map[$style] ?? (string) $style;
}

function get_all_experience_registers(PDO $pdo): array {
    return $pdo->query('SELECT * FROM dictionnaire_registres_experience ORDER BY nom')->fetchAll();
}

function get_word_register_ids(PDO $pdo, int $motId): array {
    $stmt = $pdo->prepare('SELECT registre_id FROM dictionnaire_mots_registres_experience WHERE mot_id = :id');
    $stmt->execute([':id' => $motId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'registre_id'));
}

function get_word_register_names(PDO $pdo, int $motId): array {
    $stmt = $pdo->prepare('
        SELECT r.nom
        FROM dictionnaire_registres_experience r
        JOIN dictionnaire_mots_registres_experience mr ON mr.registre_id = r.id
        WHERE mr.mot_id = :id
        ORDER BY r.nom
    ');
    $stmt->execute([':id' => $motId]);
    return array_column($stmt->fetchAll(), 'nom');
}

function sync_word_registers(PDO $pdo, int $motId, array $registerIds): void {
    $pdo->prepare('DELETE FROM dictionnaire_mots_registres_experience WHERE mot_id = :id')
        ->execute([':id' => $motId]);
    $stmt = $pdo->prepare('
        INSERT IGNORE INTO dictionnaire_mots_registres_experience (mot_id, registre_id)
        VALUES (:mot_id, :registre_id)
    ');
    foreach ($registerIds as $rid) {
        if ((int) $rid > 0) {
            $stmt->execute([':mot_id' => $motId, ':registre_id' => (int) $rid]);
        }
    }
}

function ponctuer(string $s): string {
    $s = trim($s);
    if (empty($s)) return $s;
    $dernier = mb_substr($s, -1, 1, 'UTF-8');
    if (!in_array($dernier, ['.', '!', '?', '…'])) $s .= '.';
    return $s;
}

function capitaliser(string $s): string {
    if (empty($s)) return $s;
    return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
}

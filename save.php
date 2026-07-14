<?php
require_once __DIR__ . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Méthode non autorisée.'); }
$mot          = capitaliser(truncate($_POST['mot']          ?? '', 80));
$type         = trim($_POST['type_mot']                     ?? '');
$genre        = trim($_POST['genre_mot']                    ?? '');
$typeEntree   = trim($_POST['type_entree']                  ?? 'invente');
$etymologie   = ponctuer(truncate($_POST['etymologie']      ?? '', 600));
$definition1  = ponctuer(capitaliser(truncate($_POST['definition_1'] ?? '', 800)));
$registre1    = trim($_POST['registre_definition_1']        ?? '');
$definition2  = ponctuer(capitaliser(truncate($_POST['definition_2'] ?? '', 800)));
$exemple      = ponctuer(capitaliser(truncate($_POST['exemple']      ?? '', 600)));
$registresIds = $_POST['registres_experience']              ?? [];
$emailContrib = filter_var(trim($_POST['email_contributeur'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
$consentEmail = !empty($_POST['consent_email']);
if (!$consentEmail) $emailContrib = null;

if (!$mot || !$type || !$etymologie || !$definition1 || !$registre1 || !$exemple) {
    http_response_code(400);
    die('Tous les champs obligatoires doivent être remplis.');
}
if (!in_array($type, type_options(), true))       { http_response_code(400); die('Type grammatical invalide.'); }
if (!in_array($registre1, style_options(), true)) { http_response_code(400); die('Registre stylistique invalide.'); }
if (!in_array($typeEntree, ['invente','reactive','importe','ressuscite'], true)) {
    $typeEntree = 'invente';
}

try {
    $pdo  = db($config);
    $stmt = $pdo->prepare("
        INSERT INTO dictionnaire_mots
            (mot_original, type_original, genre_mot, type_entree, etymologie_originale,
             definition_1_originale, registre_definition_1,
             definition_2_originale, exemple_original, email_contributeur, statut)
        VALUES (:mot, :type, :genre, :type_entree, :etym, :def1, :reg1, :def2, :ex, :email, 'brouillon')
    ");
    $stmt->execute([
        ':mot'         => $mot,
        ':type'        => $type,
        ':genre'       => $genre,
        ':type_entree' => $typeEntree,
        ':etym'        => $etymologie,
        ':def1'        => $definition1,
        ':reg1'        => $registre1,
        ':def2'        => $definition2,
        ':ex'          => $exemple,
        ':email'       => $emailContrib,
    ]);
    $id = (int) $pdo->lastInsertId();
    sync_word_registers($pdo, $id, $registresIds);
    header('Location: analyse.php?id=' . $id);
    exit;
} catch (Throwable $e) {
    error_log('save.php: ' . $e->getMessage());
    die("Erreur lors de l'enregistrement.");
}

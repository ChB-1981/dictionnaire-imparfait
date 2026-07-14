<?php
require_once __DIR__ . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Méthode non autorisée.'); }
// csrf_verify(); // TODO: réactiver quand la gestion de session sera stabilisée
$id           = (int) ($_POST['id']                      ?? 0);
$mot          = capitaliser(truncate($_POST['mot']           ?? '', 80));
$type         = trim($_POST['type_mot']                  ?? '');
$genre        = trim($_POST['genre_mot']                 ?? '');
$typeEntree   = trim($_POST['type_entree']               ?? 'invente');
$etymologie   = ponctuer(trim($_POST['etymologie'] ?? ''));
$definition1  = ponctuer(capitaliser(trim($_POST['definition_1'] ?? '')));
$registre1    = trim($_POST['registre_definition_1']     ?? '');
$definition2  = ponctuer(capitaliser(trim($_POST['definition_2'] ?? '')));
$exemple      = ponctuer(capitaliser(trim($_POST['exemple'] ?? '')));
$registresIds = $_POST['registres_experience']           ?? [];

if (!$id) die('ID manquant.');
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
    $pdo = db($config);
    $pdo->prepare("
        UPDATE dictionnaire_mots SET
            mot_original           = :mot,
            type_original          = :type,
            genre_mot              = :genre,
            type_entree            = :type_entree,
            etymologie_originale   = :etym,
            definition_1_originale = :def1,
            registre_definition_1  = :reg1,
            definition_2_originale = :def2,
            exemple_original       = :ex,
            suggestions_ia         = NULL,
            score_total            = NULL,
            statut                 = 'brouillon',
            updated_at             = NOW()
        WHERE id = :id
    ")->execute([
        ':mot'         => $mot,
        ':type'        => $type,
        ':genre'       => $genre,
        ':type_entree' => $typeEntree,
        ':etym'        => $etymologie,
        ':def1'        => $definition1,
        ':reg1'        => $registre1,
        ':def2'        => $definition2,
        ':ex'          => $exemple,
        ':id'          => $id,
    ]);
    sync_word_registers($pdo, $id, $registresIds);
    header('Location: analyse.php?id=' . $id);
    exit;
} catch (Throwable $e) {
    error_log('update_original.php: ' . $e->getMessage());
    die("Erreur : " . $e->getMessage());
}

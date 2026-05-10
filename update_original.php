<?php
require_once __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Méthode non autorisée.');

$id           = (int) ($_POST['id']                      ?? 0);
$mot          = capitaliser(trim($_POST['mot']           ?? ''));
$type         = trim($_POST['type_mot']                  ?? '');
$etymologie   = ponctuer(trim($_POST['etymologie'] ?? ''));
$definition1  = ponctuer(capitaliser(trim($_POST['definition_1'] ?? '')));
$registre1    = trim($_POST['registre_definition_1']     ?? '');
$definition2  = ponctuer(capitaliser(trim($_POST['definition_2'] ?? '')));
$exemple      = ponctuer(capitaliser(trim($_POST['exemple'] ?? '')));
$registresIds = $_POST['registres_experience']           ?? [];

if (!$id) die('ID manquant.');
if (!$mot || !$type || !$etymologie || !$definition1 || !$registre1 || !$exemple)
    die('Tous les champs obligatoires doivent être remplis.');
if (!in_array($type, type_options(), true))       die('Type grammatical invalide.');
if (!in_array($registre1, style_options(), true)) die('Registre stylistique invalide.');

try {
    $pdo = db($config);
    $pdo->prepare("
        UPDATE dictionnaire_mots SET
            mot_original           = :mot,
            type_original          = :type,
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
        ':mot'  => $mot,
        ':type' => $type,
        ':etym' => $etymologie,
        ':def1' => $definition1,
        ':reg1' => $registre1,
        ':def2' => $definition2,
        ':ex'   => $exemple,
        ':id'   => $id,
    ]);
    sync_word_registers($pdo, $id, $registresIds);
    header('Location: analyse.php?id=' . $id);
    exit;
} catch (Throwable $e) {
    error_log('update_original.php: ' . $e->getMessage());
    die("Erreur : " . $e->getMessage());
}

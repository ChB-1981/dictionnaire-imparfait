<?php
/**
 * finalize.php — Validation définitive d'un mot.
 * Passe le mot en statut "en_attente" pour validation éditoriale humaine.
 */

require_once __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Méthode non autorisée.');

$id = (int) ($_POST['id'] ?? 0);
if (!$id) die('ID manquant.');

try {
    $pdo  = db($config);
    $stmt = $pdo->prepare('SELECT * FROM dictionnaire_mots WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $mot  = $stmt->fetch();

    if (!$mot) die('Mot introuvable.');
    if ($mot['statut'] !== 'brouillon') die('Ce mot ne peut pas être soumis dans son état actuel.');

    $analyse = json_decode($mot['suggestions_ia'] ?? '', true);

    if ($analyse) {
        foreach (($analyse['conformite'] ?? []) as $item) {
            if (($item['ok'] ?? true) === false) {
                die('Ce mot ne peut pas être soumis : un critère de conformité est bloquant.');
            }
        }
        foreach (($analyse['coherence'] ?? []) as $item) {
            if ((int) ($item['note'] ?? 1) === 0) {
                die('Ce mot ne peut pas être soumis : un critère de cohérence est à zéro.');
            }
        }
        foreach (($analyse['utilite'] ?? []) as $item) {
            if ((int) ($item['note'] ?? 1) === 0) {
                die('Ce mot ne peut pas être soumis : un critère d\'utilité est à zéro.');
            }
        }
    }

    if ((int) $mot['score_total'] < 16) {
        die('Score insuffisant pour soumettre ce mot (minimum 16/20).');
    }

    $pdo->prepare("
        UPDATE dictionnaire_mots
        SET statut = 'en_attente', updated_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $id]);

    // ── Notifier l'éditeur ──
    $emailEditeur = $config['contact']['email'] ?? '';
    if ($emailEditeur) {
        $sujet = '[Dictionnaire imparfait] Nouvelle proposition à valider : ' . $mot['mot_original'];
        $corps  = "Un nouveau mot a été soumis et attend votre validation.\n\n"
                . "Mot : " . $mot['mot_original'] . "\n"
                . "Type : " . $mot['type_original'] . "\n"
                . "Score : " . $mot['score_total'] . "/20\n\n"
                . "Voir l'analyse : https://" . $_SERVER['HTTP_HOST'] . "/analyse.php?id=" . $id . "&admin=1\n"
                . "Page d'administration : https://" . $_SERVER['HTTP_HOST'] . "/admin.php";
        $entetes = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        mail($emailEditeur, $sujet, $corps, $entetes);
    }

    header('Location: confirmation.php?id=' . $id);
    exit;

} catch (Throwable $e) {
    error_log('finalize.php: ' . $e->getMessage());
    die('Une erreur est survenue lors de la soumission.');
}

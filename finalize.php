<?php
/**
 * finalize.php — Validation définitive d'un mot.
 */

require_once __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Méthode non autorisée.');

$id = (int) ($_POST['id'] ?? 0);
if (!$id) die('ID manquant.');

try {
    $pdo  = db($config);
    $stmt = $pdo->prepare('SELECT suggestions_ia, score_total FROM dictionnaire_mots WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $mot  = $stmt->fetch();

    if (!$mot) die('Mot introuvable.');

    $analyse = json_decode($mot['suggestions_ia'] ?? '', true);

    if ($analyse) {
        // Vérifier les critères de conformité
        foreach (($analyse['conformite'] ?? []) as $item) {
            if (($item['ok'] ?? true) === false) {
                die('Ce mot ne peut pas être finalisé : un critère de conformité est bloquant.');
            }
        }
        // Vérifier qu'aucun critère n'est à 0
        foreach (($analyse['coherence'] ?? []) as $item) {
            if ((int) ($item['note'] ?? 1) === 0) {
                die('Ce mot ne peut pas être finalisé : un critère de cohérence est à zéro.');
            }
        }
        foreach (($analyse['utilite'] ?? []) as $item) {
            if ((int) ($item['note'] ?? 1) === 0) {
                die('Ce mot ne peut pas être finalisé : un critère d\'utilité est à zéro.');
            }
        }
    }

    if ((int) $mot['score_total'] < 14) {
        die('Score insuffisant pour finaliser ce mot (minimum 14/20).');
    }

    $pdo->prepare("
        UPDATE dictionnaire_mots
        SET statut = 'finalise', updated_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $id]);

    header('Location: dictionnaire.php');
    exit;

} catch (Throwable $e) {
    error_log('finalize.php: ' . $e->getMessage());
    die('Une erreur est survenue lors de la finalisation.');
}

<?php
/**
 * vote.php — Endpoint AJAX pour ajouter ou retirer un cœur.
 * Limité à 1 vote par IP et par mot.
 * Retourne du JSON : {"coeurs": N, "voted": true/false}
 */

require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant.']);
    exit;
}

// IP réelle même derrière un proxy
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip = trim(explode(',', $ip)[0]);
$ip = substr($ip, 0, 45);

// Rate limiting : max 30 votes par IP par heure
$cle = 'vote_' . md5($ip);
if (!isset($_SESSION[$cle])) $_SESSION[$cle] = ['count' => 0, 'reset' => time() + 3600];
if (time() > $_SESSION[$cle]['reset']) $_SESSION[$cle] = ['count' => 0, 'reset' => time() + 3600];
if ($_SESSION[$cle]['count'] >= 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Trop de votes. Réessayez plus tard.']);
    exit;
}
$_SESSION[$cle]['count']++;

$pdo = db($config);

// Vérifier que le mot existe et est finalisé
$stmt = $pdo->prepare('SELECT id, coeurs FROM dictionnaire_mots WHERE id = :id AND statut = "finalise"');
$stmt->execute([':id' => $id]);
$mot = $stmt->fetch();

if (!$mot) {
    http_response_code(404);
    echo json_encode(['error' => 'Mot introuvable.']);
    exit;
}

// Vérifier si l'IP a déjà voté
$stmt = $pdo->prepare('SELECT id FROM dictionnaire_votes WHERE mot_id = :mot_id AND ip = :ip');
$stmt->execute([':mot_id' => $id, ':ip' => $ip]);
$existingVote = $stmt->fetch();

if ($existingVote) {
    // Retirer le vote
    $pdo->prepare('DELETE FROM dictionnaire_votes WHERE mot_id = :mot_id AND ip = :ip')
        ->execute([':mot_id' => $id, ':ip' => $ip]);

    $pdo->prepare('UPDATE dictionnaire_mots SET coeurs = GREATEST(0, coeurs - 1) WHERE id = :id')
        ->execute([':id' => $id]);

    $voted = false;
} else {
    // Ajouter le vote
    $pdo->prepare('INSERT INTO dictionnaire_votes (mot_id, ip) VALUES (:mot_id, :ip)')
        ->execute([':mot_id' => $id, ':ip' => $ip]);

    $pdo->prepare('UPDATE dictionnaire_mots SET coeurs = coeurs + 1 WHERE id = :id')
        ->execute([':id' => $id]);

    $voted = true;
}

// Lire le nouveau total
$stmt = $pdo->prepare('SELECT coeurs FROM dictionnaire_mots WHERE id = :id');
$stmt->execute([':id' => $id]);
$coeurs = (int) $stmt->fetchColumn();

echo json_encode(['coeurs' => $coeurs, 'voted' => $voted]);

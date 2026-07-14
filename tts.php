<?php
/**
 * tts.php — Génère et streame l'audio d'un mot via OpenAI TTS.
 * Appelé en AJAX depuis dictionnaire.php.
 * Pas de stockage — tout est streamé à la volée.
 */

require_once __DIR__ . '/app/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('ID manquant.');
}

// Rate limiting TTS : max 20 lectures par session
if (!isset($_SESSION['tts_count'])) $_SESSION['tts_count'] = 0;
if ($_SESSION['tts_count'] >= 20) {
    http_response_code(429);
    exit('Limite de lectures atteinte.');
}
$_SESSION['tts_count']++;

$pdo  = db($config);
$stmt = $pdo->prepare("
    SELECT mot_original, type_original, etymologie_originale,
           definition_1_originale, registre_definition_1,
           definition_2_originale, exemple_original
    FROM dictionnaire_mots
    WHERE id = :id AND statut = 'finalise'
");
$stmt->execute([':id' => $id]);
$mot = $stmt->fetch();

if (!$mot) {
    http_response_code(404);
    exit('Mot introuvable.');
}

$apiKey = $config['openai']['api_key'] ?? '';
if (!$apiKey) {
    http_response_code(500);
    exit('Clé OpenAI manquante.');
}

// Construire le texte à lire
$texte  = $mot['mot_original'] . '. ';
$texte .= $mot['type_original'] . '. ';

if (!empty($mot['etymologie_originale'])) {
    $texte .= $mot['etymologie_originale'] . '. ';
}

$texte .= $mot['definition_1_originale'] . '. ';

if (!empty($mot['definition_2_originale'])) {
    $texte .= 'Par extension : ' . $mot['definition_2_originale'] . '. ';
}

if (!empty($mot['exemple_original'])) {
    $texte .= $mot['exemple_original'];
}

// Appel OpenAI TTS
$payload = json_encode([
    'model' => 'tts-1',
    'voice' => 'nova',
    'input' => $texte,
    'response_format' => 'mp3',
]);

$ch = curl_init('https://api.openai.com/v1/audio/speech');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $payload,
]);

$audio    = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error || $httpCode !== 200 || !$audio) {
    http_response_code(502);
    exit('Erreur TTS.');
}

// Streamer le MP3 directement
header('Content-Type: audio/mpeg');
header('Content-Length: ' . strlen($audio));
header('Cache-Control: no-store');
echo $audio;

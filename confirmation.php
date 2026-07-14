<?php
require_once __DIR__ . '/app/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) die('ID manquant.');

$pdo  = db($config);
$stmt = $pdo->prepare('SELECT mot_original FROM dictionnaire_mots WHERE id = :id');
$stmt->execute([':id' => $id]);
$mot  = $stmt->fetch();
if (!$mot) die('Mot introuvable.');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Proposition envoyée : Dictionnaire imparfait</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
</head>
<body>
<div class="container">

<header style="padding:2.5rem 0 1.75rem;border-bottom:2px solid var(--encre);margin-bottom:2.5rem;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem">
    <div>
        <h1 style="font-size:1.75rem;font-weight:normal;letter-spacing:.04em;margin:0 0 .2rem">
            Dictionnaire <span style="font-style:italic;color:var(--brun)">imparfait</span>
        </h1>
        <p style="font-style:italic;color:var(--brun-clair);font-size:var(--taille-sm);margin:0">Pour habiter le monde autrement.</p>
    </div>
    <?php $navPage = 'autre'; include __DIR__ . '/nav.php'; ?>
</header>

<div class="card" style="text-align:center;padding:3rem 2rem">
    <p style="font-size:1.5rem;font-weight:normal;margin:0 0 1rem;color:var(--encre)">
        <?= h($mot['mot_original']) ?>
    </p>
    <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.8;margin:0 0 2rem">
        Votre proposition a été transmise.<br>
        Elle sera examinée avant d'être publiée dans le dictionnaire.<br>
        <?php if (!empty($_GET['email'])): ?>
            Vous recevrez une notification à l'adresse indiquée.
        <?php endif; ?>
    </p>
    <a href="new.php" class="btn">Proposer un autre mot</a>
    &nbsp;
    <a href="dictionnaire.php" class="btn secondary">Lire le dictionnaire</a>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>

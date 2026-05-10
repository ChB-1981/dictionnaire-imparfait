<?php
require_once __DIR__ . '/app/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) die('ID manquant.');

$pdo  = db($config);
$stmt = $pdo->prepare('SELECT * FROM dictionnaire_mots WHERE id = :id');
$stmt->execute([':id' => $id]);
$mot  = $stmt->fetch();
if (!$mot) die('Mot introuvable.');

$registres = get_word_register_names($pdo, $id);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= h($mot['mot_original']) ?> — Dictionnaire imparfait</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .fiche-header {
            padding: 2.5rem 0 1.75rem;
            border-bottom: 2px solid var(--encre);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .fiche-mot {
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            font-weight: bold;
            margin: 0 0 0.2rem;
        }
        .fiche-type {
            font-style: italic;
            color: var(--brun);
            font-size: var(--taille-sm);
            margin: 0;
        }
        .fiche-etym {
            font-size: 0.82rem;
            color: var(--encre-doux);
            font-style: italic;
            margin: 0.15rem 0 0.5rem;
            line-height: 1.55;
        }
        .fiche-etym::before { content: "["; color: var(--brun-clair); margin-right: 0.1rem; }
        .fiche-etym::after  { content: "]"; color: var(--brun-clair); margin-left:  0.1rem; }
        .fiche-def { margin: 0 0 0.15rem; line-height: 1.7; }
        .fiche-abbr {
            font-variant: small-caps;
            font-size: 0.77rem;
            color: var(--brun-clair);
            margin-right: 0.5rem;
        }
        .fiche-extension {
            margin: 0.1rem 0 0.15rem;
            color: var(--encre-doux);
            line-height: 1.7;
        }
        .fiche-extension::before {
            content: "Par ext. ";
            font-style: italic;
            color: var(--brun-clair);
            font-size: var(--taille-xs);
        }
        .fiche-exemple {
            font-style: italic;
            color: var(--encre-doux);
            font-size: 0.9rem;
            margin: 0.5rem 0 0.5rem;
            line-height: 1.6;
        }
        .fiche-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin-top: 0.5rem;
        }
        @media (max-width: 600px) {
            .fiche-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

<header class="fiche-header">
    <div>
        <h1 class="fiche-mot"><?= h($mot['mot_original']) ?></h1>
        <p class="fiche-type"><?= h($mot['type_original']) ?></p>
    </div>
    <?php $navPage = 'autre'; include __DIR__ . '/nav.php'; ?>
</header>

<div class="card">
    <?php if (!empty($mot['etymologie_originale'])): ?>
        <p class="fiche-etym"><?= h($mot['etymologie_originale']) ?></p>
    <?php endif; ?>

    <p class="fiche-def">
        <span class="fiche-abbr"><?= h(style_abbr($mot['registre_definition_1'])) ?></span>
        <?= h($mot['definition_1_originale']) ?>
    </p>

    <?php if (!empty($mot['definition_2_originale'])): ?>
        <p class="fiche-extension"><?= h($mot['definition_2_originale']) ?></p>
    <?php endif; ?>

    <p class="fiche-exemple">« <?= h($mot['exemple_original']) ?> »</p>

    <?php if (!empty($registres)): ?>
        <div class="fiche-tags">
            <?php foreach ($registres as $r): ?>
                <span class="tag"><?= h($r) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/app/bootstrap.php';
$pdo  = db($config);
$stmt = $pdo->query("SELECT * FROM dictionnaire_ressources ORDER BY ordre DESC, created_at DESC");
$ressources = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ressources : Dictionnaire imparfait</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .page-header {
            padding: 2.5rem 0 1.75rem;
            border-bottom: 2px solid var(--encre);
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-titre { font-size: 1.75rem; font-weight: normal; letter-spacing: .04em; margin: 0 0 .2rem; }
        .page-titre-imparfait { font-style: italic; color: var(--brun); }
        .page-sous-titre { font-style: italic; color: var(--brun-clair); font-size: var(--taille-sm); margin: 0; }

        .ressources-intro {
            max-width: 560px;
            color: var(--encre-doux);
            line-height: 1.8;
            margin-bottom: 3rem;
            font-size: var(--taille-sm);
        }

        .ressources-grille {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .ressource-carte {
            border: 1px solid var(--bordure);
            border-radius: var(--rayon-lg);
            overflow: hidden;
            background: var(--fond-carte);
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s;
        }

        .ressource-carte:hover { border-color: var(--brun); }

        .ressource-image {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            display: block;
            background: var(--fond-tag);
        }

        .ressource-image-placeholder {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: var(--fond-doux);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ressource-image-placeholder span {
            font-size: 2rem;
            opacity: .25;
        }

        .ressource-corps {
            padding: 1.25rem 1.25rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .ressource-source {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .4rem;
        }

        .ressource-titre {
            font-size: 1rem;
            font-weight: 600;
            color: var(--encre);
            line-height: 1.4;
            margin: 0 0 .65rem;
        }

        .ressource-date {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
            margin: 0 0 .35rem;
        }

        .ressource-resume {
            font-size: var(--taille-xs);
            color: var(--encre-doux);
            line-height: 1.7;
            flex: 1;
            margin: 0 0 1.1rem;
        }

        .ressource-lien {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: var(--taille-xs);
            color: var(--brun);
            text-decoration: none;
            border-bottom: 1px solid var(--bordure-2);
            padding-bottom: .1rem;
            transition: color .12s, border-color .12s;
            align-self: flex-start;
        }

        .ressource-lien:hover { color: var(--encre); border-color: var(--encre); }

        .vide {
            padding: 3rem 0;
            color: var(--brun-clair);
            font-style: italic;
            text-align: center;
        }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .ressources-grille { grid-template-columns: 1fr; }
        }
    </style>
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
</head>
<body>
<div class="container">

<header class="page-header">
    <div>
        <h1 class="page-titre">Dictionnaire <span class="page-titre-imparfait">imparfait</span></h1>
        <p class="page-sous-titre">Pour habiter le monde autrement.</p>
    </div>
    <?php $navPage = 'ressources'; include __DIR__ . '/nav.php'; ?>
</header>

<p class="ressources-intro">
    Des textes, des essais, des recherches qui explorent pourquoi et comment notre langue
    doit évoluer pour mieux dire ce que nous vivons.
</p>

<?php if (empty($ressources)): ?>
    <div class="vide">Aucune ressource pour le moment.</div>
<?php else: ?>
    <div class="ressources-grille">
        <?php foreach ($ressources as $r): ?>
            <article class="ressource-carte">
                <?php if (!empty($r['image_url'])): ?>
                    <img src="<?= h($r['image_url']) ?>" alt="" class="ressource-image" loading="lazy">
                <?php else: ?>
                    <div class="ressource-image-placeholder"><span>◈</span></div>
                <?php endif; ?>

                <div class="ressource-corps">
                    <?php if (!empty($r['source'])): ?>
                        <p class="ressource-source"><?= h($r['source']) ?></p>
                    <?php endif; ?>
                    <h2 class="ressource-titre"><?= h($r['titre']) ?></h2>
                    <?php if (!empty($r['date_publication'])): ?>
                        <p class="ressource-date"><?= date('j F Y', strtotime($r['date_publication'])) ?></p>
                    <?php endif; ?>
                    <p class="ressource-resume"><?= h($r['resume']) ?></p>
                    <a href="<?= h($r['url']) ?>" class="ressource-lien" target="_blank" rel="noopener">
                        Lire l'article →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>

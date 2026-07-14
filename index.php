<?php
require_once __DIR__ . '/app/bootstrap.php';
$pdo    = db($config);
$nbMots = (int) $pdo->query("SELECT COUNT(*) FROM dictionnaire_mots WHERE statut = 'finalise'")->fetchColumn();

// ── 3 dernières ressources ──
try {
    $resVedette = $pdo->query("SELECT * FROM dictionnaire_ressources ORDER BY ordre DESC, created_at DESC LIMIT 2")->fetchAll();
} catch (Throwable $e) {
    $resVedette = [];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dictionnaire imparfait</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .accueil {
            padding: 4rem 0 0;
            max-width: 560px;
        }

        .accueil-titre {
            font-size: 3rem;
            font-weight: normal;
            letter-spacing: 0.02em;
            margin: 0 0 0.2rem;
            line-height: 1.1;
            white-space: nowrap;
            text-align: left;
        }

        .accueil-titre-imparfait {
            font-style: italic;
            color: var(--brun);
        }

        .accueil-sous-titre {
            font-style: italic;
            color: var(--brun-clair);
            font-size: 1rem;
            margin: 0 0 2rem;
            text-align: left;
        }

        .accueil-intro {
            line-height: 1.85;
            color: var(--encre-doux);
            font-size: 0.97rem;
            margin-bottom: 2rem;
        }

        .accueil-intro p { margin: 0 0 0.85rem; }
        .accueil-intro p:last-child { margin: 0; }

        .accueil-cta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .accueil-cta .btn { padding: 0.7rem 1.6rem; }

        .accueil-compteur {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
            margin: 1rem 0 1.5rem;
        }

        .accueil-sep {
            border: none;
            border-top: 1px solid var(--bordure);
            margin: 1.5rem 0;
        }

        .principes { margin: 0; }

        .principes-label {
            font-size: var(--taille-xs);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--brun-clair);
            margin: 0 0 1.25rem;
            display: block;
        }

        .principe { margin-bottom: 1.25rem; }
        .principe:last-child { margin-bottom: 0; }

        .principe-mot {
            font-size: var(--taille-sm);
            font-weight: 600;
            color: var(--encre);
            margin: 0 0 0.15rem;
        }

        .principe-corps {
            font-size: var(--taille-sm);
            color: var(--encre-doux);
            line-height: 1.7;
            margin: 0;
        }

        /* ── Ressources en vedette ── */
        .ressources-vedette {
            margin-top: 3.5rem;
            padding-top: 2.5rem;
            border-top: 1px solid var(--bordure);
        }

        .ressources-vedette-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 1.5rem;
        }

        .ressources-vedette-label {
            font-size: var(--taille-xs);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--brun-clair);
        }

        .ressources-vedette-lien {
            font-size: var(--taille-xs);
            color: var(--brun);
            text-decoration: none;
        }

        .ressources-vedette-lien:hover { color: var(--encre); }

        .ressources-grille {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
        }

        .ressource-carte {
            border: 1px solid var(--bordure);
            border-radius: var(--rayon-lg);
            overflow: hidden;
            background: var(--fond-carte);
            display: flex;
            flex-direction: column;
            text-decoration: none;
            transition: border-color 0.15s;
        }

        .ressource-carte:hover { border-color: var(--brun); }

        .ressource-image {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            display: block;
        }

        .ressource-placeholder {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: var(--fond-doux);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brun-clair);
            font-size: 1.5rem;
            opacity: .3;
        }

        .ressource-corps {
            padding: 1rem 1rem 0.9rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .ressource-source {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin: 0 0 .3rem;
        }

        .ressource-titre {
            font-size: var(--taille-sm);
            font-weight: 600;
            color: var(--encre);
            line-height: 1.4;
            margin: 0 0 .5rem;
        }

        .ressource-resume {
            font-size: var(--taille-xs);
            color: var(--encre-doux);
            line-height: 1.65;
            margin: 0;
            flex: 1;
        }

        @media (max-width: 640px) {
            .accueil-titre { font-size: 2.2rem; white-space: normal; }
            .accueil-cta { flex-direction: column; }
            .accueil-cta .btn { text-align: center; }
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
<section class="accueil">

    <h1 class="accueil-titre">
        Dictionnaire <span class="accueil-titre-imparfait">imparfait</span>
    </h1>
    <p class="accueil-sous-titre">Pour habiter le monde autrement.</p>

    <div class="accueil-intro">
        <p>Il est des choses innommées. Des manières d&#8217;être au monde que la langue française, malgré sa richesse, laisse encore dans l&#8217;ombre.</p>
        <p>Ce dictionnaire est né de ce manque. Ici, on invente des mots. Chacun peut en proposer. Les mots qu'il contient vivent par ceux qui les utilisent.</p>
    </div>

    <div class="accueil-cta">
        <a class="btn" href="new.php">Proposer un mot</a>
        <a class="btn secondary" href="dictionnaire.php">Lire le dictionnaire</a>
    </div>

    <?php if ($nbMots > 0): ?>
        <p class="accueil-compteur"><?= $nbMots ?> mot<?= $nbMots > 1 ? 's' : '' ?> au dictionnaire.</p>
    <?php else: ?>
        <p class="accueil-compteur">Le dictionnaire attend ses premiers mots.</p>
    <?php endif; ?>

    <hr class="accueil-sep">

    <div class="principes">
        <span class="principes-label">Trois principes</span>

        <div class="principe">
            <p class="principe-mot">L&#8217;être avant l&#8217;avoir.</p>
            <p class="principe-corps">Les mots de ce dictionnaire cherchent moins à désigner des objets qu'à exprimer des sensations, des perceptions et des manières d'habiter le monde.</p>
        </div>
        <div class="principe">
            <p class="principe-mot">L&#8217;humain avant la machine.</p>
            <p class="principe-corps">L'intelligence artificielle générative est utilisée : c'est un parti pris. L'outil analyse et suggère mais ne décide pas. La création comme la validation demeurent humaine.</p>
        </div>
        <div class="principe">
            <p class="principe-mot">Le mot avant le nom.</p>
            <p class="principe-corps">Aucun compte. Aucune signature. Aucun auteur ne peut revendiquer un mot comme une propriété. Un mot proposé n'existe que par celles et ceux qui l'emploient.</p>
        </div>
    </div>

    <!-- ── Ressources en vedette ── -->
    <?php if (!empty($resVedette)): ?>
    <div class="ressources-vedette">
        <div class="ressources-vedette-header">
            <span class="ressources-vedette-label">Pour aller plus loin</span>
            <a href="ressources.php" class="ressources-vedette-lien">Toutes les ressources →</a>
        </div>
        <div class="ressources-grille">
            <?php foreach ($resVedette as $r): ?>
                <a href="<?= h($r['url']) ?>" class="ressource-carte" target="_blank" rel="noopener">
                    <?php if (!empty($r['image_url'])): ?>
                        <img src="<?= h($r['image_url']) ?>" alt="" class="ressource-image" loading="lazy">
                    <?php else: ?>
                        <div class="ressource-placeholder">◈</div>
                    <?php endif; ?>
                    <div class="ressource-corps">
                        <?php if (!empty($r['source'])): ?>
                            <p class="ressource-source"><?= h($r['source']) ?></p>
                        <?php endif; ?>
                        <p class="ressource-titre"><?= h($r['titre']) ?></p>
                        <?php if (!empty($r['date_publication'])): ?>
                            <p style="font-size:var(--taille-xs);color:var(--brun-clair);font-style:italic;margin:0 0 .3rem">
                                <?= date('j F Y', strtotime($r['date_publication'])) ?>
                            </p>
                        <?php endif; ?>
                        <p class="ressource-resume"><?= h($r['resume']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</section>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>

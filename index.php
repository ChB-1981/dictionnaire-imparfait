<?php
require_once __DIR__ . '/app/bootstrap.php';
$pdo    = db($config);
$nbMots = (int) $pdo->query("SELECT COUNT(*) FROM dictionnaire_mots WHERE statut = 'finalise'")->fetchColumn();
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

        @media (max-width: 640px) {
            .accueil-titre { font-size: 2.2rem; white-space: normal; }
            .accueil-cta { flex-direction: column; }
            .accueil-cta .btn { text-align: center; }
        }
    </style>
</head>
<body>
<div class="container">
<section class="accueil">

    <h1 class="accueil-titre">
        Dictionnaire <span class="accueil-titre-imparfait">imparfait</span>
    </h1>
    <p class="accueil-sous-titre">Pour habiter le monde autrement.</p>

    <div class="accueil-intro">
        <p>Il est des choses innommées. Des émotions fugitives. Des manières d&#8217;être au monde que la langue française, malgré sa richesse, laisse encore dans l&#8217;ombre.</p>
        <p>Ce dictionnaire est né de ce manque. Ici, on invente des mots. Chacun peut en proposer. Les mots qu&#8217;il contient appartiennent &agrave; ceux qui les emploient.</p>
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
            <p class="principe-corps">Un mot proposé doit révéler une expérience vécue, une sensation, une manière d’habiter le monde. Il ne se contente pas de désigner un objet ou une technique.</p>
        </div>
        <div class="principe">
            <p class="principe-mot">L&#8217;humain avant la machine.</p>
            <p class="principe-corps">L’intelligence artificielle générative est utilisée : c’est un parti pris. Elle analyse et suggère, mais ne décide pas. La création comme la validation demeurent humaine.</p>
        </div>
        <div class="principe">
            <p class="principe-mot">Le mot avant le nom.</p>
            <p class="principe-corps">Aucun compte. Aucune signature. Aucun auteur ne peut revendiquer un mot comme une propriété. Un mot proposé n'’existe que par celles et ceux qui l’emploient.</p>
        </div>
    </div>

</section>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>
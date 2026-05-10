<?php
require_once __DIR__ . '/app/bootstrap.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) die('ID manquant.');

$pdo  = db($config);
$stmt = $pdo->prepare('SELECT * FROM dictionnaire_mots WHERE id = :id');
$stmt->execute([':id' => $id]);
$mot  = $stmt->fetch();
if (!$mot) die('Mot introuvable.');

$registres = get_all_experience_registers($pdo);
$selected  = get_word_register_ids($pdo, $id);

$fromSuggestions = !empty($_POST['from_suggestions']);
$valEtym = $fromSuggestions && !empty($_POST['appliquer_etymologie'])
    ? trim($_POST['etymologie_sugg'] ?? '') : $mot['etymologie_originale'];
$valDef1 = $fromSuggestions && !empty($_POST['appliquer_definition'])
    ? trim($_POST['definition_sugg'] ?? '') : $mot['definition_1_originale'];
$valEx = $fromSuggestions && !empty($_POST['appliquer_exemple'])
    ? trim($_POST['exemple_sugg'] ?? '') : $mot['exemple_original'];
$valDef2 = $fromSuggestions && !empty($_POST['appliquer_extension'])
    ? trim($_POST['extension_sugg'] ?? '') : $mot['definition_2_originale'];

$hasPrerempli = $fromSuggestions && (
    !empty($_POST['appliquer_etymologie']) ||
    !empty($_POST['appliquer_definition']) ||
    !empty($_POST['appliquer_exemple']) ||
    !empty($_POST['appliquer_extension'])
);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Modifier : <?= h($mot['mot_original']) ?> — Dictionnaire imparfait</title>
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

        /* Sections */
        .form-section { margin-bottom: 2rem; }
        .form-section-titre {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--brun-clair);
            margin: 0 0 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--bordure);
        }

        /* Optionnel */
        .optionnel {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-weight: normal;
            font-style: italic;
            margin-left: 0.3rem;
        }

        /* Convention M */
        .convention-m {
            font-size: var(--taille-xs);
            color: var(--brun);
            font-style: italic;
            margin-bottom: 0.5rem;
            line-height: 1.55;
        }

        /* Hint select multiple */
        .select-hint {
            display: block;
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
            margin-top: -0.9rem;
            margin-bottom: 1.25rem;
        }

        /* Champ pré-rempli */
        .prerempli {
            border-color: var(--brun) !important;
            background: var(--fond-doux) !important;
        }

        .prerempli-badge {
            display: inline-block;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--brun);
            background: var(--fond-tag);
            border-radius: 999px;
            padding: .1rem .5rem;
            margin-left: .4rem;
            vertical-align: middle;
        }

        .info-suggestion {
            font-size: var(--taille-xs);
            color: var(--brun);
            font-style: italic;
            margin-bottom: 1.25rem;
            line-height: 1.6;
            padding: .75rem 1rem;
            background: var(--fond-doux);
            border-radius: var(--rayon);
            border-left: 3px solid var(--bordure-2);
        }

        /* Actions */
        .form-actions {
            padding-top: 1.5rem;
            border-top: 1px solid var(--bordure);
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }

        .analyse-loading {
            display: none;
            font-size: var(--taille-xs);
            color: var(--brun);
            font-style: italic;
            margin-top: .5rem;
        }
        .prompt-toggle {
            all: unset;
            box-sizing: border-box;
            font-family: Georgia, serif;
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
            display: block;
            margin-top: 1.5rem;
        }
        .prompt-toggle:hover { color: var(--brun); }
        .prompt-bloc {
            display: none;
            margin-top: .75rem;
            background: var(--fond-doux);
            border: 1px solid var(--bordure);
            border-radius: var(--rayon);
            padding: 1rem 1.25rem;
        }
        .prompt-bloc.open { display: block; }
        .prompt-bloc pre {
            font-family: "Courier New", monospace;
            font-size: .7rem;
            color: var(--encre-doux);
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
        }
        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .form-actions { flex-direction: column; }
            .form-actions .btn { text-align: center; width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">

<header class="page-header">
    <div>
        <h1 class="page-titre">Dictionnaire <span class="page-titre-imparfait">imparfait</span></h1>
        <p class="page-sous-titre">Pour habiter le monde autrement.</p>
    </div>
    <?php $navPage = 'autre'; include __DIR__ . '/nav.php'; ?>
</header>

<form method="post" action="update_original.php">
<div class="card">

    <?php if ($hasPrerempli): ?>
        <p class="info-suggestion">
            Les champs marqués <span class="prerempli-badge">suggestion</span> ont été pré-remplis avec les reformulations proposées.
            Vérifiez, ajustez si besoin, puis enregistrez.
        </p>
    <?php endif; ?>

    <!-- Le mot -->
    <div class="form-section">
        <p class="form-section-titre">Le mot</p>

        <input type="hidden" name="id" value="<?= h($id) ?>">

        <label>Votre mot</label>
        <input name="mot" value="<?= h($mot['mot_original']) ?>" required>

        <label>Nature grammaticale</label>
        <select name="type_mot" required>
            <?php foreach (type_options() as $opt): ?>
                <option value="<?= h($opt) ?>" <?= $opt === $mot['type_original'] ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Registres d'expérience <span class="requis">*</span></label>
        <select name="registres_experience[]" multiple>
            <?php foreach ($registres as $r): ?>
                <option value="<?= h($r['id']) ?>" <?= in_array((int)$r['id'], $selected, true) ? 'selected' : '' ?>>
                    <?= h($r['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="select-hint">Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs registres</span>
    </div>

    <!-- Étymologie -->
    <div class="form-section">
        <p class="form-section-titre">
            Étymologie
            <?php if ($fromSuggestions && !empty($_POST['appliquer_etymologie'])): ?>
                <span class="prerempli-badge">suggestion</span>
            <?php endif; ?>
        </p>
        <textarea name="etymologie" required
            class="<?= ($fromSuggestions && !empty($_POST['appliquer_etymologie'])) ? 'prerempli' : '' ?>"
        ><?= h($valEtym) ?></textarea>
    </div>

    <!-- Définition -->
    <div class="form-section">
        <p class="form-section-titre">Définition</p>

        <label>
            Sens principal
            <?php if ($fromSuggestions && !empty($_POST['appliquer_definition'])): ?>
                <span class="prerempli-badge">suggestion</span>
            <?php endif; ?>
        </label>
        <textarea name="definition_1" required
            class="<?= ($fromSuggestions && !empty($_POST['appliquer_definition'])) ? 'prerempli' : '' ?>"
        ><?= h($valDef1) ?></textarea>

        <label>Registre stylistique</label>
        <select name="registre_definition_1" required>
            <?php foreach (style_options() as $opt): ?>
                <option value="<?= h($opt) ?>" <?= $opt === $mot['registre_definition_1'] ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label>
            Par extension <span class="optionnel">optionnel, un sens second plus large ou métaphorique</span>
            <?php if ($fromSuggestions && !empty($_POST['appliquer_extension'])): ?>
                <span class="prerempli-badge">suggestion</span>
            <?php endif; ?>
        </label>
        <textarea name="definition_2"
            class="<?= ($fromSuggestions && !empty($_POST['appliquer_extension'])) ? 'prerempli' : '' ?>"
        ><?= h($valDef2) ?></textarea>
    </div>

    <!-- Exemple -->
    <div class="form-section">
        <p class="form-section-titre">
            Exemple d'usage
            <?php if ($fromSuggestions && !empty($_POST['appliquer_exemple'])): ?>
                <span class="prerempli-badge">suggestion</span>
            <?php endif; ?>
        </p>
        <p class="convention-m">
            Par une convention mystérieuse de ce dictionnaire,
            l'exemple fait toujours apparaître un prénom commençant par <strong>M</strong>.
        </p>
        <textarea name="exemple" required
            class="<?= ($fromSuggestions && !empty($_POST['appliquer_exemple'])) ? 'prerempli' : '' ?>"
        ><?= h($valEx) ?></textarea>
    </div>

    <!-- Actions -->
    <div class="form-actions">
        <a href="analyse.php?id=<?= h($id) ?>" class="btn secondary">Annuler</a>
        <button type="submit" class="btn" id="btn-analyser" onclick="lancerAnalyse()">Enregistrer et réanalyser</button>
    </div>
    <p class="analyse-loading" id="msg-loading">Analyse en cours, cela peut prendre quelques secondes…</p>

    <!-- Prompt dépliable -->
    <button type="button" class="prompt-toggle" onclick="document.getElementById('prompt-edit').classList.toggle('open'); this.textContent = this.textContent.includes('Voir') ? 'Masquer les critères d\'analyse ←' : 'Voir les critères d\'analyse →'">
        Voir les critères d'analyse →
    </button>
    <div class="prompt-bloc" id="prompt-edit">
        <?php
        $promptTexte   = require __DIR__ . '/prompt.php';
        $promptAffiche = preg_replace('/\{\$[^}]+\}/', '…', $promptTexte);
        ?>
        <pre><?= h($promptAffiche) ?></pre>
    </div>

</div>
</form>

<?php include __DIR__ . '/footer.php'; ?>
</div>
<script>
function lancerAnalyse() {
    var form = document.querySelector('form');
    if (!form.checkValidity()) return;
    setTimeout(function() {
        document.getElementById('msg-loading').style.display = 'block';
        document.getElementById('btn-analyser').disabled = true;
    }, 50);
}
</script>
</body>
</html>
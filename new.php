<?php
require_once __DIR__ . '/app/bootstrap.php';
$pdo       = db($config);
$registres = get_all_experience_registers($pdo);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Proposer un mot : Dictionnaire imparfait</title>
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

        .page-titre {
            font-size: 1.75rem;
            font-weight: normal;
            letter-spacing: 0.04em;
            margin: 0 0 0.2rem;
        }

        .page-titre-imparfait { font-style: italic; color: var(--brun); }

        .page-sous-titre {
            font-style: italic;
            color: var(--brun-clair);
            font-size: var(--taille-sm);
            margin: 0;
        }

        .page-header-actions { display: flex; gap: 0.75rem; align-items: center; }

        /* ── Sections ── */
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

        /* ── Obligatoire ── */
        .requis {
            color: #c0392b;
            margin-left: 0.15rem;
            font-size: 0.85rem;
        }

        /* ── Label + info ── */
        .label-row {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.3rem;
        }

        .label-row label { margin-bottom: 0; }

        .info-btn {
            all: unset !important;
            box-sizing: border-box !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 15px !important;
            height: 15px !important;
            border-radius: 50% !important;
            border: 1px solid var(--bordure-2) !important;
            background: none !important;
            color: var(--brun-clair) !important;
            font-size: 10px !important;
            font-family: Georgia, serif !important;
            font-style: italic !important;
            font-weight: bold !important;
            font-style: italic !important;
            cursor: pointer !important;
            line-height: 1 !important;
            vertical-align: middle !important;
            transition: all 0.12s !important;
        }

        .info-btn:hover {
            background: var(--fond-tag) !important;
            border-color: var(--brun) !important;
            color: var(--encre) !important;
        }

        .info-panel {
            display: none;
            background: var(--fond-doux);
            border: 1px solid var(--bordure);
            border-radius: var(--rayon);
            padding: 0.9rem 1.1rem;
            margin-bottom: 0.75rem;
            font-size: var(--taille-xs);
            line-height: 1.75;
            color: var(--encre-doux);
        }

        .info-panel.open { display: block; }
        .info-panel p { margin: 0 0 0.4rem; }
        .info-panel p:last-child { margin: 0; }
        .info-panel strong { color: var(--encre); }

        /* ── Genre conditionnel ── */
        #bloc-genre { display: block; }
        #bloc-genre.hidden { display: none; }

        /* ── Optionnel ── */
        .optionnel {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-weight: normal;
            font-style: italic;
            margin-left: 0.3rem;
        }

        /* ── Convention M ── */
        .convention-m {
            font-size: var(--taille-xs);
            color: var(--brun);
            font-style: italic;
            margin-bottom: 0.5rem;
            line-height: 1.55;
        }

        /* ── Hint select multiple ── */
        .select-hint {
            display: block;
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
            margin-top: -0.9rem;
            margin-bottom: 1.25rem;
        }

        /* ── Actions ── */
        .form-actions {
            padding-top: 1.5rem;
            border-top: 1px solid var(--bordure);
            margin-top: 0.5rem;
        }

        .form-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        /* Message d'attente */
        .analyse-loading {
            display: none;
            font-size: var(--taille-xs);
            color: var(--brun);
            font-style: italic;
            margin-top: .5rem;
        }

        /* Prompt dépliable */
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
            .page-header-actions { width: 100%; }
            .page-header-actions .btn { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>
<div class="container">

<header class="page-header">
    <div>
        <h1 class="page-titre">
            Dictionnaire <span class="page-titre-imparfait">imparfait</span>
        </h1>
        <p class="page-sous-titre">Pour habiter le monde autrement.</p>
    </div>
    <?php $navPage = 'new'; include __DIR__ . '/nav.php'; ?>
</header>

<form method="post" action="save.php">
<div class="card">

    <!-- ══ Le mot ══ -->
    <div class="form-section">
        <p class="form-section-titre">Le mot</p>

        <label>Votre mot <span class="requis">*</span></label>
        <input name="mot" required placeholder="Ex. Imparfait">

        <!-- Nature grammaticale -->
        <div class="label-row">
            <label for="champ-type">Nature grammaticale <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-type')" ><em>i</em></button>
        </div>
        <div class="info-panel" id="info-type">
            <p><strong>Nom commun :</strong> désigne une chose, un être ou un sentiment. <em>Ex. spleen (mélancolie vague), saudade (nostalgie portugaise)</em></p>
            <p><strong>Verbe transitif :</strong> exprime une action qui porte sur quelque chose ou quelqu'un. <em>Ex. flâner dans une ville, savourer un instant</em></p>
            <p><strong>Verbe intransitif :</strong> exprime une action sans objet. <em>Ex. rêvasser, musarder, errer</em></p>
            <p><strong>Verbe pronominal :</strong> se conjugue avec "se". <em>Ex. se morfondre, se languir, s'épanouir</em></p>
            <p><strong>Adjectif :</strong> qualifie un nom, décrit une qualité ou un état. <em>Ex. mélancolique, indicible, furtif</em></p>
            <p><strong>Adverbe :</strong> modifie un verbe ou un adjectif. <em>Ex. furtivement, langoureusement</em></p>
            <p><strong>Locution nominale :</strong> groupe de mots qui fonctionne comme un nom. <em>Ex. "art de vivre", "joie de vivre"</em></p>
            <p><strong>Locution verbale :</strong> groupe de mots qui fonctionne comme un verbe. <em>Ex. "prendre le large", "faire le deuil"</em></p>
            <p><strong>Interjection :</strong> exclamation qui exprime un sentiment vif. <em>Ex. Hélas ! Zut ! Chut !</em></p>
        </div>
        <select name="type_mot" id="champ-type" required onchange="gererGenre(this.value)">
            <?php foreach (type_options() as $opt): ?>
                <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Genre — visible si nom commun -->
        <div id="bloc-genre">
            <label>Genre <span class="optionnel">optionnel</span></label>
            <select name="genre_mot">
                <option value="">— Non précisé —</option>
                <option value="masculin">Masculin</option>
                <option value="féminin">Féminin</option>
                <option value="masculin et féminin">Masculin et féminin</option>
            </select>
        </div>

        <!-- Registres d'expérience -->
        <div class="label-row">
            <label>Registres d'expérience <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-registres')" ><em>i</em></button>
        </div>
        <div class="info-panel" id="info-registres">
            <p>Les registres d'expérience indiquent <strong>quelle dimension de l'existence</strong> votre mot explore. Ils décrivent la manière dont il touche à notre façon d'être au monde. Plusieurs choix sont possibles.</p>
            <p><strong>Rapport à soi :</strong> ce qu'on ressent intérieurement, sa propre identité, ses états d'âme profonds.</p>
            <p><strong>Rapport au corps :</strong> les sensations physiques, la présence au monde par le corps.</p>
            <p><strong>Rapport aux émotions :</strong> les états affectifs, ce qu'on éprouve sans toujours savoir le nommer.</p>
            <p><strong>Rapport au désir :</strong> l'élan vers quelque chose ou quelqu'un, le manque actif.</p>
            <p><strong>Rapport à la mémoire :</strong> le souvenir, la réminiscence, ce qui revient du passé.</p>
            <p><strong>Rapport aux autres :</strong> la relation, la présence de l'autre, ce que les autres nous font.</p>
            <p><strong>Rapport à la parole :</strong> ce qu'on dit, ce qu'on tait, la difficulté ou la facilité à s'exprimer.</p>
            <p><strong>Rapport au lieu :</strong> l'attachement à un espace, le sentiment d'appartenir quelque part.</p>
            <p><strong>Rapport au temps :</strong> la durée, l'attente, la fugacité, le passage des choses.</p>
            <p><strong>Rapport au quotidien :</strong> les gestes ordinaires, ce qui revient chaque jour, le banal habité.</p>
            <p><strong>Rapport à l'absence :</strong> ce qui manque, ce qui est parti, le vide laissé par quelqu'un ou quelque chose.</p>
            <p><strong>Rapport au manque :</strong> l'incomplétude, la sensation qu'il manque quelque chose sans savoir quoi.</p>
            <p><strong>Rapport à l'invisible :</strong> ce qu'on devine sans le voir, le pressentiment, l'imperceptible.</p>
            <p><strong>Rapport à l'attention :</strong> la capacité à être présent, à remarquer, à observer finement le monde.</p>
            <p><strong>Rapport au vivant :</strong> les relations avec les animaux, les plantes et le non-humain. Ce que le monde vivant nous fait ressentir.</p>
            <p><strong>Rapport à la beauté :</strong> la rencontre avec ce qui nous traverse, nous arrête, nous élève, qu'on ne sait pas toujours nommer.</p>
        </div>
        <select name="registres_experience[]" multiple>
            <?php foreach ($registres as $r): ?>
                <option value="<?= h($r['id']) ?>"><?= h($r['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="select-hint">Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs registres</span>
    </div>

    <!-- ══ Étymologie ══ -->
    <div class="form-section">
        <p class="form-section-titre">Étymologie</p>
        <div class="label-row">
            <label>Origine du mot <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-etymologie')"><em>i</em></button>
        </div>
        <div class="info-panel" id="info-etymologie">
            <p>L'étymologie donne au mot une origine — réelle, probable ou inventée avec soin. Elle doit être cohérente avec le mot et construite selon ce format :</p>
            <p><strong>Commence par :</strong> "Du", "De l'", "De", "Emprunté à", "Altération de", "Formé sur"</p>
            <p><strong>Le mot source :</strong> en italique dans l'affichage, suivi de sa traduction entre guillemets « »</p>
            <p><strong>Pas de :</strong> parenthèses, +, →. Utilisez "croisé avec" ou "combiné à" pour les mots composés.</p>
            <p><strong>Se termine par un point.</strong></p>
            <p><em>Exemple : Du tagalog hugot (« tirer, extraire »), désignant l'acte de puiser une émotion enfouie.</em></p>
            <p><em>Exemple : Formé sur le latin somnium (« rêve »), croisé avec le français somme.</em></p>
        </div>
        <textarea name="etymologie" required
            placeholder="Ex. Du latin imperfectus (« inachevé, incomplet »), désignant ce qui reste en devenir, jamais tout à fait accompli."></textarea>
    </div>

    <!-- ══ Définition ══ -->
    <div class="form-section">
        <p class="form-section-titre">Définition</p>

        <label>Sens principal <span class="requis">*</span></label>
        <textarea name="definition_1" required placeholder="Le sens essentiel du mot…"></textarea>

        <!-- Registre stylistique EN DESSOUS -->
        <div class="label-row">
            <label for="champ-registre1">Registre stylistique <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-registre-stylo')" ><em>i</em></button>
        </div>
        <div class="info-panel" id="info-registre-stylo">
            <p>Le registre stylistique décrit <strong>le ton et le niveau de langue</strong> de votre définition.</p>
            <p><strong>Courant :</strong> langue de tous les jours, accessible à tous. <em>Ex. "Se dit de quelqu'un qui…"</em></p>
            <p><strong>Littéraire :</strong> langue soignée et élaborée, proche de l'écrit. <em>Ex. "Désigne l'état d'âme de celui qui…"</em></p>
            <p><strong>Familier :</strong> langue parlée, décontractée, proche de l'oral. <em>Ex. "Quand on n'arrête pas de…"</em></p>
            <p><strong>Poétique :</strong> langue imagée et évocatrice, qui suggère plus qu'elle ne dit.</p>
            <p><strong>Philosophique :</strong> langue conceptuelle et abstraite, propre à la réflexion.</p>
            <p><strong>Ironique :</strong> définition qui dit le contraire de ce qu'elle semble affirmer.</p>
            <p><strong>Humoristique :</strong> définition légère, qui joue avec le sens ou la situation.</p>
            <p><strong>Intime :</strong> langue douce et personnelle, proche du ressenti intérieur.</p>
            <p><strong>Oral :</strong> langue spontanée, comme si on parlait à voix haute.</p>
            <p><strong>Technique :</strong> langue précise et spécialisée, propre à un domaine.</p>
            <p><strong>Juridique :</strong> langue formelle et normative.</p>
            <p><strong>Administratif :</strong> langue officielle et réglementaire.</p>
        </div>
        <select name="registre_definition_1" id="champ-registre1" required>
            <?php foreach (style_options() as $opt): ?>
                <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label>
            Par extension <span class="optionnel">optionnel, un sens second plus large ou métaphorique</span>
        </label>
        <textarea name="definition_2" placeholder="Par extension, se dit de…"></textarea>
    </div>

    <!-- ══ Exemple ══ -->
    <div class="form-section">
        <p class="form-section-titre">Exemple d'usage</p>
        <p class="convention-m">
            Par une convention mystérieuse de ce dictionnaire,
            l'exemple fait toujours apparaître un prénom commençant par <strong>M</strong>.
        </p>
        <textarea name="exemple" required
            placeholder="Ex. Marion avait fini par aimer ce mot, imparfait, comme on aime quelqu'un pour ce qui lui manque."></textarea>
    </div>

    <!-- ══ Actions ══ -->
    <div class="form-actions">
        <button type="submit" class="btn" id="btn-analyser" onclick="lancerAnalyse()">Lancer l'analyse</button>
        <p class="analyse-loading" id="msg-loading">Analyse en cours, cela peut prendre quelques secondes…</p>
    </div>

    <!-- Prompt dépliable -->
    <button type="button" class="prompt-toggle" onclick="document.getElementById('prompt-new').classList.toggle('open'); this.textContent = this.textContent.includes('Voir') ? 'Masquer les critères d\'analyse ←' : 'Voir les critères d\'analyse →'">
        Voir les critères d'analyse →
    </button>
    <div class="prompt-bloc" id="prompt-new">
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

function toggleInfo(id) {
    document.getElementById(id).classList.toggle('open');
}

function gererGenre(type) {
    var bloc = document.getElementById('bloc-genre');
    if (type === 'nom commun') {
        bloc.classList.remove('hidden');
    } else {
        bloc.classList.add('hidden');
    }
}

window.addEventListener('DOMContentLoaded', function() {
    gererGenre(document.getElementById('champ-type').value);
});
</script>
</body>
</html>
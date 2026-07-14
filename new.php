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
        .page-titre { font-size: 1.75rem; font-weight: normal; letter-spacing: 0.04em; margin: 0 0 0.2rem; }
        .page-titre-imparfait { font-style: italic; color: var(--brun); }
        .page-sous-titre { font-style: italic; color: var(--brun-clair); font-size: var(--taille-sm); margin: 0; }
        .page-header-actions { display: flex; gap: 0.75rem; align-items: center; }
        .form-section { margin-bottom: 2rem; }
        .form-section-titre {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--brun-clair); margin: 0 0 1.25rem;
            padding-bottom: 0.5rem; border-bottom: 1px solid var(--bordure);
        }
        .requis { color: #c0392b; margin-left: 0.15rem; font-size: 0.85rem; }
        .label-row { display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.3rem; }
        .label-row label { margin-bottom: 0; }
        .info-btn {
            all: unset !important; box-sizing: border-box !important;
            display: inline-flex !important; align-items: center !important;
            justify-content: center !important; width: 15px !important; height: 15px !important;
            border-radius: 50% !important; border: 1px solid var(--bordure-2) !important;
            background: none !important; color: var(--brun-clair) !important;
            font-size: 10px !important; font-family: Georgia, serif !important;
            font-style: italic !important; font-weight: bold !important;
            cursor: pointer !important; line-height: 1 !important;
            vertical-align: middle !important; transition: all 0.12s !important;
        }
        .info-btn:hover {
            background: var(--fond-tag) !important; border-color: var(--brun) !important;
            color: var(--encre) !important;
        }
        .info-panel {
            display: none; background: var(--fond-doux); border: 1px solid var(--bordure);
            border-radius: var(--rayon); padding: 0.9rem 1.1rem; margin-bottom: 0.75rem;
            font-size: var(--taille-xs); line-height: 1.75; color: var(--encre-doux);
        }
        .info-panel.open { display: block; }
        .info-panel p { margin: 0 0 0.4rem; }
        .info-panel p:last-child { margin: 0; }
        .info-panel strong { color: var(--encre); }
        #bloc-genre { display: block; }
        #bloc-genre.hidden { display: none; }
        .optionnel {
            font-size: var(--taille-xs); color: var(--brun-clair);
            font-weight: normal; font-style: italic; margin-left: 0.3rem;
        }
        .convention-m {
            font-size: var(--taille-xs); color: var(--brun);
            font-style: italic; margin-bottom: 0.5rem; line-height: 1.55;
        }
        .select-hint {
            display: block; font-size: var(--taille-xs); color: var(--brun-clair);
            font-style: italic; margin-top: -0.9rem; margin-bottom: 1.25rem;
        }
        .form-actions { padding-top: 1.5rem; border-top: 1px solid var(--bordure); margin-top: 0.5rem; }
        .form-actions .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; }
        .analyse-loading {
            display: none; font-size: var(--taille-xs); color: var(--brun);
            font-style: italic; margin-top: .5rem;
        }
        .prompt-toggle {
            all: unset; box-sizing: border-box; font-family: Georgia, serif;
            font-size: var(--taille-xs); color: var(--brun-clair); cursor: pointer;
            text-decoration: underline; text-underline-offset: 2px; display: block; margin-top: 1.5rem;
        }
        .prompt-toggle:hover { color: var(--brun); }
        .prompt-bloc {
            display: none; margin-top: .75rem; background: var(--fond-doux);
            border: 1px solid var(--bordure); border-radius: var(--rayon); padding: 1rem 1.25rem;
        }
        .prompt-bloc.open { display: block; }
        .prompt-bloc pre {
            font-family: "Courier New", monospace; font-size: .7rem; color: var(--encre-doux);
            line-height: 1.6; white-space: pre-wrap; word-break: break-word; margin: 0;
        }
        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header-actions { width: 100%; }
            .page-header-actions .btn { flex: 1; text-align: center; }
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
    <?php $navPage = 'new'; include __DIR__ . '/nav.php'; ?>
</header>

<form method="post" action="save.php">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<div class="card">

    <!-- ══ Le mot ══ -->
    <div class="form-section">
        <p class="form-section-titre">Le mot</p>

        <label>Votre mot <span class="requis">*</span></label>
        <input name="mot" required placeholder="Ex. Imparfait">

        <!-- Nature grammaticale -->
        <div class="label-row">
            <label for="champ-type">Nature grammaticale <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-type')"><em>i</em></button>
        </div>
        <div class="info-panel" id="info-type">
            <p><strong>Nom commun :</strong> désigne une chose, un être ou un sentiment. <em>Ex. spleen, saudade</em></p>
            <p><strong>Verbe transitif :</strong> exprime une action qui porte sur quelque chose ou quelqu'un.</p>
            <p><strong>Verbe intransitif :</strong> exprime une action sans objet. <em>Ex. rêvasser, musarder</em></p>
            <p><strong>Verbe pronominal :</strong> se conjugue avec "se". <em>Ex. se morfondre, se languir</em></p>
            <p><strong>Adjectif :</strong> qualifie un nom, décrit une qualité ou un état.</p>
            <p><strong>Adverbe :</strong> modifie un verbe ou un adjectif.</p>
            <p><strong>Locution nominale :</strong> groupe de mots qui fonctionne comme un nom. <em>Ex. "art de vivre"</em></p>
            <p><strong>Locution verbale :</strong> groupe de mots qui fonctionne comme un verbe. <em>Ex. "prendre le large"</em></p>
            <p><strong>Interjection :</strong> exclamation qui exprime un sentiment vif. <em>Ex. Hélas !</em></p>
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
                <option value="masculin">masculin</option>
                <option value="féminin">féminin</option>
                <option value="masculin et féminin">masculin et féminin</option>
            </select>
        </div>

        <!-- Type d'entrée -->
        <div class="label-row">
            <label for="champ-type-entree">Nature du mot <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-type-entree')"><em>i</em></button>
        </div>
        <div class="info-panel" id="info-type-entree">
            <p><strong>Inventé :</strong> le mot est créé de toutes pièces, il n'existe pas en français ni dans une autre langue. <em>Ex. bourniller, grésilonde, chronoclie.</em></p>
            <p><strong>Réactivé :</strong> le mot existe en français, mais vous lui proposez un sens nouveau et distinct du sens courant. <em>Ex. habitabilité au sens de Morizot, où le sens technique de norme de logement est dépassé vers une notion philosophique.</em></p>
            <p><strong>Importé :</strong> le mot est emprunté à une autre langue, francisé ou non, et n'a pas d'équivalent en français. <em>Ex. komorebi (japonais), köttskam (suédois), hugotter (tagalog).</em></p>
            <p><strong>Ressuscité :</strong> le mot a existé en vieux français ou en français classique, est tombé en désuétude, et vous le remettez en circulation avec son sens d'origine ou un sens proche.</p>
        </div>
        <select name="type_entree" id="champ-type-entree" required>
            <option value="invente">Inventé</option>
            <option value="reactive">Réactivé</option>
            <option value="importe">Importé</option>
            <option value="ressuscite">Ressuscité</option>
        </select>

        <!-- Registres d'expérience -->
        <div class="label-row">
            <label>Registres d'expérience <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-registres')"><em>i</em></button>
        </div>
        <div class="info-panel" id="info-registres">
            <p>Les registres d'expérience indiquent <strong>quelle dimension de l'existence</strong> votre mot explore. Plusieurs choix sont possibles.</p>
            <p><strong>Rapport à soi :</strong> ce qu'on ressent intérieurement, ses états d'âme profonds.</p>
            <p><strong>Rapport au corps :</strong> les sensations physiques, la présence au monde par le corps.</p>
            <p><strong>Rapport aux émotions :</strong> les états affectifs, ce qu'on éprouve sans toujours savoir le nommer.</p>
            <p><strong>Rapport au désir :</strong> l'élan vers quelque chose ou quelqu'un, le manque actif.</p>
            <p><strong>Rapport à la mémoire :</strong> le souvenir, la réminiscence, ce qui revient du passé.</p>
            <p><strong>Rapport aux autres :</strong> la relation, la présence de l'autre, ce que les autres nous font.</p>
            <p><strong>Rapport à la parole :</strong> ce qu'on dit, ce qu'on tait, la difficulté ou la facilité à s'exprimer.</p>
            <p><strong>Rapport au lieu :</strong> l'attachement à un espace, le sentiment d'appartenir quelque part.</p>
            <p><strong>Rapport au temps :</strong> la durée, l'attente, la fugacité, le passage des choses.</p>
            <p><strong>Rapport au quotidien :</strong> les gestes ordinaires, ce qui revient chaque jour.</p>
            <p><strong>Rapport à l'absence :</strong> ce qui manque, ce qui est parti, le vide laissé.</p>
            <p><strong>Rapport au manque :</strong> l'incomplétude, la sensation qu'il manque quelque chose.</p>
            <p><strong>Rapport à l'invisible :</strong> ce qu'on devine sans le voir, le pressentiment.</p>
            <p><strong>Rapport à l'attention :</strong> la capacité à être présent, à remarquer finement le monde.</p>
            <p><strong>Rapport au vivant :</strong> les relations avec les animaux, les plantes et le non-humain.</p>
            <p><strong>Rapport à la beauté :</strong> la rencontre avec ce qui nous traverse, nous arrête, nous élève.</p>
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
            <p>L'étymologie donne au mot une origine, réelle, probable ou inventée avec soin. Elle doit être cohérente avec le mot et construite selon ce format :</p>
            <p><strong>Commence par :</strong> "Du", "De l'", "De", "Emprunté à", "Altération de", "Formé sur"</p>
            <p><strong>Le mot source :</strong> suivi de sa traduction entre guillemets « »</p>
            <p><strong>Pas de :</strong> parenthèses, +, →. Utilisez "croisé avec" ou "combiné à" pour les mots composés.</p>
            <p><strong>Se termine par un point.</strong></p>
            <p><em>Exemple : Du tagalog hugot « tirer, extraire », désignant l'acte de puiser une émotion enfouie.</em></p>
            <p><em>Exemple : Formé sur le latin somnium « rêve », croisé avec le français somme.</em></p>
        </div>
        <textarea name="etymologie" required
            placeholder="Ex. Du latin imperfectus « inachevé, incomplet », désignant ce qui reste en devenir, jamais tout à fait accompli."></textarea>
    </div>

    <!-- ══ Définition ══ -->
    <div class="form-section">
        <p class="form-section-titre">Définition</p>

        <label>Sens principal <span class="requis">*</span></label>
        <textarea name="definition_1" required placeholder="Le sens essentiel du mot…"></textarea>

        <div class="label-row">
            <label for="champ-registre1">Registre stylistique <span class="requis">*</span></label>
            <button type="button" class="info-btn" onclick="toggleInfo('info-registre-stylo')"><em>i</em></button>
        </div>
        <div class="info-panel" id="info-registre-stylo">
            <p>Le registre stylistique décrit <strong>le ton et le niveau de langue</strong> de votre définition.</p>
            <p><strong>Courant :</strong> langue de tous les jours, accessible à tous.</p>
            <p><strong>Littéraire :</strong> langue soignée et élaborée, proche de l'écrit.</p>
            <p><strong>Familier :</strong> langue parlée, décontractée, proche de l'oral.</p>
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

        <label>Par extension <span class="optionnel">optionnel, un sens second plus large ou métaphorique</span></label>
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

    <!-- ══ Notification ══ -->
    <div class="form-section">
        <p class="form-section-titre">Être notifié <span style="font-weight:normal;font-style:italic;text-transform:none;letter-spacing:0">optionnel</span></p>
        <label>Votre email <span class="optionnel">optionnel</span></label>
        <input type="email" name="email_contributeur" placeholder="votre@email.fr" autocomplete="email">
        <div style="margin-top:.5rem;display:flex;align-items:flex-start;gap:.6rem">
            <input type="checkbox" name="consent_email" id="consent_email" value="1" style="margin-top:.2rem;flex-shrink:0">
            <label for="consent_email" style="font-size:var(--taille-xs);color:var(--encre-doux);line-height:1.6;cursor:pointer;margin:0">
                J'accepte que mon adresse email soit utilisée uniquement pour être informé du statut de ma proposition.
                Elle ne sera pas conservée au-delà de cette notification et ne sera jamais partagée.
            </label>
        </div>
    </div>

    <!-- ══ Actions ══ -->
    <div class="form-actions">
        <button type="submit" class="btn" id="btn-analyser">Lancer l'analyse</button>
        <p class="analyse-loading" id="msg-loading">Analyse en cours, cela peut prendre quelques secondes…</p>
    </div>

</div>
</form>

<!-- Prompt dépliable -->
<?php
ob_start();
$promptTexteLocal = require __DIR__ . '/prompt.php';
ob_end_clean();
$promptAffiche = preg_replace('/\{\$[^}]+\}/', '…', is_string($promptTexteLocal) ? $promptTexteLocal : '');
?>
<p style="font-size:var(--taille-xs);color:var(--brun-clair);font-style:italic;margin-top:1.5rem;margin-bottom:.3rem">
    L'analyse est réalisée par <strong style="font-style:normal;color:var(--encre-doux)"><?= h($config['openai']['model']) ?></strong> d'OpenAI.
</p>
<button type="button" class="prompt-toggle" id="btn-prompt-new" onclick="togglePrompt(this, 'prompt-new')">Voir les critères d'analyse →</button>
<div class="prompt-bloc" id="prompt-new">
    <pre><?= h($promptAffiche) ?></pre>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
function togglePrompt(btn, id) {
    var v = '<?= defined('PROMPT_VERSION') ? h(PROMPT_VERSION) : '' ?>';
    document.getElementById(id).classList.toggle('open');
    var ouvert = document.getElementById(id).classList.contains('open');
    btn.textContent = (ouvert ? 'Masquer les critères d\'analyse ←' : 'Voir les critères d\'analyse →') + (v ? ' v' + v : '');
}

function lancerAnalyse() {
    document.getElementById('msg-loading').style.display = 'block';
    document.getElementById('btn-analyser').style.opacity = '0.5';
    document.getElementById('btn-analyser').style.pointerEvents = 'none';
}

window.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('btn-prompt-new');
    if (btn) {
        var v = '<?= defined('PROMPT_VERSION') ? h(PROMPT_VERSION) : '' ?>';
        btn.textContent = 'Voir les critères d\'analyse →' + (v ? ' v' + v : '');
    }
    gererGenre(document.getElementById('champ-type').value);

    document.querySelector('form').addEventListener('submit', function(e) {
        var email   = document.querySelector('input[name="email_contributeur"]');
        var consent = document.getElementById('consent_email');

        if (email && consent && email.value.trim() !== '' && !consent.checked) {
            e.preventDefault();
            consent.setCustomValidity('Veuillez cocher cette case pour autoriser l\'utilisation de votre email.');
            consent.reportValidity();
            consent.setCustomValidity('');
            return;
        }

        lancerAnalyse();
    });
});

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
</script>
</body>
</html>

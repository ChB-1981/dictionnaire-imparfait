<?php
/**
 * analyse.php — Analyse qualité d'un mot via l'IA.
 */

require_once __DIR__ . '/app/bootstrap.php';

define('SEUIL_BAS',  8);
define('SEUIL_HAUT', 14);

$id = (int) ($_GET['id'] ?? 0);
if (!$id) die('ID manquant.');

$pdo  = db($config);
$stmt = $pdo->prepare('SELECT * FROM dictionnaire_mots WHERE id = :id');
$stmt->execute([':id' => $id]);
$mot  = $stmt->fetch();
if (!$mot) die('Mot introuvable.');

// Majuscule automatique sur la première lettre
if (!empty($mot['mot_original'])) {
    $premiereCar = mb_strtoupper(mb_substr($mot['mot_original'], 0, 1, 'UTF-8'), 'UTF-8');
    $suite       = mb_substr($mot['mot_original'], 1, null, 'UTF-8');
    $motCorrige  = $premiereCar . $suite;
    if ($motCorrige !== $mot['mot_original']) {
        $pdo->prepare('UPDATE dictionnaire_mots SET mot_original = :m WHERE id = :id')
            ->execute([':m' => $motCorrige, ':id' => $id]);
        $mot['mot_original'] = $motCorrige;
    }
}

$registerNames = get_word_register_names($pdo, $id);
$registerText  = implode(', ', $registerNames);

// ── Appel OpenAI si analyse absente ──
if (empty($mot['suggestions_ia'])) {
    $apiKey = $config['openai']['api_key'] ?? '';
    if (!$apiKey) die('Clé OpenAI manquante.');

    $def2 = !empty($mot['definition_2_originale'])
        ? "\nPar extension : {$mot['definition_2_originale']}" : '';

    $prompt = require __DIR__ . '/prompt.php';

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model'           => $config['openai']['model'],
            'messages'        => [
                ['role' => 'system', 'content' => 'Tu es un lexicographe exigeant et bienveillant. Tu réponds uniquement en JSON valide, sans commentaire hors du JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature'     => 0.3,
            'response_format' => ['type' => 'json_object'],
        ]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result      = json_decode($response, true);
    $analyseJson = $result['choices'][0]['message']['content'] ?? null;
    if (!$analyseJson) die('Erreur IA — réponse vide ou invalide.');

    $analyseTemp    = json_decode($analyseJson, true) ?? [];
    $scoreTotalTemp = 0;
    foreach (($analyseTemp['coherence'] ?? []) as $item) $scoreTotalTemp += (int)($item['note'] ?? 0);
    foreach (($analyseTemp['utilite']   ?? []) as $item) $scoreTotalTemp += (int)($item['note'] ?? 0);

    $pdo->prepare('UPDATE dictionnaire_mots SET suggestions_ia = :a, score_total = :s WHERE id = :id')
        ->execute([':a' => $analyseJson, ':s' => $scoreTotalTemp, ':id' => $id]);

    $mot['suggestions_ia'] = $analyseJson;
    $mot['score_total']    = $scoreTotalTemp;
}

$analyse = json_decode($mot['suggestions_ia'], true);
if (!$analyse) die('Analyse illisible. Supprimez suggestions_ia en base et rechargez.');

// ── Calcul conformité ──
$nbConformite = count($analyse['conformite'] ?? []);
$okConformite = 0;
$bloquant     = false;
foreach (($analyse['conformite'] ?? []) as $item) {
    if (($item['ok'] ?? true) === true) $okConformite++;
    else $bloquant = true;
}

// ── Calcul scores ──
$scoreCoherence = 0;
foreach (($analyse['coherence'] ?? []) as $item) $scoreCoherence += (int)($item['note'] ?? 0);
$scoreUtilite = 0;
foreach (($analyse['utilite'] ?? []) as $item) $scoreUtilite += (int)($item['note'] ?? 0);
$scoreTotal = $scoreCoherence + $scoreUtilite;

// ── Vérification format étymologie ──
$etymValide = false;
$etymMotif = "/^(Du|De l'|De |Emprunté à|Altération de|Formé sur|Dérivé de|Issu de)/u";
if (!empty($mot['etymologie_originale'])) {
    $etym = trim($mot['etymologie_originale']);
    $etymValide = preg_match($etymMotif, $etym) && substr($etym, -1) === '.';
}


// Seuil "n'importe quoi" — proposition manifestement hors sujet
$nbZeros = 0;
foreach (($analyse['coherence'] ?? []) as $item) { if ((int)($item['note'] ?? 1) === 0) $nbZeros++; }
foreach (($analyse['utilite']   ?? []) as $item) { if ((int)($item['note'] ?? 1) === 0) $nbZeros++; }
$nbBloques = 0;
foreach (($analyse['conformite'] ?? []) as $item) { if (($item['ok'] ?? true) === false) $nbBloques++; }
$propositionHorsJeu = ($nbZeros >= 3 || $nbBloques >= 3 || $scoreTotal <= 3);

// Bloquer validation si un critère cohérence ou utilité est à 0
$zeroCritere = false;
foreach (($analyse['coherence'] ?? []) as $item) {
    if ((int)($item['note'] ?? 1) === 0) { $zeroCritere = true; break; }
}
if (!$zeroCritere) {
    foreach (($analyse['utilite'] ?? []) as $item) {
        if ((int)($item['note'] ?? 1) === 0) { $zeroCritere = true; break; }
    }
}

// ── Couleur selon score ──
function scoreColor(int $score, int $max): string {
    $pct = $max > 0 ? $score / $max : 0;
    if ($pct >= 0.8) return '#1d6b35';  // vert
    if ($pct >= 0.5) return '#8a5a00';  // orange
    return '#9b1c1c';                    // rouge
}

// ── Suggestions ──
$suggs  = $analyse['suggestions'] ?? [];
$hasSugg = false;
foreach ($suggs as $s) {
    if (!empty($s['reformulation']) || !empty($s['suggestion'])) { $hasSugg = true; break; }
}

// ── Labels ──
function conformite_label(string $key): string {
    return [
        'existence_francais'  => "Le mot n'existe pas déjà en français avec ce sens",
        'etymologie_credible' => "L'étymologie est crédible et bien construite",
        'forme_mot'           => "Le mot est prononçable et compatible avec le français",
        'rapport_experience'  => "La définition exprime une expérience vécue ou une manière d'être au monde",
        'coherence_registres' => "Les registres d'expérience choisis correspondent à ce que le mot exprime",
        'ecriture_inclusive'  => "Le mot et la définition n'utilisent pas l'écriture inclusive",
        'prenom_m'            => "L'exemple contient un prénom commençant par M",
    ][$key] ?? $key;
}

function coherence_label(string $key): string {
    return [
        'mot_nature'          => "Le mot correspond à sa nature grammaticale",
        'mot_etymologie'      => "L'étymologie est cohérente avec le mot",
        'definition_registre' => "La définition correspond à son registre stylistique",
        'extension_coherente' => "La définition par extension découle logiquement de la principale",
        'exemple_definition'  => "L'exemple illustre correctement la définition",
    ][$key] ?? $key;
}

function utilite_label(string $key): string {
    return [
        'originalite_semantique'  => "Le mot apporte une nuance absente du français",
        'utilite_usage'           => "Le mot pourrait être réellement employé",
        'puissance_expressive'    => "Le mot a une force évocatrice, une belle sonorité",
        'qualite_lexicographique' => "Le style d'écriture est soutenu, précis, publiable",
    ][$key] ?? $key;
}

function suggestion_titre(string $key): string {
    return [
        'etymologie'            => "Étymologie",
        'definition_principale' => "Définition principale",
        'definition_extension'  => "Définition par extension",
        'exemple'               => "Exemple d'usage",
        'registres_experience'  => "Registres d'expérience",
        'registre_stylistique'  => "Registre stylistique",
    ][$key] ?? $key;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Analyse : <?= h($mot['mot_original']) ?> — Dictionnaire imparfait</title>
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

        /* Mot */
        .entree-mot-nom { font-size: 1.15rem; font-weight: bold; margin-right: .45rem; }
        .entree-type-txt { font-style: italic; color: var(--brun); font-size: var(--taille-sm); }
        .entree-etym {
            font-size: .82rem; color: var(--encre-doux); font-style: italic;
            margin: .15rem 0 .5rem; line-height: 1.55;
        }
        .entree-etym::before { content: "["; color: var(--brun-clair); margin-right: .1rem; }
        .entree-etym::after  { content: "]"; color: var(--brun-clair); margin-left:  .1rem; }
        .entree-def { margin: 0 0 .15rem; line-height: 1.7; font-size: var(--taille-sm); }
        .entree-abbr { font-variant: small-caps; font-size: .77rem; color: var(--brun-clair); margin-right: .5rem; }
        .entree-ext { margin: .1rem 0 .15rem; line-height: 1.7; font-size: var(--taille-sm); color: var(--encre-doux); }
        .entree-ext::before { content: "Par ext. "; font-style: italic; color: var(--brun-clair); font-size: var(--taille-xs); }
        .entree-ex { font-style: italic; color: var(--encre-doux); font-size: .9rem; margin: .45rem 0 .55rem; line-height: 1.6; }
        .entree-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }

        /* Bloc notes */
        .note-globale { display: flex; align-items: baseline; gap: 1.25rem; margin-bottom: .75rem; flex-wrap: wrap; }
        .note-chiffre { font-size: 2.8rem; font-weight: bold; line-height: 1; }
        .note-sur { font-size: 1.1rem; font-weight: normal; }
        .note-verdict { font-size: var(--taille-sm); font-style: italic; }
        .note-commentaire {
            font-size: var(--taille-sm);
            color: var(--encre-doux);
            line-height: 1.7;
            margin: .75rem 0 0;
            padding: .9rem 1rem;
            background: var(--fond-doux);
            border-radius: var(--rayon);
        }

        /* Sous-sections */
        .sous-note { border-top: 1px solid var(--bordure); padding: .9rem 0 0; margin-top: .9rem; }
        .sous-note-header {
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; user-select: none; padding-bottom: .9rem; gap: 1rem;
        }
        .sous-note-gauche { display: flex; align-items: center; gap: .75rem; }
        .sous-note-titre { font-size: var(--taille-sm); font-weight: 600; color: var(--encre-doux); transition: color .12s; }
        .sous-note-header:hover .sous-note-titre { color: var(--encre); }
        .sous-note-score {
            font-size: var(--taille-xs); padding: .15rem .55rem;
            border-radius: 999px; white-space: nowrap; font-weight: 600;
        }
        .sous-note-chevron { font-size: .75rem; color: var(--brun-clair); transition: transform .2s; flex-shrink: 0; }
        .sous-note.open .sous-note-chevron { transform: rotate(180deg); }
        .sous-note-corps { display: none; padding-bottom: .5rem; }
        .sous-note.open .sous-note-corps { display: block; }

        /* Suggestions */
        .suggestion-item { padding: 1.1rem 0; border-top: 1px solid var(--bordure); }
        .suggestion-item:first-of-type { border-top: none; padding-top: 0; }
        .suggestion-check-row { display: flex; align-items: flex-start; gap: .75rem; }
        .suggestion-check { margin-top: .2rem; width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--encre); cursor: pointer; }
        .suggestion-contenu { flex: 1; }
        .suggestion-section-titre {
            font-size: .68rem; text-transform: uppercase; letter-spacing: .08em;
            color: var(--brun-clair); display: block; margin-bottom: .5rem;
        }
        .suggestion-comparaison { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .suggestion-col-label {
            font-size: .65rem; text-transform: uppercase; letter-spacing: .07em;
            color: var(--brun-clair); display: block; margin-bottom: .2rem;
        }
        .suggestion-original {
            font-size: var(--taille-xs); color: var(--brun-clair); font-style: italic;
            line-height: 1.55; text-decoration: line-through; opacity: .7;
        }
        .suggestion-reformulation {
            font-size: var(--taille-sm); color: var(--encre-doux);
            font-style: italic; line-height: 1.65;
        }
        .suggestion-simple { font-size: var(--taille-sm); color: var(--encre-doux); line-height: 1.6; }
        .suggestion-explication { font-size: var(--taille-xs); color: var(--brun-clair); font-style: italic; margin-top: .2rem; }
        .suggestions-intro {
            font-size: var(--taille-xs); color: var(--brun); font-style: italic;
            line-height: 1.6; margin: 0 0 1.25rem;
        }

        /* Bouton appliquer */
        .btn-appliquer-wrap { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--bordure); }

        /* Actions */
        .actions-bloc { display: flex; gap: .75rem; flex-wrap: wrap; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .actions-bloc { flex-direction: column; }
            .actions-bloc .btn { text-align: center; }
            .suggestion-comparaison { grid-template-columns: 1fr; }
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

<!-- ══ BLOC 1 : Votre proposition ══ -->
<div class="card">
    <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--brun-clair);margin:0 0 1rem">Votre proposition</p>

    <p style="margin:0 0 .3rem">
        <span class="entree-mot-nom"><?= h($mot['mot_original']) ?></span>
        <span class="entree-type-txt"><?= h($mot['type_original']) ?></span>
    </p>
    <?php if (!empty($mot['etymologie_originale'])): ?>
        <p class="entree-etym"><?= h($mot['etymologie_originale']) ?></p>
    <?php endif; ?>
    <p class="entree-def">
        <span class="entree-abbr"><?= h(style_abbr($mot['registre_definition_1'])) ?></span>
        <?= h($mot['definition_1_originale']) ?>
    </p>
    <?php if (!empty($mot['definition_2_originale'])): ?>
        <p class="entree-ext"><?= h($mot['definition_2_originale']) ?></p>
    <?php endif; ?>
    <p class="entree-ex">« <?= h($mot['exemple_original']) ?> »</p>
    <?php if (!empty($registerNames)): ?>
        <div class="entree-tags">
            <?php foreach ($registerNames as $tag): ?>
                <span class="tag"><?= h($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Avertissement format étymologie -->
<?php
$etymIaOk = ($analyse['conformite']['etymologie_credible']['ok'] ?? true) === true;
?>
<?php if (!$etymValide && !$etymIaOk && !empty($mot['etymologie_originale'])): ?>
<div style="margin-bottom:1.25rem;padding:.85rem 1rem;background:#fff4dd;border:1px solid #e8c97a;border-radius:var(--rayon);font-size:var(--taille-xs);color:#8a5a00;line-height:1.6">
    <strong>Format d'étymologie à corriger.</strong>
    L'étymologie doit commencer par "Du", "De l'", "Emprunté à", "Formé sur"… et se terminer par un point.
    Exemple : <em>Du latin somnium (« rêve »), désignant l'état de celui qui s'abandonne au sommeil.</em>
</div>
<?php endif; ?>

<!-- ══ BLOC 2 : Notes ══ -->
<div class="card">
    <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--brun-clair);margin:0 0 1.25rem">Résultat de l'analyse</p>

    <div class="note-globale">
        <span class="note-chiffre" style="color:<?= scoreColor($scoreTotal, 20) ?>">
            <?= h($scoreTotal) ?><span class="note-sur" style="color:var(--brun-clair)">/20</span>
        </span>
        <span class="note-verdict">
            <?php if ($propositionHorsJeu): ?>
                <span class="alert">Cette proposition ne correspond pas aux critères du dictionnaire.</span>
            <?php elseif ($bloquant): ?>
                <span class="alert">Un critère de conformité n'est pas respecté.</span>
            <?php elseif ($zeroCritere): ?>
                <span class="alert">Un critère de cohérence ou d'utilité est à zéro.</span>
            <?php elseif ($scoreTotal < SEUIL_BAS): ?>
                <span class="alert">Le mot doit être repris avant de continuer.</span>
            <?php elseif ($scoreTotal < SEUIL_HAUT): ?>
                <span class="warning">Le mot est prometteur et mérite d'être affiné.</span>
            <?php else: ?>
                <span class="good">Le mot est solide et peut être validé.</span>
            <?php endif; ?>
        </span>
    </div>

    <?php if (!empty($analyse['verdict_global'])): ?>
        <p class="note-commentaire"><?= h($analyse['verdict_global']) ?></p>
    <?php endif; ?>

    <!-- Conformité -->
    <?php
    $cfColor  = $bloquant ? '#9b1c1c' : '#1d6b35';
    $cfBg     = $bloquant ? '#fdecec' : '#eef7ee';
    $cfTexte  = $bloquant
        ? "Un critère de conformité n'est pas respecté"
        : "Les critères de conformité sont respectés";
    ?>
    <div class="sous-note <?= $bloquant ? 'open' : '' ?>" id="sn-conformite">
        <div class="sous-note-header" onclick="toggleSousNote('sn-conformite')">
            <div class="sous-note-gauche">
                <span class="sous-note-titre">Conformité au projet</span>
                <span class="sous-note-score" style="color:<?= $cfColor ?>;background:<?= $cfBg ?>"><?= $cfTexte ?></span>
            </div>
            <span class="sous-note-chevron">▼</span>
        </div>
        <div class="sous-note-corps">
            <?php foreach (($analyse['conformite'] ?? []) as $key => $item): ?>
                <div class="qa-block">
                    <div class="question" style="font-weight:normal;font-size:var(--taille-sm)"><?= h(conformite_label($key)) ?></div>
                    <div class="answer">
                        <span class="<?= ($item['ok'] ?? false) ? 'status-ok' : 'status-bad' ?>">
                            <?= ($item['ok'] ?? false) ? '✓' : '✗' ?>
                        </span>
                        <span class="comment"><?= h($item['commentaire'] ?? '') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($analyse['commentaire_conformite'])): ?>
                <p class="block-comment"><?= h($analyse['commentaire_conformite']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cohérence -->
    <?php $cohColor = scoreColor($scoreCoherence, 10); ?>
    <div class="sous-note" id="sn-coherence">
        <div class="sous-note-header" onclick="toggleSousNote('sn-coherence')">
            <div class="sous-note-gauche">
                <span class="sous-note-titre">Cohérence d'ensemble</span>
                <span class="sous-note-score" style="color:<?= $cohColor ?>;background:var(--fond-tag)"><?= h($scoreCoherence) ?>/10</span>
            </div>
            <span class="sous-note-chevron">▼</span>
        </div>
        <div class="sous-note-corps">
            <?php foreach (($analyse['coherence'] ?? []) as $key => $item): ?>
                <div class="qa-block">
                    <div class="question" style="font-weight:normal;font-size:var(--taille-sm)"><?= h(coherence_label($key)) ?></div>
                    <div class="answer">
                        <span class="note"><?= h($item['note'] ?? 0) ?>/<?= h($item['max'] ?? 2) ?></span>
                        <span class="comment"><?= h($item['commentaire'] ?? '') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($analyse['commentaire_coherence'])): ?>
                <p class="block-comment"><?= h($analyse['commentaire_coherence']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Utilité -->
    <?php $utilColor = scoreColor($scoreUtilite, 10); ?>
    <div class="sous-note" id="sn-utilite">
        <div class="sous-note-header" onclick="toggleSousNote('sn-utilite')">
            <div class="sous-note-gauche">
                <span class="sous-note-titre">Utilité lexicographique</span>
                <span class="sous-note-score" style="color:<?= $utilColor ?>;background:var(--fond-tag)"><?= h($scoreUtilite) ?>/10</span>
            </div>
            <span class="sous-note-chevron">▼</span>
        </div>
        <div class="sous-note-corps">
            <?php foreach (($analyse['utilite'] ?? []) as $key => $item): ?>
                <div class="qa-block">
                    <div class="question" style="font-weight:normal;font-size:var(--taille-sm)"><?= h(utilite_label($key)) ?></div>
                    <div class="answer">
                        <span class="note"><?= h($item['note'] ?? 0) ?>/<?= h($item['max'] ?? 0) ?></span>
                        <span class="comment"><?= h($item['commentaire'] ?? '') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($analyse['commentaire_utilite'])): ?>
                <p class="block-comment"><?= h($analyse['commentaire_utilite']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ BLOC 3 : Suggestions ══ -->
<?php if ($hasSugg && !$propositionHorsJeu && !(!$bloquant && !$zeroCritere && $scoreTotal === 20)): ?>
<div class="card" style="background:var(--fond-doux)">
    <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--brun-clair);margin:0 0 .5rem">Suggestions de reformulation</p>
    <p class="suggestions-intro">
        Sélectionnez les suggestions que vous souhaitez intégrer.
        L'original est barré à gauche, la reformulation proposée à droite.
        Les champs sélectionnés seront pré-remplis dans le formulaire de modification.
    </p>

    <form method="post" action="edit_original.php" id="form-suggestions">
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <input type="hidden" name="from_suggestions" value="1">
        <input type="hidden" name="etymologie_base"   value="<?= h($mot['etymologie_originale']) ?>">
        <input type="hidden" name="definition_1_base" value="<?= h($mot['definition_1_originale']) ?>">
        <input type="hidden" name="definition_2_base" value="<?= h($mot['definition_2_originale']) ?>">
        <input type="hidden" name="exemple_base"      value="<?= h($mot['exemple_original']) ?>">

        <?php
        $suggMap = [
            'etymologie'            => ['champ' => 'appliquer_etymologie',   'hidden' => 'etymologie_sugg'],
            'definition_principale' => ['champ' => 'appliquer_definition',   'hidden' => 'definition_sugg'],
            'definition_extension'  => ['champ' => 'appliquer_extension',    'hidden' => 'extension_sugg'],
            'exemple'               => ['champ' => 'appliquer_exemple',      'hidden' => 'exemple_sugg'],
        ];
        ?>

        <?php foreach ($suggMap as $key => $map):
            $s = $suggs[$key] ?? [];
            $reformulation = trim($s['reformulation'] ?? '');
            $original      = trim($s['original']      ?? '');
            if (empty($reformulation)) continue;
            $idChk = 'chk-' . $key;
        ?>
            <div class="suggestion-item">
                <div class="suggestion-check-row">
                    <input type="checkbox" class="suggestion-check" name="<?= $map['champ'] ?>" id="<?= $idChk ?>" value="1" onchange="updateBtn()">
                    <div class="suggestion-contenu">
                        <label for="<?= $idChk ?>" class="suggestion-section-titre" style="cursor:pointer"><?= h(suggestion_titre($key)) ?></label>
                        <div class="suggestion-comparaison">
                            <div>
                                <span class="suggestion-col-label">Votre version</span>
                                <p class="suggestion-original"><?= $key === 'exemple' ? '« ' . h($original) . ' »' : h($original) ?></p>
                            </div>
                            <div>
                                <span class="suggestion-col-label">Version proposée</span>
                                <p class="suggestion-reformulation"><?= $key === 'exemple' ? '« ' . h($reformulation) . ' »' : h($reformulation) ?></p>
                            </div>
                        </div>
                        <input type="hidden" name="<?= $map['hidden'] ?>" value="<?= h($reformulation) ?>">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>



        <div class="btn-appliquer-wrap">
            <button type="submit" class="btn" id="btn-appliquer">
                Modifier mon mot<?php if($hasSugg): ?> avec les suggestions sélectionnées<?php endif; ?>
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ══ BLOC 4 : Actions ══ -->
<div class="card">
    <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--brun-clair);margin:0 0 .75rem">Que faire maintenant ?</p>

    <?php if ($propositionHorsJeu): ?>
        <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.6;margin:0 0 1rem">
            Cette proposition ne correspond pas aux critères du dictionnaire. Revenez à la saisie et proposez un mot qui exprime une expérience vécue ou une manière d'être au monde.
        </p>
        <div class="actions-bloc">
            <a class="btn" href="new.php">Proposer un nouveau mot</a>
        </div>

    <?php elseif ($bloquant || $scoreTotal < SEUIL_BAS): ?>
        <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.6;margin:0 0 1rem">
            <?= $bloquant
                ? "Un critère de conformité bloque la validation. Consultez le détail ci-dessus et corrigez votre mot."
                : "Le score est insuffisant pour continuer. Consultez le détail ci-dessus et affinez votre proposition." ?>
        </p>
        <?php if (!$hasSugg): ?>
        <div class="actions-bloc">
            <a class="btn" href="edit_original.php?id=<?= h($id) ?>">Modifier mon mot</a>
        </div>
        <?php endif; ?>

    <?php elseif ($scoreTotal < SEUIL_HAUT): ?>
        <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.6;margin:0 0 1rem">
            Le mot a du potentiel. Intégrez les suggestions ci-dessus ou modifiez-le manuellement.
        </p>
        <?php if (!$hasSugg): ?>
        <div class="actions-bloc">
            <a class="btn" href="edit_original.php?id=<?= h($id) ?>">Modifier mon mot</a>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <?php if ($zeroCritere): ?>
            <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.6;margin:0 0 1rem">
                Un critère de cohérence ou d'utilité est à 0. Le mot doit être amélioré avant de pouvoir être validé.
            </p>
            <?php if (!$hasSugg): ?>
            <div class="actions-bloc">
                <a class="btn" href="edit_original.php?id=<?= h($id) ?>">Modifier mon mot</a>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="font-size:var(--taille-sm);color:var(--encre-doux);line-height:1.6;margin:0 0 1rem">
                Le mot est solide. Vous pouvez le valider définitivement.
            </p>
            <div class="actions-bloc">
                <form method="post" action="finalize.php" style="margin:0">
                    <input type="hidden" name="id" value="<?= h($id) ?>">
                    <button class="btn" type="submit">Valider définitivement</button>
                </form>
                <?php if (!$hasSugg): ?>
                    <a class="btn secondary" href="edit_original.php?id=<?= h($id) ?>">Affiner encore</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
function toggleSousNote(id) {
    document.getElementById(id).classList.toggle('open');
}

function updateBtn() { /* bouton toujours actif */ }
</script>
</body>
</html>
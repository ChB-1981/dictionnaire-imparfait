<?php
require_once __DIR__ . '/app/bootstrap.php';
$pdo = db($config);

$registreId = (int) ($_GET['registre'] ?? 0);
$tri        = in_array($_GET['tri'] ?? '', ['alpha', 'likes', 'recent']) ? $_GET['tri'] : 'alpha';
$registres  = get_all_experience_registers($pdo);
$ip         = substr(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]), 0, 45);

$orderBy = match($tri) {
    'likes'  => 'm.coeurs DESC, m.mot_original COLLATE utf8mb4_unicode_ci ASC',
    'recent' => 'm.created_at DESC',
    default  => 'm.mot_original COLLATE utf8mb4_unicode_ci ASC',
};

$select = "SELECT m.id, m.mot_original, m.type_original, m.etymologie_originale,
               m.definition_1_originale, m.registre_definition_1,
               m.definition_2_originale, m.exemple_original, m.coeurs,
               m.created_at,
               (SELECT 1 FROM dictionnaire_votes v WHERE v.mot_id = m.id AND v.ip = :ip) AS deja_vote
        FROM dictionnaire_mots m";

if ($registreId) {
    $stmt = $pdo->prepare("$select
        INNER JOIN dictionnaire_mots_registres_experience mr ON mr.mot_id = m.id
        WHERE m.statut = 'finalise' AND mr.registre_id = :registre
        GROUP BY m.id
        ORDER BY $orderBy
    ");
    $stmt->execute([':ip' => $ip, ':registre' => $registreId]);
} else {
    $stmt = $pdo->prepare("$select
        WHERE m.statut = 'finalise'
        ORDER BY $orderBy
    ");
    $stmt->execute([':ip' => $ip]);
}
$mots = $stmt->fetchAll();

// Grouper par lettre uniquement en mode alpha
$parLettre = [];
if ($tri === 'alpha') {
    foreach ($mots as $mot) {
        $lettre = mb_strtoupper(mb_substr($mot['mot_original'], 0, 1, 'UTF-8'), 'UTF-8');
        $parLettre[$lettre][] = $mot;
    }
    ksort($parLettre);
}

$registreActif = null;
foreach ($registres as $r) {
    if ((int)$r['id'] === $registreId) { $registreActif = $r['nom']; break; }
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
        /* ── En-tête ── */
        .dico-header {
            padding: 2.5rem 0 1.75rem;
            border-bottom: 2px solid var(--encre);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dico-titre-principal {
            font-size: 1.75rem;
            font-weight: normal;
            letter-spacing: 0.04em;
            margin: 0 0 0.2rem;
        }

        .dico-titre-imparfait { font-style: italic; color: var(--brun); }

        .dico-sous-titre {
            font-style: italic;
            color: var(--brun-clair);
            font-size: var(--taille-sm);
            margin: 0;
        }

        .dico-header-actions { display: flex; gap: 0.75rem; align-items: center; }

        /* ── Barre de contrôle : filtre + index ── */
        .dico-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        /* Select filtre */
        .filtre-select {
            font-family: Georgia, serif;
            font-size: var(--taille-sm);
            color: var(--encre);
            background: var(--fond-carte);
            border: 1px solid var(--bordure-2);
            border-radius: var(--rayon);
            padding: 0.5rem 2rem 0.5rem 0.85rem;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%239a8878'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            width: 200px;
            margin: 0;
        }

        .filtre-select:focus { outline: none; border-color: var(--brun); }

        /* Compteur */
        .dico-compteur {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
        }

        /* ── Index alphabétique ── */
        .alpha-index {
            display: flex;
            gap: 0.2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding-bottom: 0;
            border-bottom: none;
        }

        .alpha-index a {
            display: inline-block;
            width: 1.65rem;
            height: 1.65rem;
            line-height: 1.65rem;
            text-align: center;
            font-size: var(--taille-xs);
            color: var(--brun);
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.1s, color 0.1s;
        }

        .alpha-index a:hover {
            background: var(--fond-tag);
            color: var(--encre);
        }

        /* ── Section lettre ── */
        .lettre-section { margin-bottom: 2.5rem; }
        .lettre-section .entree:first-of-type { padding-top: 1.25rem; }

        .lettre-titre {
            font-size: 1.6rem;
            font-weight: normal;
            font-style: italic;
            color: var(--brun);
            border-bottom: none;
            padding-bottom: 0;
            margin: 0.5rem 0 0;
        }


        .lettre-titre {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            font-size: 1.5rem;
            font-style: italic;
            color: var(--brun);
            margin: 0.5rem 0 0;
            font-weight: normal;
            border: none;
        }

        .lettre-comme {
            font-size: 1.5rem;
            color: var(--brun-clair);
            font-style: normal;
            font-weight: normal;
        }

        .lettre-comme em {
            font-style: italic;
            color: var(--brun);
        }

        /* ── Entrée dictionnaire ── */
        .entree {
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--fond-tag);
        }

        .entree:last-of-type { border-bottom: none; }

        /* Mot + type */
        .entree-titre {
            margin: 0 0 0.3rem;
            line-height: 1.3;
        }

        .entree-mot {
            font-size: 1.15rem;
            font-weight: bold;
            margin-right: 0.45rem;
        }

        .entree-type {
            font-style: italic;
            color: var(--brun);
            font-size: var(--taille-sm);
        }

        /* Étymologie */
        .entree-etym {
            font-size: 0.82rem;
            color: var(--encre-doux);
            font-style: italic;
            margin: 0.1rem 0 0.5rem;
            line-height: 1.55;
        }

        .entree-etym::before { content: "["; margin-right: 0.1rem; color: var(--brun-clair); }
        .entree-etym::after  { content: "]"; margin-left: 0.1rem;  color: var(--brun-clair); }

        /* Définition */
        .entree-def {
            margin: 0 0 0.15rem;
            line-height: 1.7;
            font-size: var(--taille-sm);
        }

        .entree-abbr {
            font-variant: small-caps;
            font-size: 0.77rem;
            color: var(--brun-clair);
            margin-right: 0.5rem;
            letter-spacing: 0.02em;
        }

        /* Par extension */
        .entree-extension {
            margin: 0.1rem 0 0.15rem;
            line-height: 1.7;
            font-size: var(--taille-sm);
            color: var(--encre-doux);
        }

        .entree-extension::before {
            content: "Par ext. ";
            font-style: italic;
            color: var(--brun-clair);
            font-size: var(--taille-xs);
        }

        /* Exemple */
        .entree-exemple {
            font-style: italic;
            color: var(--encre-doux);
            font-size: 0.9rem;
            margin: 0.45rem 0 0.55rem;
            line-height: 1.6;
        }

        /* Tags */
        .entree-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin-bottom: 0.75rem;
        }

        /* ── Actions bas : copier + cœur ── */
        .entree-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.6rem;
            padding-top: 0.55rem;
            border-top: 1px solid var(--fond-tag);
        }

        /* ── Boutons d'action bas de mot ── */
        .entree-actions button {
            all: unset;
            box-sizing: border-box;
            cursor: pointer;
            font-family: Georgia, serif;
            font-size: var(--taille-xs);
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            border: 1px solid var(--bordure-2);
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            color: var(--encre-doux);
            background: none;
            transition: background 0.12s, border-color 0.12s, color 0.12s;
            min-width: 130px;
            text-align: center;
        }

        .entree-actions button:hover {
            background: var(--fond-tag);
            border-color: var(--brun);
            color: var(--encre);
        }

        /* Écouter — états */
        .btn-ecouter.loading {
            color: var(--brun-clair);
            border-color: var(--bordure);
            cursor: default;
        }

        .btn-ecouter.playing {
            color: var(--brun);
            border-color: var(--brun);
            background: var(--fond-tag);
        }

        /* Copier — état confirmé */
        .btn-copier.copie-ok {
            color: #1d6b35;
            border-color: #1d6b35;
            background: #eef7ee;
            cursor: default;
        }

        /* Cœur — sans bordure, sans min-width */
        .btn-coeur {
            border: none !important;
            min-width: auto !important;
            padding: 0 0.2rem !important;
            color: var(--bordure-2) !important;
            gap: 0.4rem;
            transition: color 0.12s, transform 0.12s !important;
        }

        .btn-coeur:hover {
            background: none !important;
            border: none !important;
            color: #b85450 !important;
            transform: scale(1.1);
        }

        .btn-coeur.voted { color: #b85450 !important; }
        .coeur-icon { font-size: 1rem; }

        .entree-date-inline {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
        }

        /* ── Vide ── */
        .vide {
            padding: 3rem 0;
            color: var(--brun-clair);
            font-style: italic;
            text-align: center;
            line-height: 2;
        }

        @media (max-width: 600px) {
            .dico-header { flex-direction: column; align-items: flex-start; }
            .dico-header-actions { width: 100%; }
            .dico-header-actions .btn { flex: 1; text-align: center; }
            .dico-controls { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

<!-- En-tête -->
<header class="dico-header">
    <div>
        <h1 class="dico-titre-principal">
            Dictionnaire <span class="dico-titre-imparfait">imparfait</span>
        </h1>
        <p class="dico-sous-titre">Pour habiter le monde autrement.</p>
    </div>
    <?php $navPage = 'dictionnaire'; include __DIR__ . '/nav.php'; ?>
</header>

<!-- Filtres + tri + compteur -->
<div class="dico-controls">
    <form method="get" id="form-filtre" style="margin:0;display:flex;gap:0.5rem;flex-wrap:wrap">
        <select name="registre" class="filtre-select" onchange="this.form.submit()">
            <option value="0" <?= !$registreId ? 'selected' : '' ?>>Tous les registres</option>
            <?php foreach ($registres as $r): ?>
                <option value="<?= h($r['id']) ?>" <?= (int)$r['id'] === $registreId ? 'selected' : '' ?>>
                    <?= h($r['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="tri" class="filtre-select" onchange="this.form.submit()">
            <option value="alpha"  <?= $tri === 'alpha'  ? 'selected' : '' ?>>Ordre alphabétique</option>
            <option value="likes"  <?= $tri === 'likes'  ? 'selected' : '' ?>>Les plus aimés</option>
            <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Les plus récents</option>
        </select>
    </form>

    <span class="dico-compteur">
        <?= count($mots) ?> mot<?= count($mots) > 1 ? 's' : '' ?>
        <?= $registreActif ? 'dans ce registre' : 'au dictionnaire' ?>
    </span>
</div>

<?php if (empty($mots)): ?>
    <div class="vide">
        <?= $registreActif ? "Aucun mot finalisé pour ce registre." : "Le dictionnaire est encore vide." ?>
    </div>
<?php else: ?>

    <?php if ($tri === 'alpha'): ?>
    <!-- Index alphabétique -->
    <nav class="alpha-index">
        <?php foreach (array_keys($parLettre) as $l): ?>
            <a href="#lettre-<?= h($l) ?>"><?= h($l) ?></a>
        <?php endforeach; ?>
    </nav>

    <!-- Entrées groupées par lettre -->
    <?php foreach ($parLettre as $lettre => $entrees): ?>
        <section class="lettre-section" id="lettre-<?= h($lettre) ?>">
            <?php $motLettre = $entrees[array_rand($entrees)]; ?>
            <h2 class="lettre-titre">
                <?= h($lettre) ?>
                <span class="lettre-comme">comme <em><?= h($motLettre['mot_original']) ?></em></span>
            </h2>

            <?php foreach ($entrees as $mot):
                $registresMot = get_word_register_names($pdo, $mot['id']);
                $voted        = (bool) $mot['deja_vote'];
                $urlMot       = 'https://' . $_SERVER['HTTP_HOST'] . '/view.php?id=' . $mot['id'];
                $textePartage = $mot['mot_original']
                    . ' — ' . $mot['definition_1_originale']
                    . ' « ' . $mot['exemple_original'] . ' »'
                    . '  ' . $urlMot;
            ?>
            <article class="entree">

                <p class="entree-titre">
                    <span class="entree-mot"><?= h($mot['mot_original']) ?></span>
                    <span class="entree-type"><?= h($mot['type_original']) ?></span>
                </p>

                <?php if (!empty($mot['etymologie_originale'])): ?>
                    <p class="entree-etym"><?= h($mot['etymologie_originale']) ?></p>
                <?php endif; ?>

                <p class="entree-def">
                    <span class="entree-abbr"><?= h(style_abbr($mot['registre_definition_1'])) ?></span>
                    <?= h($mot['definition_1_originale']) ?>
                </p>

                <?php if (!empty($mot['definition_2_originale'])): ?>
                    <p class="entree-extension"><?= h($mot['definition_2_originale']) ?></p>
                <?php endif; ?>

                <p class="entree-exemple">« <?= h($mot['exemple_original']) ?> »</p>

                <?php if (!empty($registresMot)): ?>
                    <div class="entree-tags">
                        <?php foreach ($registresMot as $reg):
                            $rid = 0;
                            foreach ($registres as $r) {
                                if ($r['nom'] === $reg) { $rid = $r['id']; break; }
                            }
                        ?>
                            <a href="?registre=<?= h($rid) ?>" class="tag"><?= h($reg) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="entree-actions"
                     data-texte="<?= h($textePartage) ?>"
                     data-id="<?= h($mot['id']) ?>">
                    <button class="btn-ecouter" type="button" data-id="<?= h($mot['id']) ?>">▶ Écouter le mot</button>
                    <button class="btn-copier" type="button">Copier ce mot</button>
                    <button class="btn-coeur <?= $voted ? 'voted' : '' ?>"
                            type="button"
                            title="<?= $voted ? 'Retirer mon cœur' : 'Donner un cœur' ?>">
                        <span class="coeur-icon"><?= $voted ? '♥' : '♡' ?></span>
                        <?php if ($mot['coeurs'] > 0): ?>
                            <span class="coeur-count" style="margin-left:0.3rem"><?= (int)$mot['coeurs'] ?></span>
                        <?php endif; ?>
                    </button>
                </div>

            </article>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <?php else: ?>
    <!-- Entrées en liste plate (likes ou récents) -->
    <?php foreach ($mots as $mot):
        $registresMot = get_word_register_names($pdo, $mot['id']);
        $voted        = (bool) $mot['deja_vote'];
        $urlMot       = 'https://' . $_SERVER['HTTP_HOST'] . '/view.php?id=' . $mot['id'];
        $textePartage = $mot['mot_original']
            . ' — ' . $mot['definition_1_originale']
            . ' « ' . $mot['exemple_original'] . ' »'
            . '  ' . $urlMot;
    ?>
    <article class="entree">
        <p class="entree-titre">
            <span class="entree-mot"><?= h($mot['mot_original']) ?></span>
            <span class="entree-type"><?= h($mot['type_original']) ?></span>
            <?php if ($tri === 'recent'): ?>
                <?php
                $moisFr = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
                $d = new DateTime($mot['created_at']);
                $dateStr = $d->format('j') . ' ' . $moisFr[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
                ?>
                <span class="entree-date-inline"> · <?= h($dateStr) ?></span>
            <?php endif; ?>
        </p>
        <?php if (!empty($mot['etymologie_originale'])): ?>
            <p class="entree-etym"><?= h($mot['etymologie_originale']) ?></p>
        <?php endif; ?>
        <p class="entree-def">
            <span class="entree-abbr"><?= h(style_abbr($mot['registre_definition_1'])) ?></span>
            <?= h($mot['definition_1_originale']) ?>
        </p>
        <?php if (!empty($mot['definition_2_originale'])): ?>
            <p class="entree-extension"><?= h($mot['definition_2_originale']) ?></p>
        <?php endif; ?>
        <p class="entree-exemple">« <?= h($mot['exemple_original']) ?> »</p>
        <?php if (!empty($registresMot)): ?>
            <div class="entree-tags">
                <?php foreach ($registresMot as $reg):
                    $rid = 0;
                    foreach ($registres as $r) { if ($r['nom'] === $reg) { $rid = $r['id']; break; } }
                ?>
                    <a href="?registre=<?= h($rid) ?>" class="tag"><?= h($reg) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="entree-actions"
             data-texte="<?= h($textePartage) ?>"
             data-id="<?= h($mot['id']) ?>">
            <button class="btn-ecouter" type="button" data-id="<?= h($mot['id']) ?>">▶ Écouter le mot</button>
            <button class="btn-copier" type="button">Copier ce mot</button>
            <button class="btn-coeur <?= $voted ? 'voted' : '' ?>"
                    type="button"
                    title="<?= $voted ? 'Retirer mon cœur' : 'Donner un cœur' ?>">
                <span class="coeur-icon"><?= $voted ? '♥' : '♡' ?></span>
                <?php if ($mot['coeurs'] > 0): ?>
                    <span class="coeur-count" style="margin-left:0.3rem"><?= (int)$mot['coeurs'] ?></span>
                <?php endif; ?>
            </button>
        </div>
    </article>
    <?php endforeach; ?>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
// ── Cœurs ──
document.querySelectorAll('.btn-coeur').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var self    = this;
        var actions = this.closest('.entree-actions');
        var id      = actions.dataset.id;

        self.style.transform = 'scale(1.3)';
        setTimeout(function() { self.style.transform = ''; }, 160);

        fetch('vote.php', {
            method:  'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:    'id=' + encodeURIComponent(id)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) return;
            var icon  = self.querySelector('.coeur-icon');
            var count = self.querySelector('.coeur-count');
            self.classList.toggle('voted', d.voted);
            icon.textContent = d.voted ? '♥' : '♡';
            self.title = d.voted ? 'Retirer mon cœur' : 'Donner un cœur';
            if (d.coeurs > 0) {
                if (!count) {
                    count = document.createElement('span');
                    count.className = 'coeur-count';
                    self.appendChild(count);
                }
                count.textContent = d.coeurs;
            } else if (count) {
                count.remove();
            }
        });
    });
});

// ── Copier ──
document.querySelectorAll('.btn-copier').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var self     = this;
        var texte    = this.closest('.entree-actions').dataset.texte;
        var original = self.textContent;

        function ok() {
            self.textContent = 'Copié ✓';
            self.classList.add('copie-ok');
            setTimeout(function() {
                self.textContent = original;
                self.classList.remove('copie-ok');
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texte).then(ok).catch(function() { fallback(texte, ok); });
        } else {
            fallback(texte, ok);
        }
    });
});

function fallback(texte, cb) {
    var ta = document.createElement('textarea');
    ta.value = texte;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); cb(); } catch(e) {}
    document.body.removeChild(ta);
}

// ── Écouter ──
var audioEnCours = null;
var btnEnCours   = null;

document.querySelectorAll('.btn-ecouter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var self = this;
        var id   = self.dataset.id;

        // Si ce bouton joue déjà → arrêter
        if (btnEnCours === self && audioEnCours) {
            audioEnCours.pause();
            audioEnCours = null;
            btnEnCours   = null;
            self.textContent = '▶ Écouter le mot';
            self.classList.remove('playing');
            return;
        }

        // Arrêter l'audio précédent si un autre jouait
        if (audioEnCours) {
            audioEnCours.pause();
            audioEnCours = null;
            if (btnEnCours) {
                btnEnCours.textContent = '▶ Écouter le mot';
                btnEnCours.classList.remove('playing', 'loading');
            }
        }

        // Chargement
        self.textContent = '… Chargement';
        self.classList.add('loading');
        btnEnCours = self;

        var audio = new Audio('tts.php?id=' + encodeURIComponent(id));
        audioEnCours = audio;

        audio.addEventListener('canplay', function() {
            if (audioEnCours !== audio) return;
            self.classList.remove('loading');
            self.classList.add('playing');
            self.innerHTML = '◼ Arrêter';
            audio.play();
        });

        audio.addEventListener('ended', function() {
            self.textContent = '▶ Écouter le mot';
            self.classList.remove('playing');
            audioEnCours = null;
            btnEnCours   = null;
        });

        audio.addEventListener('error', function() {
            self.textContent = '▶ Écouter le mot';
            self.classList.remove('loading', 'playing');
            audioEnCours = null;
            btnEnCours   = null;
        });
    });
});

</script>
</body>
</html>
<?php
require_once __DIR__ . '/app/bootstrap.php';

// ── Protection par mot de passe ──
$motDePasse = $config['admin']['password'] ?? 'changez-moi';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $motDePasse) {
        $_SESSION['admin_ok'] = true;
    } else {
        $erreurLogin = true;
    }
}

if (empty($_SESSION['admin_ok'])) {
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Administration : Dictionnaire imparfait</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="container" style="max-width:400px;margin:4rem auto">
        <h1 style="font-size:1.5rem;font-weight:normal;margin:0 0 2rem">Administration</h1>
        <?php if (!empty($erreurLogin)): ?>
            <p style="color:#9b1c1c;font-size:var(--taille-sm);margin:0 0 1rem">Mot de passe incorrect.</p>
        <?php endif; ?>
        <form method="post">
            <label>Mot de passe</label>
            <input type="password" name="admin_password" required autofocus>
            <button type="submit" class="btn" style="margin-top:1rem">Accéder</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$pdo = db($config);

// ── Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ajouter une ressource
    if (!empty($_POST['ajouter_ressource'])) {
        $titre    = trim($_POST['res_titre']    ?? '');
        $resume   = trim($_POST['res_resume']   ?? '');
        $url      = trim($_POST['res_url']      ?? '');
        $image    = trim($_POST['res_image']    ?? '');
        $source   = trim($_POST['res_source']   ?? '');
        $date     = trim($_POST['res_date']    ?? '') ?: null;
        $ordre    = (int) ($_POST['res_ordre']   ?? 0);
        if ($titre && $resume && $url) {
            $pdo->prepare("
                INSERT INTO dictionnaire_ressources (titre, resume, url, image_url, source, date_publication, ordre)
                VALUES (:titre, :resume, :url, :image, :source, :date, :ordre)
            ")->execute([
                ':titre'  => $titre,
                ':resume' => $resume,
                ':url'    => $url,
                ':image'  => $image ?: null,
                ':source' => $source ?: null,
                ':date'   => $date,
                ':ordre'  => $ordre,
            ]);
            $message = "Ressource ajoutée.";
        }
    }

    // Modifier une ressource
    if (!empty($_POST['modifier_ressource']) && !empty($_POST['res_id'])) {
        $rid    = (int) $_POST['res_id'];
        $titre  = trim($_POST['res_titre']  ?? '');
        $resume = trim($_POST['res_resume'] ?? '');
        $url    = trim($_POST['res_url']    ?? '');
        $image  = trim($_POST['res_image']  ?? '');
        $source = trim($_POST['res_source'] ?? '');
        $date   = trim($_POST['res_date']  ?? '') ?: null;
        $ordre  = (int) ($_POST['res_ordre'] ?? 0);
        if ($titre && $resume && $url) {
            $pdo->prepare("
                UPDATE dictionnaire_ressources
                SET titre = :titre, resume = :resume, url = :url,
                    image_url = :image, source = :source, date_publication = :date,
                    ordre = :ordre
                WHERE id = :id
            ")->execute([
                ':titre'  => $titre,
                ':resume' => $resume,
                ':url'    => $url,
                ':image'  => $image ?: null,
                ':source' => $source ?: null,
                ':date'   => $date,
                ':ordre'  => $ordre,
                ':id'     => $rid,
            ]);
            $message = "Ressource mise à jour.";
        }
    }

    // Supprimer une ressource
    if (!empty($_POST['supprimer_ressource']) && !empty($_POST['res_id'])) {
        $pdo->prepare("DELETE FROM dictionnaire_ressources WHERE id = :id")
            ->execute([':id' => (int)$_POST['res_id']]);
        $message = "Ressource supprimée.";
    }


    // Publier
    if (!empty($_POST['publier']) && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE dictionnaire_mots SET statut = 'finalise', updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $id]);

        // Notifier par email si renseigné
        $stmt = $pdo->prepare('SELECT mot_original, email_contributeur FROM dictionnaire_mots WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $mot = $stmt->fetch();
        if (!empty($mot['email_contributeur'])) {
            $sujet  = '[Dictionnaire imparfait] Votre mot a été publié';
            $corps  = "Bonjour,\n\nVotre mot « {$mot['mot_original']} » a été retenu et publié dans le Dictionnaire imparfait.\n\nVous pouvez le retrouver ici : https://" . $_SERVER['HTTP_HOST'] . "/dictionnaire.php\n\nMerci pour votre contribution.\n\nDictionnaire imparfait\nhttps://" . $_SERVER['HTTP_HOST'];
            $entetes = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            mail($mot['email_contributeur'], $sujet, $corps, $entetes);
            // Supprimer l'email après notification
            $pdo->prepare("UPDATE dictionnaire_mots SET email_contributeur = NULL WHERE id = :id")
                ->execute([':id' => $id]);
        }
        $message = "Le mot a été publié.";
    }

    // Refuser
    if (!empty($_POST['refuser']) && !empty($_POST['id'])) {
        $id     = (int) $_POST['id'];
        $motif  = trim($_POST['motif'] ?? '');
        $pdo->prepare("UPDATE dictionnaire_mots SET statut = 'brouillon', motif_refus = :motif, updated_at = NOW() WHERE id = :id")
            ->execute([':motif' => $motif, ':id' => $id]);

        // Notifier par email si renseigné
        $stmt = $pdo->prepare('SELECT mot_original, email_contributeur FROM dictionnaire_mots WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $mot = $stmt->fetch();
        if (!empty($mot['email_contributeur'])) {
            $sujet  = '[Dictionnaire imparfait] Votre proposition n\'a pas été retenue';
            $corps  = "Bonjour,\n\nVotre proposition « {$mot['mot_original']} » n'a pas été retenue pour le Dictionnaire imparfait.";
            if ($motif) {
                $corps .= "\n\n" . $motif;
            }
            $corps .= "\n\nVous pouvez modifier votre proposition et la soumettre à nouveau.\n\nDictionnaire imparfait\nhttps://" . $_SERVER['HTTP_HOST'];
            $entetes = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            mail($mot['email_contributeur'], $sujet, $corps, $entetes);
            // Supprimer l'email après notification
            $pdo->prepare("UPDATE dictionnaire_mots SET email_contributeur = NULL WHERE id = :id")
                ->execute([':id' => $id]);
        }
        $message = "La proposition a été refusée.";
    }
}

// ── Récupérer les mots en attente ──
$stmt = $pdo->query("
    SELECT id, mot_original, type_original, type_entree, score_total,
           email_contributeur, created_at
    FROM dictionnaire_mots
    WHERE statut = 'en_attente'
    ORDER BY created_at ASC
");
$mots = $stmt->fetchAll();

// ── Récupérer les ressources ──
$ressources = $pdo->query("SELECT * FROM dictionnaire_ressources ORDER BY ordre DESC, created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Administration : Dictionnaire imparfait</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-header {
            padding: 2rem 0 1.5rem;
            border-bottom: 2px solid var(--encre);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-titre { font-size: 1.5rem; font-weight: normal; margin: 0; }
        .mot-card {
            border: 1px solid var(--bordure);
            border-radius: var(--rayon-lg);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            background: var(--fond-carte);
        }
        .mot-card-header {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .mot-nom { font-size: 1.15rem; font-weight: bold; }
        .mot-type { font-style: italic; color: var(--brun); font-size: var(--taille-sm); }
        .mot-score {
            font-size: var(--taille-xs);
            padding: .15rem .55rem;
            border-radius: 999px;
            font-weight: 600;
            background: var(--fond-tag);
            color: var(--brun);
        }
        .mot-email {
            font-size: var(--taille-xs);
            color: var(--brun-clair);
            font-style: italic;
            margin: 0.25rem 0 0;
        }
        .mot-actions {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--fond-tag);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .mot-actions textarea {
            width: 100%;
            box-sizing: border-box;
            min-height: 64px;
            font-size: var(--taille-xs);
            font-family: Georgia, serif;
            color: var(--encre-doux);
            resize: vertical;
            margin: 0;
        }
        .mot-actions-boutons { display: flex; gap: 0.6rem; }
        .btn-publier {
            all: unset; box-sizing: border-box; cursor: pointer;
            font-family: Georgia, serif; font-size: var(--taille-xs);
            padding: 0.35rem 1.1rem; border-radius: 999px;
            border: 1px solid #1d6b35; background: #eef7ee; color: #1d6b35;
            transition: background 0.12s;
        }
        .btn-publier:hover { background: #d4edda; }
        .btn-refuser {
            all: unset; box-sizing: border-box; cursor: pointer;
            font-family: Georgia, serif; font-size: var(--taille-xs);
            padding: 0.35rem 1.1rem; border-radius: 999px;
            border: 1px solid #9b1c1c; background: #fdecec; color: #9b1c1c;
            transition: background 0.12s;
        }
        .btn-refuser:hover { background: #f8d0d0; }
        .vide {
            padding: 3rem 0;
            text-align: center;
            color: var(--brun-clair);
            font-style: italic;
        }
        .message-ok {
            padding: .75rem 1rem;
            background: #eef7ee;
            border: 1px solid #c3e0c3;
            border-radius: var(--rayon);
            color: #1d6b35;
            font-size: var(--taille-sm);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
<div class="container">

<div class="admin-header">
    <h1 class="admin-titre">Mots en attente de validation</h1>
    <a href="dictionnaire.php" class="btn secondary">Voir le dictionnaire</a>
</div>

<?php if (!empty($message)): ?>
    <p class="message-ok"><?= h($message) ?></p>
<?php endif; ?>

<?php if (empty($mots)): ?>
    <div class="vide">Aucun mot en attente de validation.</div>
<?php else: ?>
    <p style="font-size:var(--taille-xs);color:var(--brun-clair);font-style:italic;margin:0 0 1.5rem">
        <?= count($mots) ?> mot<?= count($mots) > 1 ? 's' : '' ?> en attente.
    </p>
    <?php foreach ($mots as $mot): ?>
        <div class="mot-card">
            <div class="mot-card-header">
                <span class="mot-nom"><?= h($mot['mot_original']) ?></span>
                <span class="mot-type"><?= h($mot['type_original']) ?></span>
                <?php if ($mot['score_total'] !== null): ?>
                    <span class="mot-score"><?= h($mot['score_total']) ?>/20</span>
                <?php endif; ?>
                <a href="analyse.php?id=<?= h($mot['id']) ?>" class="btn secondary" style="font-size:var(--taille-xs);padding:.2rem .6rem">Voir l'analyse</a>
            </div>

            <?php if (!empty($mot['email_contributeur'])): ?>
                <p class="mot-email">Notification prévue : <?= h($mot['email_contributeur']) ?></p>
            <?php else: ?>
                <p class="mot-email">Pas de notification (aucun email renseigné)</p>
            <?php endif; ?>

            <div class="mot-actions">
                <textarea name="motif" form="form-refus-<?= h($mot['id']) ?>"
                    placeholder="Motif du refus (optionnel, envoyé par email au contributeur)…"></textarea>
                <div class="mot-actions-boutons">
                    <form method="post" style="margin:0">
                        <input type="hidden" name="id" value="<?= h($mot['id']) ?>">
                        <button type="submit" name="publier" value="1" class="btn-publier">Publier</button>
                    </form>
                    <form method="post" style="margin:0" id="form-refus-<?= h($mot['id']) ?>">
                        <input type="hidden" name="id" value="<?= h($mot['id']) ?>">
                        <button type="submit" name="refuser" value="1" class="btn-refuser">Refuser</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<!-- ══ SECTION RESSOURCES ══ -->
<div style="margin-top:3rem;padding-top:2rem;border-top:2px solid var(--encre)">
    <h2 style="font-size:1.25rem;font-weight:normal;margin:0 0 1.5rem">Ressources</h2>

    <!-- Formulaire d'ajout -->
    <div style="background:var(--fond-doux);border:1px solid var(--bordure);border-radius:var(--rayon-lg);padding:1.5rem;margin-bottom:2rem">
        <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--brun-clair);margin:0 0 1rem">Ajouter une ressource</p>
        <form method="post" style="display:flex;flex-direction:column;gap:.75rem">
            <input type="text" name="res_titre" placeholder="Titre de l'article *" required style="margin:0">
            <textarea name="res_resume" placeholder="Résumé (2-3 phrases) *" required style="min-height:80px;margin:0"></textarea>
            <input type="url" name="res_url" placeholder="URL de l'article *" required style="margin:0">
            <input type="text" name="res_source" placeholder="Source (ex. Le Monde, Baptiste Morizot…)" style="margin:0">
            <input type="date" name="res_date" style="margin:0;font-size:var(--taille-xs)" title="Date de publication de l'article">
            <input type="number" name="res_ordre" value="0" min="0" max="99" style="margin:0;font-size:var(--taille-xs);width:80px" title="Ordre d'affichage (0 = dernier, 1 = premier)">
            <input type="url" name="res_image" placeholder="URL d'une image de couverture (optionnel)" style="margin:0">
            <div>
                <button type="submit" name="ajouter_ressource" value="1" class="btn">Ajouter</button>
            </div>
        </form>
    </div>

    <!-- Liste des ressources existantes -->
    <?php if (empty($ressources)): ?>
        <p style="font-size:var(--taille-xs);color:var(--brun-clair);font-style:italic">Aucune ressource pour le moment.</p>
    <?php else: ?>
        <?php foreach ($ressources as $r): ?>
            <div style="border:1px solid var(--bordure);border-radius:var(--rayon-lg);padding:1.25rem;margin-bottom:1rem;background:var(--fond-carte)">
                <!-- En-tête avec titre et bouton supprimer -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                    <div>
                        <?php if (!empty($r['source'])): ?>
                            <span style="font-size:var(--taille-xs);color:var(--brun-clair);text-transform:uppercase;letter-spacing:.06em"><?= h($r['source']) ?> · </span>
                        <?php endif; ?>
                        <strong style="font-size:var(--taille-sm)"><?= h($r['titre']) ?></strong>
                        <br>
                        <a href="<?= h($r['url']) ?>" target="_blank" rel="noopener"
                           style="font-size:var(--taille-xs);color:var(--brun)"><?= h($r['url']) ?></a>
                    </div>
                    <form method="post" style="margin:0;flex-shrink:0">
                        <input type="hidden" name="res_id" value="<?= h($r['id']) ?>">
                        <button type="submit" name="supprimer_ressource" value="1"
                                class="btn-refuser" style="white-space:nowrap"
                                onclick="return confirm('Supprimer cette ressource ?')">Supprimer</button>
                    </form>
                </div>

                <!-- Formulaire de modification (dépliable) -->
                <details style="margin-top:.5rem">
                    <summary style="font-size:var(--taille-xs);color:var(--brun);cursor:pointer;list-style:none;display:inline-flex;align-items:center;gap:.3rem">
                        ✎ Modifier
                    </summary>
                    <form method="post" style="display:flex;flex-direction:column;gap:.6rem;margin-top:.75rem">
                        <input type="hidden" name="res_id" value="<?= h($r['id']) ?>">
                        <input type="text" name="res_titre" value="<?= h($r['titre']) ?>" placeholder="Titre *" required style="margin:0;font-size:var(--taille-xs)">
                        <textarea name="res_resume" placeholder="Résumé *" required style="min-height:70px;margin:0;font-size:var(--taille-xs)"><?= h($r['resume']) ?></textarea>
                        <input type="url" name="res_url" value="<?= h($r['url']) ?>" placeholder="URL *" required style="margin:0;font-size:var(--taille-xs)">
                        <input type="text" name="res_source" value="<?= h($r['source'] ?? '') ?>" placeholder="Source" style="margin:0;font-size:var(--taille-xs)">
                        <input type="date" name="res_date" value="<?= h($r['date_publication'] ?? '') ?>" style="margin:0;font-size:var(--taille-xs)" title="Date de publication de l'article">
                        <input type="number" name="res_ordre" value="<?= (int)($r['ordre'] ?? 0) ?>" min="0" max="99" style="margin:0;font-size:var(--taille-xs);width:80px" title="Ordre d'affichage (0 = dernier, 1 = premier)">
                        <input type="url" name="res_image" value="<?= h($r['image_url'] ?? '') ?>" placeholder="URL image" style="margin:0;font-size:var(--taille-xs)">
                        <div>
                            <button type="submit" name="modifier_ressource" value="1" class="btn-publier">Enregistrer</button>
                        </div>
                    </form>
                </details>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
</body>
</html>

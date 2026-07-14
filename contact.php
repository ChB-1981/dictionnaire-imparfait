<?php
require_once __DIR__ . '/app/bootstrap.php';

$envoye  = false;
$erreur  = '';
$message = '';
$nom     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $honey   = $_POST['site'] ?? ''; // Champ honeypot anti-spam

    if ($honey !== '') {
        // Bot détecté — on fait semblant d'envoyer
        $envoye = true;
    } elseif (!$nom || !$message) {
        $erreur = 'Merci de remplir tous les champs.';
    } elseif (mb_strlen($message) < 10) {
        $erreur = 'Le message est trop court.';
    } else {
        $destinataire = $config['contact']['email'] ?? '';
        $sujet        = '[Dictionnaire imparfait] Message de ' . mb_substr($nom, 0, 60);
        $email_exp    = trim($_POST['email'] ?? '');
        $corps        = "Nom : " . $nom . "\n" . ($email_exp ? "Email : " . $email_exp . "\n" : "") . "\n" . $message;
        $entetes      = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'dictionnaire.charleshenriboisseau.fr') . "\r\n"
                      . "Reply-To: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'dictionnaire.charleshenriboisseau.fr') . "\r\n"
                      . "Content-Type: text/plain; charset=UTF-8\r\n";

        if ($destinataire && mail($destinataire, $sujet, $corps, $entetes)) {
            $envoye  = true;
            $nom     = '';
            $message = '';
        } else {
            $erreur = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contact : Dictionnaire imparfait</title>
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

        .contact-intro {
            max-width: 520px;
            color: var(--encre-doux);
            line-height: 1.75;
            margin-bottom: 2rem;
            font-size: var(--taille-sm);
        }

        .contact-form {
            max-width: 520px;
        }

        .honeypot {
            display: none;
        }

        .form-success {
            padding: 1.25rem 1.5rem;
            background: #eef7ee;
            border: 1px solid #c3e0c3;
            border-radius: var(--rayon-lg);
            color: #1d6b35;
            font-size: var(--taille-sm);
            line-height: 1.6;
            max-width: 520px;
        }

        .form-error {
            padding: 0.75rem 1rem;
            background: #fdecec;
            border: 1px solid #f0c0c0;
            border-radius: var(--rayon);
            color: #9b1c1c;
            font-size: var(--taille-sm);
            margin-bottom: 1rem;
        }

        .form-actions {
            margin-top: 0.5rem;
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
    <?php $navPage = 'autre'; include __DIR__ . '/nav.php'; ?>
</header>

<p class="contact-intro">
À vos mots. Prêts ? Envoyez.
</p>

<?php if ($envoye): ?>
    <div class="form-success">
        Merci pour votre message.<br/> Je vous répondrai dès que possible.
    </div>
<?php else: ?>
    <?php if ($erreur): ?>
        <div class="form-error"><?= h($erreur) ?></div>
    <?php endif; ?>

    <form method="post" action="contact.php" class="contact-form">

        <!-- Honeypot anti-spam -->
        <div class="honeypot">
            <label>Ne pas remplir</label>
            <input type="text" name="site" value="" autocomplete="off" tabindex="-1">
        </div>

        <label>Votre nom</label>
        <input type="text" name="nom" value="<?= h($nom) ?>" required placeholder="Comment vous appelez-vous ?">

        <label>Votre email <span style="font-size:var(--taille-xs);color:var(--brun-clair);font-style:italic;font-weight:normal">optionnel, si vous souhaitez une réponse</span></label>
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" placeholder="votre@email.fr">

        <label>Votre message</label>
        <textarea name="message" required placeholder="Votre message…" style="min-height:140px"><?= h($message) ?></textarea>

        <div class="form-actions">
            <button type="submit" class="btn">Envoyer</button>
        </div>

    </form>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>
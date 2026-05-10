<?php
/**
 * apropos.example.php — Template de la page À propos.
 *
 * Copiez ce fichier en apropos.php et personnalisez-le.
 * Ce fichier est un point de départ — adaptez les textes,
 * les principes et les conventions à votre propre projet.
 */

require_once __DIR__ . '/app/bootstrap.php';
$pdo    = db($config);
$nbMots = (int) $pdo->query("SELECT COUNT(*) FROM dictionnaire_mots WHERE statut = 'finalise'")->fetchColumn();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>À propos : Votre dictionnaire</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

<header class="page-header">
    <div>
        <h1 class="page-titre">Votre <span class="page-titre-imparfait">dictionnaire</span></h1>
        <p class="page-sous-titre">Votre tagline ici.</p>
    </div>
    <?php $navPage = 'autre'; include __DIR__ . '/nav.php'; ?>
</header>

<div class="apropos-corps">

    <h2>Le projet</h2>
    <p>Décrivez ici l'origine et l'intention de votre dictionnaire.</p>
    <p>Quels mots cherchez-vous à collecter ? Quelle expérience humaine explorez-vous ?</p>

    <h2>Trois principes</h2>
    <ul>
        <li><strong>Principe 1 :</strong> description.</li>
        <li><strong>Principe 2 :</strong> description.</li>
        <li><strong>Principe 3 :</strong> description.</li>
    </ul>

    <h2>Comment fonctionne l'analyse</h2>
    <p>
        Chaque proposition est soumise à un modèle de langage (GPT-4.1-mini d'OpenAI)
        instruit pour évaluer la qualité des mots proposés selon vos critères.
    </p>
    <button class="prompt-toggle" onclick="document.getElementById('prompt-complet').classList.toggle('open'); this.textContent = this.textContent.includes('Voir') ? 'Masquer le prompt ←' : 'Voir le prompt complet →'">
        Voir le prompt complet →
    </button>
    <div class="prompt-bloc" id="prompt-complet">
        <?php
        $promptTexte   = require __DIR__ . '/prompt.php';
        $promptAffiche = preg_replace('/\{\$[^}]+\}/', '…', $promptTexte);
        ?>
        <pre><?= h($promptAffiche) ?></pre>
    </div>

    <h2>Administration</h2>
    <p>
        Ce projet a été initié par <a href="#">Votre nom</a>.
        Basé sur <a href="https://github.com/ChB-1981/dictionnaire-imparfait.git" target="_blank" rel="noopener">Dictionnaire imparfait</a>
        de Charles-Henri Boisseau, sous licence MIT.
    </p>

</div>

<?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
// Le bouton prompt-toggle est géré inline
</script>
</body>
</html>

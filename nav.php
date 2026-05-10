<?php
$navPage = $navPage ?? 'autre';
?>
<nav class="nav-capsules">
    <?php if ($navPage !== 'accueil'): ?>
        <a href="index.php" class="tag">Revenir &agrave; l&rsquo;accueil</a>
    <?php endif; ?>
    <?php if ($navPage !== 'new'): ?>
        <a href="new.php" class="tag">Proposer un mot</a>
    <?php endif; ?>
    <?php if ($navPage !== 'dictionnaire'): ?>
        <a href="dictionnaire.php" class="tag">Lire le dictionnaire</a>
    <?php endif; ?>
</nav>

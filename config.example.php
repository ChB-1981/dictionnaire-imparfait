<?php
/**
 * config.example.php — Template de configuration.
 *
 * Copiez ce fichier en config.php et remplissez vos valeurs.
 * Le fichier config.php ne doit jamais être commité sur Git.
 *
 *   cp config.example.php config.php
 */

return [
    'db' => [
        'host'    => 'localhost',       // Hôte MySQL
        'name'    => 'votre_base',      // Nom de la base de données
        'user'    => 'votre_user',      // Utilisateur MySQL
        'pass'    => 'votre_mot_passe', // Mot de passe MySQL
        'charset' => 'utf8mb4',
    ],
    'contact' => [
        'email' => 'votre@email.fr',    // Email qui reçoit les messages de contact
    ],
    'openai' => [
        'api_key' => 'sk-...',          // Clé API OpenAI (https://platform.openai.com)
        'model'   => 'gpt-4.1-mini',   // Modèle utilisé pour l'analyse
    ],
];

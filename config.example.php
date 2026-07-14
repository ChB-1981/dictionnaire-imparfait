<?php
/**
 * config.php — Configuration du Dictionnaire imparfait
 *
 * SÉCURITÉ : Ne jamais committer config.php avec de vraies valeurs.
 * Copiez ce fichier en config.php et renseignez vos valeurs.
 * config.php est dans .gitignore.
 */

return [
    'db' => [
        'host'    => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
        'name'    => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'votre_base',
        'user'    => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'votre_user',
        'pass'    => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'votre_mot_de_passe',
        'charset' => 'utf8mb4',
    ],
    'contact' => [
        'email' => 'votre@email.fr',
    ],
    'openai' => [
        'api_key' => $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?: 'votre_cle_api',
        'model'   => 'gpt-4.1-mini',
    ],
    'admin' => [
        'password' => 'choisissez-un-mot-de-passe-solide',
    ],
];

# Dictionnaire imparfait

> Un dictionnaire collaboratif de mots qui n'existent pas encore en français, analysés par l'IA et validés par des humains.

**Site de référence :** [dictionnaire.charleshenriboisseau.fr](https://dictionnaire.charleshenriboisseau.fr)

---

## Présentation

Il existe des expériences que la langue laisse encore dans l'ombre. Des sensations discrètes, des manières d'habiter le monde que l'on reconnaît immédiatement, sans pourtant disposer d'un mot juste pour les dire.

Ce projet est un chantier ouvert. N'importe qui peut proposer un mot. Chaque proposition est analysée par un modèle de langage selon des critères définis, puis validée par l'utilisateur. Les mots retenus entrent dans la langue.

**Trois principes :**
- L'être avant l'avoir — un mot doit révéler une expérience humaine.
- L'humain avant la machine — l'IA analyse et suggère, l'humain décide.
- Le mot avant le nom — anonymat total, aucun compte requis.

---

## Prérequis

- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.3+
- Une clé API OpenAI (modèle `gpt-4.1-mini`)
- Un hébergement web avec cURL activé

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/charleshenriboisseau/dictionnaire-imparfait.git
cd dictionnaire-imparfait
```

### 2. Configurer

```bash
cp config.example.php config.php
```

Éditez `config.php` et renseignez vos identifiants de base de données, votre email de contact et votre clé API OpenAI.

### 3. Créer la base de données

Dans phpMyAdmin ou via la ligne de commande :

```bash
mysql -u votre_user -p votre_base < install.sql
```

Cela crée les quatre tables nécessaires et insère les 16 registres d'expérience par défaut.

### 4. Personnaliser

```bash
cp apropos.example.php apropos.php
```

Éditez `apropos.php` pour présenter votre propre projet.

---

## Structure des fichiers

```
dictionnaire-imparfait/
├── app/
│   ├── bootstrap.php       # Initialisation (session, config, db, helpers)
│   ├── db.php              # Connexion PDO
│   └── helpers.php         # Fonctions utilitaires
├── index.php               # Page d'accueil
├── new.php                 # Formulaire de proposition
├── save.php                # Enregistrement d'un mot
├── analyse.php             # Analyse IA d'une proposition
├── prompt.php              # Prompt d'analyse (à modifier pour personnaliser)
├── edit_original.php       # Modification d'un mot
├── update_original.php     # Mise à jour en base
├── finalize.php            # Validation définitive
├── dictionnaire.php        # Lecture du dictionnaire public
├── view.php                # Fiche d'un mot
├── vote.php                # Endpoint AJAX votes (cœurs)
├── tts.php                 # Text-to-speech via OpenAI
├── contact.php             # Formulaire de contact
├── nav.php                 # Navigation commune
├── footer.php              # Pied de page commun
├── style.css               # Styles globaux
├── install.sql             # Script de création de la base
├── config.example.php      # Template de configuration
├── apropos.example.php     # Template de la page À propos
├── .gitignore
└── LICENSE
```

---

## Personnalisation

Ce projet est conçu pour être décliné. Voici les points d'entrée principaux :

| Fichier | Ce qu'on y change |
|---|---|
| `config.php` | Credentials base de données, clé API, email |
| `prompt.php` | Les critères d'analyse de l'IA |
| `style.css` | Les couleurs et la typographie (variables CSS en haut du fichier) |
| `apropos.php` | Le texte de présentation du projet |
| `install.sql` | Les registres d'expérience (section `INSERT INTO dictionnaire_registres_experience`) |
| `app/helpers.php` | Les types grammaticaux et registres stylistiques disponibles |

### Faire évoluer le prompt

Le prompt d'analyse est dans `prompt.php`. Chaque modification doit faire l'objet d'un commit descriptif :

```bash
git commit -m "prompt: renforcer la règle mot_nature"
git commit -m "prompt: ajouter critère écriture inclusive"
```

L'historique Git devient le versioning du prompt.

---

## Fonctionnement de l'analyse

Chaque proposition est soumise à `gpt-4.1-mini` avec un prompt structuré. L'analyse porte sur :

1. **La conformité** (7 critères binaires) — le mot existe-t-il déjà ? L'étymologie est-elle crédible ? La définition exprime-t-elle une expérience vécue ? etc.
2. **La cohérence d'ensemble** (noté sur 10) — la construction du mot, sa définition, son étymologie et son exemple forment-ils un ensemble solide ?
3. **L'utilité lexicographique** (noté sur 10) — le mot apporte-t-il quelque chose de nouveau à la langue ?

Un mot peut être validé à partir de **14/20**, sans critère à zéro et sans blocage de conformité.

---

## Idées de déclinaisons

- Un dictionnaire des émotions sportives
- Un dictionnaire des états météorologiques intérieurs
- Un dictionnaire des gestes oubliés
- Un dictionnaire de l'enfance
- Un dictionnaire en anglais, en espagnol, en occitan…

---

## Licence

MIT — voir [LICENSE](LICENSE).

Vous pouvez utiliser, modifier et redistribuer ce projet librement, y compris pour des projets commerciaux, à condition de conserver la mention de l'auteur original dans le fichier `LICENSE`.

# Dictionnaire imparfait

Un moteur de dictionnaire collaboratif en PHP. Les contributeurs proposent des mots, un modèle de langage les analyse, les humains valident. Les mots retenus sont publiés dans un dictionnaire public.

**Site de référence :** [dictionnaireimparfait.fr](https://dictionnaireimparfait.fr)

Le projet est conçu pour être décliné : vous pouvez changer le thème, les critères d'analyse, le modèle de langage, les registres — et créer votre propre dictionnaire.

\---

## Prérequis

* PHP 8.0+
* MySQL 5.7+ ou MariaDB 10.3+
* Un hébergement web avec cURL activé
* Une clé API pour un modèle de langage (voir ci-dessous)

\---

## Installation

### 1\. Cloner le dépôt

```bash
git clone https://github.com/ChB-1981/dictionnaire-imparfait.git
cd dictionnaire-imparfait
```

### 2\. Configurer

```bash
cp config.example.php config.php
```

Éditez `config.php` et renseignez vos identifiants de base de données, votre email de contact et votre clé API.

### 3\. Créer la base de données

```bash
mysql -u votre\\\_user -p votre\\\_base < install.sql
```

Cela crée les quatre tables et insère les registres d'expérience par défaut.

### 4\. Personnaliser la page À propos

```bash
cp apropos.example.php apropos.php
```

\---

## Modèle de langage

Configuré par défaut pour **GPT-4.1-mini d'OpenAI**, mais aucune obligation d'utiliser OpenAI. Adaptez `analyse.php` et `config.php` pour utiliser :

* **OpenAI** : GPT-4o, GPT-4.1-mini... ([platform.openai.com](https://platform.openai.com))
* **Anthropic** : Claude Sonnet, Claude Haiku... ([console.anthropic.com](https://console.anthropic.com))
* **Mistral** : Mistral Large, Mistral Small... ([console.mistral.ai](https://console.mistral.ai))
* **Ollama** : modèles open source en local, sans coût ([ollama.com](https://ollama.com))

Le prompt d'analyse est dans `prompt.php`, indépendant du modèle choisi.

\---

## Personnalisation

|Fichier|Ce qu'on y change|
|-|-|
|`config.php`|Credentials base de données, clé API, email|
|`prompt.php`|Les critères d'analyse du modèle|
|`analyse.php`|L'appel API (à adapter selon le fournisseur)|
|`style.css`|Les couleurs et la typographie (variables CSS en haut du fichier)|
|`apropos.php`|Le texte de présentation du projet|
|`install.sql`|Les registres d'expérience (section `INSERT INTO`)|
|`app/helpers.php`|Les types grammaticaux et registres stylistiques disponibles|

### Faire évoluer le prompt

```bash
git commit -m "prompt: renforcer la règle mot\\\_nature"
```

L'historique Git devient le versioning du prompt.

\---

## Structure des fichiers

```
dictionnaire-imparfait/
├── app/
│   ├── bootstrap.php       # Initialisation
│   ├── db.php              # Connexion PDO
│   └── helpers.php         # Fonctions utilitaires et sécurité
├── index.php               # Page d'accueil
├── new.php                 # Formulaire de proposition
├── save.php                # Enregistrement d'un mot
├── analyse.php             # Analyse par le modèle de langage
├── prompt.php              # Prompt d'analyse (personnalisable)
├── edit\\\_original.php       # Modification d'un mot
├── update\\\_original.php     # Mise à jour en base
├── finalize.php            # Validation définitive
├── dictionnaire.php        # Dictionnaire public
├── view.php                # Fiche d'un mot
├── vote.php                # Endpoint AJAX votes
├── tts.php                 # Text-to-speech (optionnel, via OpenAI)
├── contact.php             # Formulaire de contact
├── nav.php                 # Navigation commune
├── footer.php              # Pied de page commun
├── style.css               # Styles globaux
├── install.sql             # Création de la base
├── config.example.php      # Template de configuration
├── apropos.example.php     # Template de la page À propos
├── .gitignore
└── LICENSE
```

\---

## Le projet

Il existe des expériences que la langue laisse encore dans l'ombre. Des sensations discrètes, des manières d'habiter le monde que l'on reconnaît immédiatement, sans pourtant disposer d'un mot juste pour les dire.

Ce dictionnaire est né de ce manque. Trois principes le guident.

* **L'être avant l'avoir** : un mot doit révéler une expérience humaine, pas désigner un objet ou une technique.
* **L'humain avant la machine** : le modèle analyse et suggère, l'humain crée et valide.
* **Le mot avant le nom** : anonymat total, aucun compte requis.

\---

## Idées de déclinaisons

* Un dictionnaire des mots oubliés
* Un dictionnaire de l'Anthropocène
* Un dictionnaire du patois charentais…

\---

## Licence

MIT — voir [LICENSE](LICENSE).

Libre d'utilisation, de modification et de redistribution, y compris pour des projets commerciaux, à condition de conserver la mention de l'auteur original dans le fichier `LICENSE`.


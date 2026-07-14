# Dictionnaire imparfait

Un moteur de dictionnaire collaboratif en PHP. Les contributeurs proposent des mots, un modèle de langage les analyse, les humains valident. Les mots retenus sont publiés dans un dictionnaire public.

**Site de référence :** [dictionnaire.charleshenriboisseau.fr](https://dictionnaire.charleshenriboisseau.fr)

Le projet est conçu pour être décliné : changez le thème, les critères d'analyse, le modèle de langage, les registres et créez votre propre dictionnaire.

---

## Prérequis

- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.3+
- Un hébergement web avec cURL activé
- Une clé API pour un modèle de langage (voir ci-dessous)

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/ChB-1981/dictionnaire-imparfait.git
cd dictionnaire-imparfait
```

### 2. Configurer

```bash
cp config.example.php config.php
```

Éditez `config.php` et renseignez vos identifiants de base de données, votre email de contact, votre clé API et le mot de passe de la page d'administration.

### 3. Créer la base de données

```bash
mysql -u votre_user -p votre_base < install.sql
```

Cela crée les tables et insère les 16 registres d'expérience par défaut.

### 4. Personnaliser la page À propos

```bash
cp apropos.example.php apropos.php
```

Éditez `apropos.php` pour présenter votre projet.

---

## Modèle de langage

Configuré par défaut pour **GPT-4.1-mini d'OpenAI**, mais adaptable à n'importe quel fournisseur. Modifiez `analyse.php` et `config.php` pour utiliser :

- **OpenAI** : GPT-4o, GPT-4.1-mini... ([platform.openai.com](https://platform.openai.com))
- **Anthropic** : Claude Sonnet, Claude Haiku... ([console.anthropic.com](https://console.anthropic.com))
- **Mistral** : Mistral Large, Mistral Small... ([console.mistral.ai](https://console.mistral.ai))
- **Ollama** : modèles open source en local, sans coût ([ollama.com](https://ollama.com))

Le prompt d'analyse est dans `prompt.php`, indépendant du modèle choisi.

---

## Personnalisation

| Fichier | Ce qu'on y change |
|---|---|
| `config.php` | Credentials base de données, clé API, email, mot de passe admin |
| `prompt.php` | Les critères d'analyse du modèle |
| `analyse.php` | L'appel API (à adapter selon le fournisseur) |
| `style.css` | Les couleurs et la typographie (variables CSS en haut du fichier) |
| `apropos.php` | Le texte de présentation du projet |
| `install.sql` | Les registres d'expérience (section `INSERT INTO`) |
| `app/helpers.php` | Les types grammaticaux et registres stylistiques disponibles |

### Faire évoluer le prompt

Le fichier `prompt.php` contient les critères d'analyse du modèle. Chaque modification significative mérite un commit Git avec un message descriptif — l'historique devient ainsi le versioning du prompt.

```bash
git commit -m "prompt v1.4 : ajout type_entree, coherence_categorie"
```

---

## Comment fonctionne la validation

Le parcours d'un mot suit trois étapes.

1. **Proposition** — le contributeur soumet un mot via `new.php`. Le modèle de langage l'analyse et propose des reformulations. Le contributeur peut modifier sa proposition autant de fois qu'il le souhaite.
2. **Soumission** — lorsque le score atteint 16/20 sans critère bloquant, le contributeur peut soumettre son mot. Il passe en statut `en_attente`. L'éditeur reçoit une notification par email.
3. **Validation éditoriale** — depuis la page `admin.php` (protégée par mot de passe), l'éditeur consulte les mots en attente, peut voir l'analyse complète, puis publie ou refuse avec un motif. Le contributeur est notifié par email si il en a renseigné un.

---

## Structure des fichiers

```
dictionnaire-imparfait/
├── app/
│   ├── bootstrap.php           # Initialisation
│   ├── db.php                  # Connexion PDO
│   └── helpers.php             # Fonctions utilitaires et sécurité
├── migrations/                 # Migrations SQL (évolutions de schéma)
├── index.php                   # Page d'accueil
├── new.php                     # Formulaire de proposition
├── save.php                    # Enregistrement d'un mot
├── analyse.php                 # Analyse par le modèle de langage
├── prompt.php                  # Prompt d'analyse (versionné séparément)
├── edit_original.php           # Modification d'un mot
├── update_original.php         # Mise à jour en base
├── finalize.php                # Soumission pour validation éditoriale
├── confirmation.php            # Page de confirmation après soumission
├── admin.php                   # Administration (validation, ressources)
├── dictionnaire.php            # Dictionnaire public
├── ressources.php              # Page des ressources éditoriales
├── view.php                    # Fiche d'un mot
├── vote.php                    # Endpoint AJAX votes
├── tts.php                     # Text-to-speech (optionnel, via OpenAI)
├── contact.php                 # Formulaire de contact
├── apropos.php                 # Page À propos (personnalisée, hors git)
├── nav.php                     # Navigation commune
├── footer.php                  # Pied de page commun
├── style.css                   # Styles globaux
├── install.sql                 # Création de la base de données
├── config.example.php          # Template de configuration
├── apropos.example.php         # Template de la page À propos
├── .htaccess                   # Redirection HTTPS et index par défaut
├── .gitignore
└── LICENSE
```

---

## Le projet initial

Il existe des expériences que la langue laisse encore dans l'ombre. Des sensations discrètes, des manières d'habiter le monde que l'on reconnaît immédiatement, sans pourtant disposer d'un mot juste pour les dire.

Ce dictionnaire est né de ce manque. Trois principes le guident.

- **L'être avant l'avoir** : un mot doit révéler une expérience humaine, pas désigner un objet ou une technique.
- **L'humain avant la machine** : le modèle analyse et suggère, l'humain crée et valide.
- **Le mot avant le nom** : anonymat total, aucun compte requis.

---

## Nature des mots

Le dictionnaire distingue quatre types d'entrées.

- **Inventé** : mot créé de toutes pièces, n'existe pas en français ni ailleurs.
- **Réactivé** : mot français existant auquel on donne un sens nouveau et distinct du sens courant.
- **Importé** : mot emprunté à une autre langue, francisé ou non, sans équivalent en français.
- **Ressuscité** : mot du vieux français ou du français classique remis en circulation.

---

## Idées de déclinaisons

- Un dictionnaire des émotions sportives
- Un dictionnaire des états météorologiques intérieurs
- Un dictionnaire des gestes oubliés
- Un dictionnaire de l'enfance

---

## Licence

MIT — voir [LICENSE](LICENSE).

Libre d'utilisation, de modification et de redistribution, y compris pour des projets commerciaux, à condition de conserver la mention de l'auteur original dans le fichier `LICENSE`.

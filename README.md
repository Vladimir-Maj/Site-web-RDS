# StageFlow

> Plateforme web de recherche et gestion de stages — CESI Nancy, CPI 2ème année (2025–2026)

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures étudiantes dans une interface unique, adaptée à chaque profil utilisateur (administrateur, pilote de promotion, étudiant, visiteur anonyme).

---

## Équipe

| Nom | Rôle Scrum |
|---|---|
| Clément BERGER | Développeur |
| Arthur CHANTRAINE | Développeur |
| Vladimir MAJCHER | Développeur |
| Turker CALISKAN | Développeur |

---

## Démarrage rapide

## Prérequis

- Git
- Docker + Docker Compose v2
- Composer (pour l'installation locale des dépendances PHP)

> **Ports requis :** `80` (HTTP) et `443` (HTTPS).  
> Si Apache tourne déjà sur votre machine, libérez les ports avant de lancer le projet :
> ```bash
> sudo systemctl stop apache2
> sudo systemctl disable apache2  # Empêche le redémarrage automatique au boot
> ```

---
## Installation

### 1. Cloner le projet
```bash
git clone git@github.com:Vladimir-Maj/WS-priv.git
cd WS-priv
```

### 2. Installer les dépendances PHP (obligatoire)
> Sans cette étape, le conteneur `phpunit` échoue au démarrage avec l'erreur `vendor/bin/phpunit: no such file or directory`.

```bash
cd www/prod
composer install
cd ../..
```

### 3. Configurer les domaines locaux
```bash
echo "127.0.0.1 prod.stageflow.fr" | sudo tee -a /etc/hosts
echo "127.0.0.1 cdn.stageflow.fr" | sudo tee -a /etc/hosts
```

### 4. Construire et démarrer les conteneurs
```bash
docker compose up -d --build
```

C'est tout. Docker monte automatiquement Apache, PHP et MySQL avec la base de données initialisée.

### 5. Vérifier que tout tourne
```bash
docker compose ps
```

---

## Comptes de test

> Les comptes ci-dessous fonctionnent uniquement si un seed SQL a été importé. Le schéma seul ne crée aucun compte.

| Email | Mot de passe | Rôle |
|---|---|---|
| `admin@example.com` | `Admin1234!` | Administrateur |
| `pilot@example.com` | `Pilote1234!` | Pilote |
| `pilot2@example.com` | `Pilote1234!` | Pilote |
| `student1@example.com` | `Etudiant1234!` | Étudiant |
| `student2@example.com` | `EtudiantBis1234!` | Étudiant |
| `test@example.com` | `Test1234!` | Étudiant |


## Accès

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | http://prod.stageflow.fr |
| Assets & Médias (Vhost CDN) | http://cdn.stageflow.fr |

> L'accès se fait directement sur le port **80**, sans numéro de port dans l'URL.

---

## Base de données

La connexion est configurée dans `www/prod/.back/util/db_connect.php` :

| Paramètre | Valeur |
|---|---|
| Hôte | `db` |
| Port | `3306` |
| Base | `sql_db` |
| Utilisateur | `website-local` |
| Mot de passe | `1234` |

Le schéma est chargé automatiquement au démarrage depuis `mysql/init/01-create-tables.sql`.

> **Important :** le code attend le nouveau schéma avec les colonnes nommées `id_user`, `email_user`, `title_internship_offer`, etc. Utiliser un ancien schéma cassera l'application.

---

## Charger le jeu de données

Une fois les conteneurs démarrés, vous pouvez importer le jeu de données dans MySQL avec le fichier `seed.sql` placé à la racine du projet.

### Import du seed

Depuis la racine du projet :

```bash
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql

### Scripts utilitaires

Des scripts bash sont disponibles dans le dossier `scripts/` pour faciliter la gestion du projet :

```bash
scripts/chmod_all_scripts.sh   # Donne les droits d'exécution à tous les scripts
scripts/clean-tree.sh          # Nettoie l'arborescence
scripts/db-logs.sh             # Affiche les logs MySQL
scripts/server-logs.sh         # Affiche les logs Apache
scripts/units.sh               # Lance les tests unitaires
scripts/wipe_volumes_clean.sh  # Réinitialise les volumes Docker (BDD comprise)
```

---

## Fonctionnalités

StageFlow propose des interfaces différentes selon le profil de l'utilisateur connecté.

Les **visiteurs anonymes** peuvent consulter les entreprises, les offres de stage et les statistiques sans créer de compte.

Les **étudiants** peuvent en plus postuler aux offres, suivre leurs candidatures et gérer leur wish-list personnelle.

Les **pilotes de promotion** ont accès à la gestion des entreprises et des offres, et peuvent consulter les candidatures de leurs élèves.

Les **administrateurs** ont accès à l'ensemble de la plateforme, y compris la gestion des comptes pilotes et l'administration complète des utilisateurs.

La liste complète des 25 fonctionnalités (SFx1 à SFx25) est détaillée dans le [cahier des charges](docs/cahier-des-charges.md).

---

## Architecture

Le projet suit une architecture **MVC stricte**, sans framework backend ni CMS.

```
WS-priv/
├── apache/
│   ├── entrypoint.sh          # Script de démarrage Apache (génère le certificat SSL)
│   └── vhosts.conf            # Configuration des vhosts (prod + cdn)
├── deprecated/                # Anciennes pages (non actives)
│   ├── account/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── profile.php
│   │   ├── register.php
│   │   └── upload_cv.php
│   └── offers/
│       ├── offer_delete.php
│       ├── offer_detail.php
│       ├── offer_editor.php
│       └── offer_search.php
├── mysql/
│   └── init/
│       └── 01-create-tables.sql   # Schéma de la base de données
├── scripts/
│   ├── chmod_all_scripts.sh
│   ├── clean-tree.sh
│   ├── db-logs.sh
│   ├── server-logs.sh
│   ├── units.sh
│   └── wipe_volumes_clean.sh
├── www/
│   ├── cdn/
│   │   ├── assets/            # Images, fonts et autres médias
│   │   └── styles.css         # Feuille de style principale
│   └── prod/
│       ├── .back/             # Logique serveur (routeur, auth, contrôleurs)
│       │   ├── controllers/
│       │   ├── models/
│       │   ├── repository/
│       │   ├── templates/
│       │   └── util/
│       ├── tests/             # Tests unitaires PHPUnit
│       ├── .htaccess          # Réécriture d'URL (mod_rewrite)
│       ├── composer.json
│       ├── composer.lock
│       └── index.php          # Point d'entrée unique de l'application
├── .gitignore
├── CLAUDE.md
├── Dockerfile
├── docker-compose.yaml
└── README.md
```

### Couche de données — Repositories

Les interactions avec la base de données sont centralisées dans des classes Repository dédiées :

| Repository | Responsabilités |
|---|---|
| `OfferRepository` | Recherche multicritères, pagination, détails d'une offre, statistiques |
| `CompanyRepository` | Liste des entreprises, filtrage, évaluations, détails |
| `UserRepository` | Authentification, gestion des profils étudiants et pilotes |
| `ApplicationRepository` | Candidatures, wish-list, suivi par étudiant et par pilote |
| `PromotionRepository` | Promotions et affectations pilotes |
| `SkillRepository` | Compétences requises par les offres |

### Logique de recherche et filtres

La recherche d'offres utilise une construction de requête dynamique avec `WHERE 1=1` :

```php
$sql = "SELECT * FROM internship_offer WHERE 1=1";
if ($keyword)  $sql .= " AND (title_internship_offer LIKE ? OR description_internship_offer LIKE ?)";
if ($location) $sql .= " AND city_company_site = ?";
if ($company)  $sql .= " AND id_company = ?";
```

Chaque filtre est optionnel et les valeurs sont passées via requête préparée (PDO).

---


## Stack technique

| Couche | Technologie |
|---|---|
| Serveur | Apache 2.4 (via Docker) |
| Frontend | HTML5 / CSS3 / JavaScript vanilla |
| Backend | PHP 8.2 — POO, PSR-12 |
| Moteur de template | Twig |
| Base de données | MySQL 8 |
| Tests | PHPUnit |
| Conteneurisation | Docker + Docker Compose |
| Versionning | Git — workflow Git Flow |

Aucun framework frontend (React, Vue, Angular) ni backend (Laravel, Symfony) n'est utilisé, conformément aux spécifications techniques du projet.

---

## Tests

Les tests unitaires couvrent au minimum un contrôleur complet avec PHPUnit.

```bash
# Via le script dédié
bash scripts/units.sh

# Ou directement depuis le conteneur PHP
docker exec -it stageflow-php ./vendor/bin/phpunit www/prod/tests/
```

---

## Workflow Git

Le projet utilise **Git Flow** :

- `main` — version stable / livrée
- `develop` — branche d'intégration continue
- `feature/[nom]` — développement d'une fonctionnalité
- `fix/[nom]` — correction de bug

Chaque fonctionnalité passe par une Pull Request relue par un autre membre avant merge sur `develop`.

---

## Sécurité

- Mots de passe hashés avec `password_hash()` / vérifiés avec `password_verify()` (bcrypt)
- Sessions serveur + cookies `HttpOnly`, `Secure`, `SameSite=Strict`
- Protection SQL via PDO et requêtes préparées exclusivement
- Protection XSS via l'échappement automatique Twig (`{{ var }}`)
- Tokens CSRF sur tous les formulaires
- HTTPS activé via certificat auto-signé

---

## Licence

Projet académique — CESI Nancy. Non destiné à un usage en production.
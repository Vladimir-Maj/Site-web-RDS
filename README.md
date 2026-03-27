# StageFlow

> Plateforme web de recherche et gestion de stages — CESI Nancy, CPI 2ème année (2025–2026)

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures étudiantes dans une interface unique, adaptée à chaque profil utilisateur (administrateur, pilote de promotion, étudiant, visiteur anonyme).

---

## Démo

> 🔗 [prod.localhost:8080](http://prod.localhost:8080) — accessible en local après installation

| Identifiant de test | Rôle |
|---|---|
| `admin@stageflow.fr` / `Admin1234!` | Administrateur |
| `pilote@stageflow.fr` / `Pilote1234!` | Pilote de promotion |
| `etudiant@stageflow.fr` / `Etudiant1234!` | Étudiant |

---

## Démarrage rapide
### Prérequis

- Docker Desktop (ou Docker + Docker Compose)
- Git

### Installation

```bash
git clone https://github.com/Vladimir-Maj/Site-web-RDS.git
cd stageflow
docker compose up -d
```

C'est tout. Docker monte automatiquement Apache, PHP et MySQL avec la base de données initialisée.

### Accès aux environnements

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | http://prod.localhost:8080 |
| Assets & Médias (Vhost CDN) | http://cdn.localhost:8080 |

### Configuration de la base de données

Les paramètres de connexion sont dans `util/config.php`. En développement local, les valeurs par défaut du `docker-compose.yml` s'appliquent sans modification.

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
stageflow/
├── www/
│   ├── prod/               # Point d'entrée (index.php) + contrôleurs de pages
│   │   └── .back/          # Logique serveur (routeur, auth, traitement)
│   └── cdn/                # Templates HTML statiques, CSS, JS, uploads
├── repository/             # Classes d'accès BDD (PDO, requêtes préparées)
├── util/                   # Helpers : rendu HTML, formatage, configuration
├── sql/
│   ├── schema.sql          # Création des tables
│   └── fixtures.sql        # Données de démonstration
├── tests/                  # Tests unitaires PHPUnit
├── docs/                   # MCD, MLD, cahier des charges
├── docker-compose.yml
├── sitemap.xml
└── robots.txt
```

### Couche de données — Repositories

Les interactions avec la base de données sont centralisées dans des classes Repository dédiées :

| Repository | Responsabilités |
|---|---|
| `OfferRepository` | Recherche multicritères, pagination, détails d'une offre, statistiques |
| `CompanyRepository` | Liste des entreprises, filtrage, évaluations, détails |
| `UserRepository` | Authentification, gestion des profils étudiants et pilotes |
| `ApplicationRepository` | Candidatures, wish-list, suivi par étudiant et par pilote |

### Logique de recherche et filtres

La recherche d'offres utilise une construction de requête dynamique avec `WHERE 1=1`, ce qui permet d'ajouter les filtres actifs sans casser la syntaxe SQL :

```php
$sql = "SELECT * FROM offre_stage WHERE 1=1";
if ($keyword)  $sql .= " AND (titre LIKE ? OR description LIKE ? OR nom_entreprise LIKE ?)";
if ($location) $sql .= " AND localisation = ?";
if ($company)  $sql .= " AND id_entreprise = ?";
if ($type)     $sql .= " AND type_contrat = ?";
```

Chaque filtre est optionnel et les valeurs sont passées via requête préparée (PDO).

---

## Stack technique

| Couche | Technologie |
|---|---|
| Serveur | Apache 2.4 (via Docker) |
| Frontend | HTML5 / CSS3 / JavaScript vanilla |
| Backend | PHP 8.x — POO, PSR-12 |
| Moteur de template | Twig |
| Base de données | MySQL / MariaDB |
| Tests | PHPUnit |
| Conteneurisation | Docker + Docker Compose |
| Versionning | Git — workflow Git Flow |

Aucun framework frontend (React, Vue, Angular) ni backend (Laravel, Symfony) n'est utilisé, conformément aux spécifications techniques du projet.

---

## Tests

Les tests unitaires couvrent au minimum un contrôleur complet avec PHPUnit.

```bash
# Lancer tous les tests (depuis le conteneur PHP)
docker exec -it stageflow-php ./vendor/bin/phpunit tests/

# Lancer un test spécifique
docker exec -it stageflow-php ./vendor/bin/phpunit tests/OffreControllerTest.php
```

---

## Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Sessions serveur + cookies `HttpOnly`, `Secure`, `SameSite=Strict`
- Protection SQL via PDO et requêtes préparées exclusivement
- Protection XSS via l'échappement automatique Twig (`{{ var }}`)
- Tokens CSRF sur tous les formulaires
- HTTPS en production

---

## Workflow Git

Le projet utilise **Git Flow** :

- `main` — version stable / livrée
- `develop` — branche d'intégration continue
- `feature/[nom]` — développement d'une fonctionnalité
- `fix/[nom]` — correction de bug

Chaque fonctionnalité passe par une Pull Request relue par un autre membre avant merge sur `develop`.

---

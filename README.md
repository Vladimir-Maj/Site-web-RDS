# StageFlow

> Plateforme web de recherche et gestion de stages — CESI Nancy, CPI 2ème année (2025–2026)

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures étudiantes dans une interface unique, adaptée à chaque profil utilisateur : administrateur, pilote de promotion, étudiant ou visiteur anonyme.

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

### Prérequis

- Git
- Docker + Docker Compose v2
- Composer (pour l'installation locale des dépendances PHP)

> **Ports requis :** `80` (HTTP) et `443` (HTTPS)  
> Si Apache tourne déjà sur votre machine, libérez les ports avant de lancer le projet :
>
> ```bash
> sudo systemctl stop apache2
> sudo systemctl disable apache2
> ```

---

### Installation

#### 1. Cloner le projet

```bash
git clone git@github.com:Vladimir-Maj/Site-web-RDS.git
cd Site-web-RDS
```

### 2. Installer les dépendances PHP (obligatoire)

> Sans cette étape, PHPUnit et certaines dépendances PHP ne seront pas disponibles. Le conteneur ou service de test peut échouer avec l'erreur vendor/bin/phpunit: no such file or directory.

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

## Accès

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | http://prod.stageflow.fr |
| Assets & médias (Vhost CDN) | http://cdn.stageflow.fr |

> L'accès se fait directement sur le port **80**, sans numéro de port dans l'URL.

---

## Services Docker

Le projet s'appuie sur plusieurs services Docker définis dans `docker-compose.yaml` :

| Service | Rôle |
|---|---|
| `web` | Exécution de l'application PHP et serveur Apache |
| `db` | Base de données MySQL |
| `cdn` / vhost statique | Distribution des assets statiques selon la configuration Apache |

> Le nom exact des services peut être vérifié dans `docker-compose.yaml`.

---

## Base de données

La connexion est configurée dans `www/prod/.back/util/db_connect.php` :

| Paramètre | Valeur |
|---|---|
| Hôte | db |
| Port | 3306 |
| Base | sql_db |
| Utilisateur | website-local |
| Mot de passe | 1234 |

Le schéma est chargé automatiquement au démarrage depuis `mysql/init/01-create-tables.sql`.

> **Important :** le code utilise le nouveau schéma avec des colonnes comme `id_user`, `email_user`, `id_company`, `id_internship_offer`, etc. Utiliser un ancien schéma cassera l'application.

---

## Réinitialiser complètement le projet

Pour repartir d'un environnement propre, base de données comprise :

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
```

Cette opération arrête les conteneurs, supprime les volumes, réinitialise la base de données et relance les services à partir d'un état propre.

---

## Charger le jeu de données

Une fois les conteneurs démarrés, vous pouvez importer le jeu de données avec le fichier `seed.sql` placé à la racine du projet.

### Import du seed

```bash
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Si MySQL n'est pas encore prêt juste après le démarrage des conteneurs, attendez quelques secondes puis relancez simplement la commande.
>
> docker compose exec -T db mysql -u website-local -p1234 -e \
  "USE sql_db; \
   SELECT COUNT(*) AS users_count FROM user; \
   SELECT COUNT(*) AS offers_count FROM internship_offer; \
   SELECT COUNT(*) AS applications_count FROM application; \
   SELECT COUNT(*) AS wishlist_count FROM wishlist;"

### Réinitialiser puis réimporter le seed

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

### Vérifier l'import

```bash
docker exec -it lamp-db mysql -u website-local -p1234 -e \
  "USE sql_db; \
   SELECT COUNT(*) AS users_count FROM user; \
   SELECT COUNT(*) AS offers_count FROM internship_offer; \
   SELECT COUNT(*) AS applications_count FROM application; \
   SELECT COUNT(*) AS wishlist_count FROM wishlist;"
```

### Contenu du jeu de données

Le fichier `seed.sql` a été conçu pour la démonstration et la soutenance :

- données réalistes,
- comptes de test exploitables,
- promotions demandées par le jury,
- au moins 15 enregistrements dans chaque table principale.

---

## Comptes de test

> Ces comptes fonctionnent uniquement si le `seed.sql` a été importé. Le schéma seul ne crée aucun utilisateur.

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@example.com | Admin1234! | Administrateur |
| pilot@example.com | Pilote1234! | Pilote |
| pilot2@example.com | Pilote1234! | Pilote |
| student1@example.com | Etudiant1234! | Étudiant |
| student2@example.com | EtudiantBis1234! | Étudiant |
| test@example.com | Test1234! | Étudiant |

---

## Scripts utilitaires

Des scripts bash sont disponibles dans le dossier `scripts/` pour faciliter l'exploitation du projet :

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

StageFlow propose des interfaces différentes selon le profil utilisateur.

- Les **visiteurs anonymes** peuvent consulter les entreprises, les offres de stage et certaines informations publiques.
- Les **étudiants** peuvent consulter les offres, postuler, suivre leurs candidatures et gérer leur wish-list.
- Les **pilotes de promotion** peuvent gérer les entreprises et les offres, et consulter les candidatures de leurs élèves.
- Les **administrateurs** ont accès à l'ensemble de la plateforme, y compris la gestion des comptes.

La liste complète des fonctionnalités est définie dans le cahier des charges du projet.

### Matrice simplifiée des rôles

| Action | Anonyme | Étudiant | Pilote | Admin |
|---|---|---|---|---|
| Voir les offres | ✅ | ✅ | ✅ | ✅ |
| Voir les entreprises | ✅ | ✅ | ✅ | ✅ |
| Se connecter / se déconnecter | ✅ | ✅ | ✅ | ✅ |
| Postuler à une offre | ❌ | ✅ | ❌ | ❌ |
| Gérer sa wish-list | ❌ | ✅ | ❌ | ❌ |
| Gérer les offres | ❌ | ❌ | ✅ | ✅ |
| Gérer les entreprises | ❌ | ❌ | ✅ | ✅ |
| Gérer les comptes | ❌ | ❌ | ❌ | ✅ |

---

## État d'avancement

### Fonctionnalités réalisées ou bien avancées

- Authentification / déconnexion
- Catalogue des offres
- Détail d'une offre
- Pagination des offres
- Page d'accueil avec message de bienvenue
- Suivi des candidatures pour l'étudiant sur l'accueil
- Wish-list étudiant
- Ajout / retrait d'une offre à la wish-list
- Flash messages utilisateurs
- Routage backend propre
- Utilisation de Twig sur les vues principales

### Fonctionnalités en cours ou à finaliser

- Gestion complète des entreprises
- Gestion complète des pilotes
- Gestion complète des étudiants
- Vue complète des candidatures par étudiant
- Vue des candidatures des élèves pour le pilote
- Statistiques avancées
- Tests unitaires complets
- Mentions légales finalisées
- `robots.txt` et `sitemap.xml`

---

## Architecture

Le projet suit une architecture **MVC stricte**, sans framework backend ni CMS.

```
Site-web-RDS/
├── apache/
│   ├── entrypoint.sh          # Script de démarrage Apache
│   └── vhosts.conf            # Configuration des vhosts (prod + cdn)
├── deprecated/                # Anciennes pages non utilisées
│   ├── account/
│   └── offers/
├── mysql/
│   └── init/
│       └── 01-create-tables.sql
├── scripts/
│   ├── chmod_all_scripts.sh
│   ├── clean-tree.sh
│   ├── db-logs.sh
│   ├── server-logs.sh
│   ├── units.sh
│   └── wipe_volumes_clean.sh
├── www/
│   ├── cdn/
│   │   ├── assets/
│   │   └── styles.css
│   └── prod/
│       ├── .back/
│       │   ├── controllers/
│       │   ├── models/
│       │   ├── repository/
│       │   ├── templates/
│       │   └── util/
│       ├── tests/
│       ├── .htaccess
│       ├── composer.json
│       ├── composer.lock
│       └── index.php
├── .gitignore
├── CLAUDE.md
├── Dockerfile
├── docker-compose.yaml
├── README.md
└── seed.sql
```

### Couche de données — Repositories

Les interactions avec la base sont centralisées dans des classes Repository dédiées :

| Repository | Responsabilités |
|---|---|
| `OfferRepository` | Recherche multicritères, pagination, détails d'une offre |
| `CompanyRepository` | Liste des entreprises, filtrage, détails |
| `UserRepository` | Authentification, gestion des profils |
| `ApplicationRepository` | Candidatures et suivi étudiant |
| `PromotionRepository` | Promotions et affectations |
| `SkillRepository` | Compétences requises par les offres |
| `WishlistRepository` | Gestion de la wish-list étudiante |

### Logique de recherche et filtres

La recherche d'offres utilise une construction dynamique de requête SQL :

```php
$sql = "SELECT * FROM internship_offer WHERE 1=1";
if ($keyword)  $sql .= " AND (title_internship_offer LIKE ? OR description_internship_offer LIKE ?)";
if ($location) $sql .= " AND city_company_site = ?";
if ($company)  $sql .= " AND id_company = ?";
```

Chaque filtre est optionnel et les valeurs sont injectées via des requêtes préparées PDO.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Serveur | Apache 2.4 |
| Frontend | HTML5 / CSS3 / JavaScript vanilla |
| Backend | PHP 8.2 orienté objet |
| Templates | Twig |
| Base de données | MySQL 8 |
| Tests | PHPUnit |
| Conteneurisation | Docker + Docker Compose |
| Versionning | Git |

Aucun framework frontend ou backend n'est utilisé, conformément aux contraintes du projet.

---

## Uploads et candidatures

Le projet gère le dépôt de candidatures étudiantes avec CV et lettre de motivation. Les fichiers liés aux candidatures sont référencés en base (CV, lettre de motivation). Selon la configuration du projet, les fichiers peuvent être stockés dans un dossier d'uploads dédié.

---

## Conformité technique

Le projet vise le respect des exigences suivantes :

- architecture MVC stricte,
- utilisation de Twig sur les vues,
- utilisation de PDO pour l'accès aux données,
- routage backend avec URLs lisibles,
- séparation claire entre logique métier et affichage,
- authentification par rôle,
- HTTPS,
- pagination,
- sécurité des formulaires.

---

## Tests

Les tests unitaires couvrent au minimum un contrôleur complet avec PHPUnit.

```bash
# Via le script du projet
bash scripts/units.sh
```

```bash
# Ou manuellement depuis le conteneur PHP
# Le nom exact du conteneur peut varier selon votre docker-compose.yaml
docker compose exec <php-service-name> ./vendor/bin/phpunit /var/www/html/prod/tests/
```

---

## Workflow Git

Le projet suit une organisation proche de Git Flow :

- `main` — version stable
- `develop` — branche d'intégration
- `feature/[nom]` — développement d'une fonctionnalité
- `fix/[nom]` — correction de bug

Chaque fonctionnalité passe idéalement par une Pull Request avant intégration.

---

## Sécurité

- Mots de passe hashés avec `password_hash()` et vérifiés avec `password_verify()`
- Sessions serveur + cookies `HttpOnly`, `Secure`, `SameSite=Strict`
- Requêtes préparées PDO contre les injections SQL
- Échappement automatique Twig contre les XSS
- Tokens CSRF sur les formulaires sensibles
- HTTPS activé via certificat auto-signé

---

## Licence

Projet académique — CESI Nancy.  
Non destiné à un usage en production.

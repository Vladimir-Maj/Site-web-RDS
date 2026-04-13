<div align="center">

# 🎓 StageFlow

**Plateforme web de recherche et gestion de stages**  
CESI Nancy — CPI 2ème année | 2025–2026

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)](https://docker.com)
[![Twig](https://img.shields.io/badge/Template-Twig-green)](https://twig.symfony.com)
[![License](https://img.shields.io/badge/Licence-Académique-lightgrey)](#licence)

</div>

---

## 📋 Présentation

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures étudiantes dans une interface unique, adaptée à chaque profil : **administrateur**, **pilote de promotion**, **étudiant** ou **visiteur anonyme**.

Le projet est développé sans framework backend ni frontend, conformément aux contraintes académiques, en suivant une architecture **MVC stricte**.

---

## 👥 Équipe

| Nom | Rôle Scrum |
|---|---|
| Clément BERGER | Développeur |
| Arthur CHANTRAINE | Développeur |
| Vladimir MAJCHER | Développeur |
| Turker CALISKAN | Développeur |

---

## 🚀 Démarrage rapide

### Prérequis

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) + Docker Compose v2
- [Composer](https://getcomposer.org/) (dépendances PHP locales)

> **⚠️ Ports requis :** `80` (HTTP) et `443` (HTTPS)  
> Si Apache tourne déjà sur votre machine, libérez les ports avant de lancer :
> ```bash
> sudo systemctl stop apache2 && sudo systemctl disable apache2
> ```

---

### Installation

#### 1. Cloner le dépôt

```bash
git clone git@github.com:Vladimir-Maj/Site-web-RDS.git
cd Site-web-RDS
```

#### 2. Installer les dépendances PHP

> Obligatoire — sans cette étape, PHPUnit et certaines dépendances ne seront pas disponibles.

```bash
cd www/prod
composer install
cd ../..
```

#### 3. Configurer les domaines locaux

```bash
echo "127.0.0.1 prod.stageflow.fr" | sudo tee -a /etc/hosts
echo "127.0.0.1 cdn.stageflow.fr"  | sudo tee -a /etc/hosts
```

#### 4. Construire et démarrer les conteneurs

```bash
docker compose up -d --build
```

Docker démarre automatiquement Apache, PHP et MySQL avec le schéma de base.

#### 5. Vérifier l'état des services

```bash
docker compose ps
```

---

### Accès

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | http://prod.stageflow.fr |
| Assets et médias (Vhost CDN) | http://cdn.stageflow.fr |

> L'accès se fait directement sur le port 80, sans numéro de port dans l'URL.

---

## 🗄️ Base de données

### Connexion

Configurée dans `www/prod/.back/util/db_connect.php` :

| Paramètre | Valeur |
|---|---|
| Hôte | `db` |
| Port | `3306` |
| Base | `sql_db` |
| Utilisateur | `website-local` |
| Mot de passe | `1234` |

> Le schéma est chargé automatiquement au démarrage depuis `mysql/init/01-create-tables.sql`.  
> ⚠️ Utiliser un ancien schéma cassera l'application (colonnes attendues : `id_user`, `email_user`, `id_company`, `id_internship_offer`, etc.)

### Charger le jeu de données

Une fois les conteneurs démarrés :

```bash
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Si MySQL n'est pas encore prêt, attendez quelques secondes et relancez.

### Vérifier l'import

```bash
docker exec -it lamp-db mysql -u website-local -p1234 -e \
  "USE sql_db; \
   SELECT COUNT(*) AS users_count       FROM user; \
   SELECT COUNT(*) AS offers_count      FROM internship_offer; \
   SELECT COUNT(*) AS applications_count FROM application; \
   SELECT COUNT(*) AS wishlist_count    FROM wishlist;"
```

### Réinitialiser complètement

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
# Optionnel : réimporter le seed
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

---

## 👤 Comptes de test

> Ces comptes fonctionnent **uniquement si `seed.sql` a été importé**. Le schéma seul ne crée aucun utilisateur.

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@example.com | `Admin1234!` | Administrateur |
| pilot@example.com | `Pilote1234!` | Pilote |
| pilot2@example.com | `Pilote1234!` | Pilote |
| student1@example.com | `Etudiant1234!` | Étudiant |
| student2@example.com | `EtudiantBis1234!` | Étudiant |
| test@example.com | `Test1234!` | Étudiant |

---

## 🔐 Matrice des rôles

| Action | Anonyme | Étudiant | Pilote | Admin |
|---|:---:|:---:|:---:|:---:|
| Voir les offres | ✅ | ✅ | ✅ | ✅ |
| Voir les entreprises | ✅ | ✅ | ✅ | ✅ |
| Se connecter / déconnecter | ✅ | ✅ | ✅ | ✅ |
| Postuler à une offre | ❌ | ✅ | ❌ | ❌ |
| Gérer sa wish-list | ❌ | ✅ | ❌ | ❌ |
| Gérer les offres | ❌ | ❌ | ✅ | ✅ |
| Gérer les entreprises | ❌ | ❌ | ✅ | ✅ |
| Gérer les comptes | ❌ | ❌ | ❌ | ✅ |

---

## ✅ État d'avancement

### Réalisé

- [x] Authentification / déconnexion
- [x] Catalogue des offres avec pagination
- [x] Détail d'une offre
- [x] Page d'accueil avec message de bienvenue
- [x] Suivi des candidatures (étudiant)
- [x] Wish-list étudiant (ajout / retrait)
- [x] Flash messages utilisateurs
- [x] Routage backend propre
- [x] Intégration Twig sur les vues principales

### En cours / À finaliser

- [ ] Gestion complète des entreprises
- [ ] Gestion complète des pilotes
- [ ] Gestion complète des étudiants
- [ ] Vue complète des candidatures par étudiant
- [ ] Vue candidatures élèves pour le pilote
- [ ] Statistiques avancées
- [ ] Tests unitaires complets
- [ ] Mentions légales
- [ ] `robots.txt` et `sitemap.xml`

---

## 🏗️ Architecture

```
Site-web-RDS/
├── apache/
│   ├── entrypoint.sh              # Script de démarrage Apache
│   └── vhosts.conf                # Configuration vhosts (prod + cdn)
├── deprecated/                    # Anciennes pages non utilisées
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

Le projet suit une **architecture MVC stricte**, sans framework backend ni CMS.

---

## 🧩 Couche de données — Repositories

| Repository | Responsabilités |
|---|---|
| `OfferRepository` | Recherche multicritères, pagination, détail d'une offre |
| `CompanyRepository` | Liste, filtrage et détails des entreprises |
| `UserRepository` | Authentification, gestion des profils |
| `ApplicationRepository` | Candidatures et suivi étudiant |
| `PromotionRepository` | Promotions et affectations |
| `SkillRepository` | Compétences requises par les offres |
| `WishlistRepository` | Gestion de la wish-list étudiante |

### Logique de recherche dynamique

```php
$sql = "SELECT * FROM internship_offer WHERE 1=1";
if ($keyword)  $sql .= " AND (title_internship_offer LIKE ? OR description_internship_offer LIKE ?)";
if ($location) $sql .= " AND city_company_site = ?";
if ($company)  $sql .= " AND id_company = ?";
```

Chaque filtre est optionnel. Les valeurs sont injectées via des **requêtes préparées PDO**.

---

## 🛠️ Stack technique

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

> Aucun framework frontend ou backend n'est utilisé, conformément aux contraintes du projet.

---

## 🧪 Tests

```bash
# Via le script du projet
bash scripts/units.sh

# Ou manuellement depuis le conteneur PHP
docker compose exec <php-service-name> ./vendor/bin/phpunit /var/www/html/prod/tests/
```

> Le nom exact du service PHP est défini dans `docker-compose.yaml`.

---

## 🔀 Workflow Git

Le projet suit une organisation proche de **Git Flow** :

| Branche | Usage |
|---|---|
| `main` | Version stable |
| `develop` | Branche d'intégration |
| `feature/[nom]` | Développement d'une fonctionnalité |
| `fix/[nom]` | Correction de bug |

Chaque fonctionnalité passe idéalement par une **Pull Request** avant intégration dans `develop`.

---

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` et vérifiés avec `password_verify()`
- Sessions serveur avec cookies `HttpOnly`, `Secure` et `SameSite=Strict`
- Requêtes préparées PDO (protection contre les injections SQL)
- Échappement automatique Twig (protection contre les XSS)
- Tokens CSRF sur les formulaires sensibles
- HTTPS activé via certificat auto-signé

---

## 🧰 Scripts utilitaires

```bash
bash scripts/chmod_all_scripts.sh   # Donne les droits d'exécution à tous les scripts
bash scripts/clean-tree.sh          # Nettoie l'arborescence
bash scripts/db-logs.sh             # Affiche les logs MySQL
bash scripts/server-logs.sh         # Affiche les logs Apache
bash scripts/units.sh               # Lance les tests unitaires
bash scripts/wipe_volumes_clean.sh  # Réinitialise les volumes Docker (BDD comprise)
```

---

## 🐳 Services Docker

| Service | Rôle |
|---|---|
| `web` | Exécution PHP + serveur Apache |
| `db` | Base de données MySQL |
| `cdn` | Distribution des assets statiques |

> Le nom exact des services peut être vérifié dans `docker-compose.yaml`.

---

## 📄 Licence

Projet académique — **CESI Nancy**  
Non destiné à un usage en production.

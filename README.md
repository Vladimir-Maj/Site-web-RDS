<div align="center">

# ? StageFlow

**Plateforme web de recherche et gestion de stages**  
CESI Nancy ? CPI 2�me ann�e | 2025?2026

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)](https://docker.com)
[![Twig](https://img.shields.io/badge/Template-Twig-green)](https://twig.symfony.com)
[![License](https://img.shields.io/badge/Licence-Acad�mique-lightgrey)](#licence)

</div>

---

## ? Pr�sentation

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures �tudiantes dans une interface unique, adapt�e � chaque profil : **administrateur**, **pilote de promotion**, **�tudiant** ou **visiteur anonyme**.

Le projet est d�velopp� sans framework backend ni frontend, conform�ment aux contraintes acad�miques, en suivant une architecture **MVC stricte**.

---

## ? �quipe

| Nom | R�le |
|---|---|
| Cl�ment BERGER | D�veloppeur |
| Arthur CHANTRAINE | D�veloppeur |
| Vladimir MAJCHER | D�veloppeur |
| Turker CALISKAN | D�veloppeur |

---

## ?? Installation de l'environnement

> Cette section couvre l'installation compl�te sur **Linux Mint / Ubuntu** depuis un syst�me vierge.  
> Si Git, Docker et Composer sont d�j� install�s, passez directement au [D�marrage rapide](#-d�marrage-rapide).

### 1. Mettre le syst�me � jour

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Installer Git et les d�pendances

```bash
sudo apt install -y git ca-certificates curl gnupg lsb-release software-properties-common unzip php-cli
git --version
```

### 3. Installer Docker + Docker Compose

**Ajouter la cl� et le d�p�t Docker :**

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo ${UBUNTU_CODENAME:-$VERSION_CODENAME}) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

**Installer Docker :**

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
docker --version
docker compose version
```

**Autoriser Docker sans `sudo` :**

```bash
sudo usermod -aG docker $USER
newgrp docker
docker run hello-world
```

### 4. Installer les extensions PHP requises

```bash
sudo apt update
sudo apt install -y php-xml
php -m | grep -i dom
```

> L'extension `dom` doit appara�tre dans la liste ? elle est requise par PHPUnit.

### 5. Installer Composer

```bash
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
rm composer-setup.php
composer --version
```

---

## ? D�marrage rapide

### Pr�requis

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) + Docker Compose v2
- [Composer](https://getcomposer.org/) (d�pendances PHP locales)

> **?? Ports requis :** `80` (HTTP) et `443` (HTTPS)  
> Si Apache tourne d�j� sur votre machine, lib�rez les ports avant de lancer :
> ```bash
> sudo systemctl stop apache2 && sudo systemctl disable apache2
> ```

---

### Installation

#### 1. Cloner le d�p�t

```bash
git clone git@github.com:Vladimir-Maj/Site-web-RDS.git
cd Site-web-RDS
```

#### 2. Installer les d�pendances PHP

> Obligatoire ? sans cette �tape, PHPUnit et certaines d�pendances ne seront pas disponibles.

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

#### 4. Construire et d�marrer les conteneurs

```bash
docker compose up -d --build
```

Docker d�marre automatiquement Apache, PHP et MySQL avec le sch�ma de base.  
**Les donn�es de d�monstration ne sont pas charg�es automatiquement** : il faut importer `seed.sql` apr�s le d�marrage.

#### 5. V�rifier l'�tat des services

```bash
docker compose ps
docker compose logs --tail=50 web
docker compose logs --tail=50 db
```

---

### Acc�s

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | http://prod.stageflow.fr |
| Assets et m�dias (Vhost CDN) | http://cdn.stageflow.fr |

> L'acc�s se fait directement sur le port 80, sans num�ro de port dans l'URL.

---

## ?? Base de donn�es

### Connexion

Configur�e dans `www/prod/.back/util/db_connect.php` :

| Param�tre | Valeur |
|---|---|
| H�te | `db` |
| Port | `3306` |
| Base | `sql_db` |
| Utilisateur | `website-local` |
| Mot de passe | `1234` |

> Le sch�ma est charg� automatiquement au d�marrage depuis `mysql/init/01-create-tables.sql`.  
> ?? Utiliser un ancien sch�ma cassera l'application (colonnes attendues : `id_user`, `email_user`, `id_company`, `id_internship_offer`, etc.)

### Charger le jeu de donn�es

Une fois les conteneurs d�marr�s :

```bash
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Si MySQL n'est pas encore pr�t, attendez quelques secondes et relancez.

### V�rifier l'import

```bash
docker exec -it lamp-db mysql -u website-local -p1234 -e \
  "USE sql_db; \
   SELECT COUNT(*) AS users_count        FROM user; \
   SELECT COUNT(*) AS offers_count       FROM internship_offer; \
   SELECT COUNT(*) AS applications_count FROM application; \
   SELECT COUNT(*) AS wishlist_count     FROM wishlist;"
```

### R�initialiser compl�tement

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Apr�s une r�initialisation compl�te, il faut **r�importer `seed.sql`** pour restaurer les comptes et les donn�es de d�monstration.

---

## ? Comptes de test

> Ces comptes fonctionnent **uniquement si `seed.sql` a �t� import�**. Le sch�ma seul ne cr�e aucun utilisateur.

| Email | Mot de passe | R�le |
|---|---|---|
| admin@example.com | `Admin1234!` | Administrateur |
| pilot@example.com | `Pilote1234!` | Pilote |
| pilot2@example.com | `Pilote1234!` | Pilote |
| student1@example.com | `Etudiant1234!` | �tudiant |
| student2@example.com | `EtudiantBis1234!` | �tudiant |
| test@example.com | `Test1234!` | �tudiant |

---

## ? Matrice des r�les

| Action | Anonyme | �tudiant | Pilote | Admin |
|---|:---:|:---:|:---:|:---:|
| Voir les offres | ? | ? | ? | ? |
| Voir les entreprises | ? | ? | ? | ? |
| Se connecter / d�connecter | ? | ? | ? | ? |
| Postuler � une offre | ? | ? | ? | ? |
| G�rer sa wish-list | ? | ? | ? | ? |
| G�rer les offres | ? | ? | ? | ? |
| G�rer les entreprises | ? | ? | ? | ? |
| G�rer les comptes | ? | ? | ? | ? |

---

## ? �tat d'avancement

### R�alis�

- [x] Authentification / d�connexion
- [x] Catalogue des offres avec pagination
- [x] D�tail d'une offre
- [x] Page d'accueil avec message de bienvenue
- [x] Suivi des candidatures (�tudiant)
- [x] Wish-list �tudiant (ajout / retrait)
- [x] Flash messages utilisateurs
- [x] Routage backend propre
- [x] Int�gration Twig sur les vues principales

### En cours / � finaliser

- [ ] Gestion compl�te des entreprises
- [ ] Gestion compl�te des pilotes
- [ ] Gestion compl�te des �tudiants
- [ ] Vue compl�te des candidatures par �tudiant
- [ ] Vue candidatures �l�ves pour le pilote
- [ ] Statistiques avanc�es
- [ ] Tests unitaires complets
- [ ] Mentions l�gales
- [ ] `robots.txt` et `sitemap.xml`

---

## ?? Architecture

```text
Site-web-RDS/
??? apache/
?   ??? entrypoint.sh              # Script de d�marrage Apache
?   ??? vhosts.conf                # Configuration des vhosts (prod + cdn)
??? mysql/
?   ??? init/                      # Scripts d'initialisation de la base
?   ??? migrations/                # Scripts de migration si utilis�s
?   ??? scripts/                   # Utilitaires SQL / maintenance
??? scripts/
?   ??? chmod_all_scripts.sh
?   ??? clean-tree.sh
?   ??? db-logs.sh
?   ??? server-logs.sh
?   ??? units.sh
?   ??? wipe_volumes_clean.sh
??? www/
?   ??? cdn/
?   ?   ??? assets/
?   ?   ??? styles.css
?   ??? prod/
?       ??? .back/
?       ?   ??? controllers/
?       ?   ??? models/
?       ?   ??? repository/
?       ?   ??? templates/
?       ?   ??? util/
?       ??? tests/
?       ??? .htaccess
?       ??? composer.json
?       ??? composer.lock
?       ??? index.php
??? .gitignore
??? CLAUDE.md
??? Dockerfile
??? docker-compose.yaml
??? README.md
??? seed.sql
```

> Cette arborescence refl�te la structure principale du d�p�t. Elle doit rester coh�rente avec l'�tat r�el du projet.

Le projet suit une **architecture MVC stricte**, sans framework backend ni CMS.

---

## ? Couche de donn�es ? Repositories

| Repository | Responsabilit�s |
|---|---|
| `OfferRepository` | Recherche multicrit�res, pagination, d�tail d'une offre |
| `CompanyRepository` | Liste, filtrage et d�tails des entreprises |
| `UserRepository` | Authentification, gestion des profils |
| `ApplicationRepository` | Candidatures et suivi �tudiant |
| `PromotionRepository` | Promotions et affectations |
| `SkillRepository` | Comp�tences requises par les offres |
| `WishlistRepository` | Gestion de la wish-list �tudiante |

### Logique de recherche dynamique

```php
$sql = "SELECT * FROM internship_offer WHERE 1=1";
if ($keyword)  $sql .= " AND (title_internship_offer LIKE ? OR description_internship_offer LIKE ?)";
if ($location) $sql .= " AND city_company_site = ?";
if ($company)  $sql .= " AND id_company = ?";
```

Chaque filtre est optionnel. Les valeurs sont inject�es via des **requ�tes pr�par�es PDO**.

---

## ?? Stack technique

| Couche | Technologie |
|---|---|
| Serveur | Apache 2.4 |
| Frontend | HTML5 / CSS3 / JavaScript vanilla |
| Backend | PHP 8.2 orient� objet |
| Templates | Twig |
| Base de donn�es | MySQL 8 |
| Tests | PHPUnit |
| Conteneurisation | Docker + Docker Compose |
| Versionning | Git |

> Aucun framework frontend ou backend n'est utilis�, conform�ment aux contraintes du projet.

---

## ? Tests

```bash
# Via le script du projet
bash scripts/units.sh

# Ou directement avec le service de tests
docker compose run --rm phpunit
```

> Le service de test d�clar� dans `docker-compose.yaml` s'appelle `phpunit` et lance PHPUnit avec l'entrypoint configur�. ?filecite?turn17file0?turn17file6?

---

## ? Workflow Git

Le projet suit une organisation proche de **Git Flow** :

| Branche | Usage |
|---|---|
| `main` | Version stable |
| `develop` | Branche d'int�gration |
| `feature/[nom]` | D�veloppement d'une fonctionnalit� |
| `fix/[nom]` | Correction de bug |

Chaque fonctionnalit� passe id�alement par une **Pull Request** avant int�gration dans `develop`.

---

## ? S�curit�

- Mots de passe hash�s avec `password_hash()` et v�rifi�s avec `password_verify()`
- Sessions serveur avec cookies `HttpOnly`, `Secure` et `SameSite=Strict`
- Requ�tes pr�par�es PDO (protection contre les injections SQL)
- �chappement automatique Twig (protection contre les XSS)
- Tokens CSRF sur les formulaires sensibles
- HTTPS activ� via certificat auto-sign�

---

## ? Scripts utilitaires

```bash
bash scripts/chmod_all_scripts.sh   # Donne les droits d'ex�cution � tous les scripts
bash scripts/clean-tree.sh          # Nettoie l'arborescence
bash scripts/db-logs.sh             # Affiche les logs MySQL
bash scripts/server-logs.sh         # Affiche les logs Apache
bash scripts/units.sh               # Lance les tests unitaires
bash scripts/wipe_volumes_clean.sh  # R�initialise les volumes Docker (BDD comprise)
```

---

## ? Services Docker

| Service | R�le |
|---|---|
| `web` | Serveur Apache + ex�cution PHP pour l'application et le vhost CDN |
| `db` | Base de donn�es MySQL |
| `phpunit` | Service d�di� � l'ex�cution des tests unitaires |

> Le `docker-compose.yaml` d�clare `db`, `web` et `phpunit`. Le domaine `cdn.stageflow.fr` est servi par **Apache via un vhost d�di�**, pas par un service Docker s�par�. ?filecite?turn17file0?turn17file17?

---

## ? Licence

Projet acad�mique ? **CESI Nancy**  
Non destin� � un usage en production.

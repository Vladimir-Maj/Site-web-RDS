# Contenu juridique ? StageFlow

*Document source pour la page l�gale combin�e (`/legals`)*

---

## Notice importante

Ce document correspond au **contexte r�el du projet StageFlow** : une plateforme acad�mique r�alis�e dans le cadre du bloc D�veloppement Web du **CESI Nancy**.  
Il ne s?agit pas d?une soci�t� commerciale exploit�e en production.

En cons�quence :

- certaines mentions obligatoires d?un site professionnel classique sont **non applicables** ;
- les parties li�es au SIRET, au capital social ou � une exploitation commerciale r�elle doivent �tre adapt�es en cons�quence ;
- si le projet devait �tre publi� hors du cadre p�dagogique, une **relecture juridique** resterait n�cessaire.

---

## 1. Mentions l�gales

### 1.1 �diteur du site

**Nom du projet :** StageFlow  
**Nature :** Projet acad�mique non commercial  
**Cadre :** Bloc D�veloppement Web ? CESI Nancy  
**Ann�e universitaire :** 2025?2026

**�quipe projet :**
- Cl�ment Berger
- Arthur Chantraine
- Vladimir Majcher
- Turker Caliskan

**Forme juridique :** Projet p�dagogique non commercial  
**SIRET :** Non applicable  
**SIREN :** Non applicable  
**Capital social :** Non applicable

**Rattachement p�dagogique :**  
CESI Nancy  
6 rue du Bois du Ch�ne le Loup  
54500 Vand?uvre-l�s-Nancy  
France

### 1.2 Responsable de publication

La publication du contenu est assur�e dans le cadre du projet p�dagogique StageFlow et de sa soutenance.  
Le contenu est produit par l?�quipe projet dans le cadre de l?enseignement re�u au CESI Nancy.

### 1.3 H�bergement

**Type d?h�bergement :** environnement de d�monstration local  
**Technologies :** Docker, Apache, PHP, MySQL  
**Domaines utilis�s :**
- `prod.stageflow.fr`
- `cdn.stageflow.fr`

**Exploitant de l?environnement :** �quipe projet StageFlow dans un cadre acad�mique

### 1.4 Conception et d�veloppement

**Projet :** StageFlow  
**Conception et d�veloppement :** �quipe projet StageFlow  
**Contexte :** application web MVC de gestion de stages

### 1.5 Propri�t� intellectuelle

Les maquettes, d�veloppements, structures de donn�es, interfaces, textes et �l�ments graphiques r�alis�s pour StageFlow sont produits dans le cadre d?un projet acad�mique CESI.

Toute reproduction, diffusion ou r�utilisation hors de ce cadre doit faire l?objet d?une autorisation pr�alable des auteurs ou de l?encadrement p�dagogique concern�.

**Exceptions :**
- les documents d�pos�s par les utilisateurs (CV, lettres de motivation) restent leur propri�t� ;
- les offres de stage saisies dans l?application restent li�es � leur auteur ou � leur contexte de d�monstration ;
- les �valuations et commentaires �ventuels rel�vent du fonctionnement p�dagogique du projet.

### 1.6 Conformit� aux lois

Le projet vise � respecter les principes g�n�raux applicables en mati�re de s�curit�, de confidentialit� et de protection des donn�es, notamment :
- RGPD
- principes CNIL
- bonnes pratiques de s�curit� web
- bonnes pratiques de d�veloppement acad�mique

---

## 2. Politique de confidentialit�

### 2.1 Donn�es collect�es et finalit�s

#### 2.1.1 �tudiants

| Donn�e | Collect�e | Finalit� | Base |
|--------|-----------|----------|------|
| Pr�nom, nom | Oui | Identification utilisateur | Fonctionnement du service |
| Adresse email | Oui | Connexion et communication | Fonctionnement du service |
| Mot de passe hach� | Oui | Authentification s�curis�e | Fonctionnement du service |
| R�le | Oui | Gestion des permissions | Fonctionnement du service |
| Campus / promotion | Oui | Suivi p�dagogique | Fonctionnement du service |
| CV | Oui (optionnel) | Candidature | Fonctionnement du service |
| Lettre de motivation | Oui (optionnel) | Candidature | Fonctionnement du service |
| Wish-list | Oui | Suivi des offres | Fonctionnement du service |
| Historique candidatures | Oui | Suivi des demandes | Fonctionnement du service |

#### 2.1.2 Pilotes

| Donn�e | Collect�e | Finalit� | Base |
|--------|-----------|----------|------|
| Pr�nom, nom | Oui | Identification | Fonctionnement du service |
| Adresse email | Oui | Connexion et gestion | Fonctionnement du service |
| Mot de passe hach� | Oui | Authentification | Fonctionnement du service |
| Affectations / suivi | Oui | Gestion des �tudiants | Fonctionnement du service |
| �valuations �ventuelles | Oui | Suivi qualit� | Int�r�t p�dagogique |

#### 2.1.3 Administrateurs

| Donn�e | Collect�e | Finalit� | Base |
|--------|-----------|----------|------|
| Identit� du compte | Oui | Administration | Fonctionnement du service |
| Authentification | Oui | S�curit� | Fonctionnement du service |
| Actions d?administration | Partiellement | Audit et coh�rence | Int�r�t l�gitime |

#### 2.1.4 Entreprises

| Donn�e | Collect�e | Finalit� | Base |
|--------|-----------|----------|------|
| Nom | Oui | R�f�rencement | Fonctionnement du service |
| Email / t�l�phone | Oui | Contact | Fonctionnement du service |
| Secteur / localisation | Oui | Recherche et affichage | Fonctionnement du service |
| Offres associ�es | Oui | Gestion des stages | Fonctionnement du service |

### 2.2 Partage des donn�es

Les donn�es ne sont ni vendues, ni lou�es, ni c�d�es � des tiers � des fins publicitaires.

L?acc�s est limit� :
- � l?utilisateur concern� pour ses propres donn�es ;
- aux pilotes selon leurs permissions ;
- aux administrateurs selon leur r�le ;
- au contexte technique strictement n�cessaire au fonctionnement du projet.

### 2.3 Mesures de s�curit�

StageFlow met en ?uvre les mesures suivantes :

- mots de passe stock�s sous forme hach�e ;
- sessions avec cookies techniques s�curis�s ;
- requ�tes pr�par�es PDO ;
- protection contre les injections SQL ;
- protections contre les attaques XSS et CSRF ;
- validation des fichiers d�pos�s ;
- HTTPS pr�vu dans l?environnement web de d�monstration.

### 2.4 Dur�e de conservation

| Type de donn�e | Dur�e |
|----------------|-------|
| Comptes actifs | Jusqu?� suppression ou d�sactivation |
| CV / lettres | Jusqu?� suppression des donn�es associ�es |
| Candidatures | Dur�e utile au fonctionnement du projet |
| Logs techniques | Dur�e limit�e de diagnostic |
| Sauvegardes | Selon les besoins techniques de l?environnement |

### 2.5 Droits des utilisateurs

Les utilisateurs disposent, dans les limites du projet et des fonctionnalit�s disponibles, des droits suivants :

- droit d?acc�s ;
- droit de rectification ;
- droit � l?effacement ;
- droit � la portabilit� ;
- droit d?opposition.

Ces droits peuvent �tre exerc�s par les fonctionnalit�s pr�vues dans l?application lorsqu?elles existent, ou dans le cadre du projet p�dagogique.

### 2.6 Cookies

StageFlow utilise uniquement des **cookies techniques de session** n�cessaires :
- � l?authentification ;
- � la s�curit� ;
- au maintien de session.

Aucun cookie publicitaire ou traceur tiers n?est utilis� dans le fonctionnement normal du projet.

---

## 3. Conditions d?utilisation

### 3.1 Acceptation

L?utilisation de la plateforme implique l?acceptation de ses r�gles de fonctionnement dans le cadre p�dagogique du projet.

### 3.2 Utilisation autoris�e

L?utilisateur s?engage � ne pas :
- usurper l?identit� d?un autre utilisateur ;
- tenter d?acc�der � des zones non autoris�es ;
- d�poser du contenu malveillant ;
- d�tourner la plateforme de son objet ;
- porter atteinte aux autres utilisateurs ou � leurs donn�es.

### 3.3 Compte personnel

Chaque utilisateur est responsable :
- de la confidentialit� de ses identifiants ;
- des actions r�alis�es sous son compte ;
- des documents qu?il d�pose dans l?application.

### 3.4 Limitation de responsabilit�

La plateforme est fournie dans un cadre de d�monstration p�dagogique.

Aucune garantie absolue ne peut �tre donn�e concernant :
- la disponibilit� permanente ;
- l?absence totale d?erreur ;
- l?absence totale d?interruption ;
- l?exhaustivit� des donn�es de d�monstration.

### 3.5 �volution

Le contenu, les fonctionnalit�s et les pr�sentes mentions peuvent �tre modifi�s selon l?avancement du projet p�dagogique.

---

## 4. Proc�dures RGPD

### 4.1 Demande d?acc�s

Lorsqu?elle est disponible, la fonctionnalit� d?export permet de r�cup�rer les donn�es personnelles dans un format exploitable.

### 4.2 Demande de suppression

Lorsqu?elle est disponible, la suppression de compte entra�ne l?effacement des donn�es associ�es, sous r�serve :
- des contraintes techniques �ventuelles ;
- des sauvegardes temporaires ;
- des traces minimales n�cessaires � la s�curit�.

### 4.3 Recours

Si un utilisateur estime que ses droits ne sont pas respect�s, il peut se rapprocher de l?encadrement p�dagogique ou de l?autorit� comp�tente.

**CNIL**  
3 Place de Fontenoy  
75007 Paris ? France  
Site : https://www.cnil.fr

---

## 5. Contact

Pour toute question relative au projet, aux donn�es ou � l?usage de la plateforme :

**Projet :** StageFlow  
**Cadre :** CESI Nancy ? D�veloppement Web  
**�quipe :** Cl�ment Berger, Arthur Chantraine, Vladimir Majcher, Turker Caliskan

---

## Version

**Version :** 1.0  
**Derni�re mise � jour :** 14 avril 2026  
**Contexte :** projet p�dagogique StageFlow

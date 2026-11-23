# 🏛️ Grand Washington Hotel & Spa — Réservation hôtelière (Symfony 7.4)

Projet du module **Développement d'applications sécurisées** — thème **Hôtel** (équipe de 2).

**Étudiante 1 :** Erij Yacoubi — classe **ING-4-SSIR-B**

Application Symfony permettant aux clients de **rechercher, réserver et gérer des séjours** dans un hôtel 5★ à Hammamet, et aux administrateurs de **gérer tout le site** depuis un back-office avec statistiques.

---

## ✅ Couverture du cahier des charges

| Exigence | Implémentation |
|---|---|
| **Équipe de 2 → 4 entités min. + CRUD** (hors User) | **6 entités** avec CRUD complet : `Room`, `Category`, `Amenity`, `HotelService`, `Reservation`, `Review` (+ `User`) |
| Admin — gérer les produits/services **(upload obligatoire)** | Chambres (photo obligatoire) et Services/Spa (image) — upload sécurisé |
| Admin — gérer les réservations | Liste filtrable, changement de statut rapide, création/édition, export CSV |
| Admin — gérer les clients | Recherche, fiche client, création/édition, suspension de compte, suppression |
| Admin — statistiques | KPI (CA, occupation, panier moyen…) + 5 graphiques Chart.js |
| Client — inscription / connexion / compte | Inscription, connexion (+ « se souvenir de moi »), profil, changement de mot de passe |
| Client — liste de ses réservations | Historique filtrable par statut, détail, annulation |
| Client — recherche multicritère | Dates (**disponibilité réelle**), voyageurs, catégorie, budget, équipements, mot-clé, tri |
| Client — panier | Panier de séjours en session → validation en réservations |
| Git obligatoire | Dépôt Git initialisé (voir « Travail en équipe ») |
| Recommandations : XSS / SQLi / CSRF, prod, responsive | Voir section Sécurité ; mode `prod` ; Bootstrap 5 responsive |

## 🗂️ Modèle de données

```
User 1──* Reservation *──1 Room *──1 Category
  │            │             │
  │            *             *──* Amenity
  │       HotelService       │
  └──1──* Review *──1────────┘
```

- **Room** : nom, slug, description, prix/nuit, capacité, surface, literie, photo, en ligne, mise en avant
- **Reservation** : référence unique `GW-XXXXXXXX`, dates, voyageurs, services, total recalculé, statut (en attente / confirmée / terminée / annulée)
- **Review** : note 1–5 + commentaire, modéré par l'admin, réservé aux clients ayant séjourné

## 🔐 Sécurité (point clé du module)

| Menace | Protection |
|---|---|
| **XSS** | Échappement automatique Twig (aucun `|raw` sur des données utilisateur) ; **Content-Security-Policy** stricte en prod (aucun script inline : tout le JS est dans `public/js`) ; JSON des graphiques encodé avec `JSON_HEX_TAG` |
| **Injection SQL** | Doctrine ORM + QueryBuilder avec **paramètres liés** partout ; `LIKE` échappé (`%`, `_`) ; tri limité à une liste blanche (`SortParameterSubscriber`) |
| **CSRF** | Jeton sur tous les formulaires Symfony, le login, le logout, et chaque action POST (suppression, statut, panier, annulation…) |
| **IDOR** | `ReservationVoter` : un client ne voit / n'annule que **ses** réservations (403 sinon) |
| Contrôle d'accès | `access_control` sur `/admin` et `/mon-compte` + `#[IsGranted]` sur les contrôleurs |
| Brute-force | `login_throttling` : 5 tentatives / minute |
| Mots de passe | Hachage `auto` (bcrypt/argon2id), politique (8 car., maj., min., chiffre), jamais stockés en session |
| Upload | Contrainte `Image` (MIME réel, 3 Mo), extension déduite du contenu, nom aléatoire + slug (anti path-traversal) |
| Manipulation des prix | Le panier ne stocke que des IDs/dates : **prix et disponibilité recalculés côté serveur** dans une transaction |
| Comptes | Suspension par l'admin (`UserChecker`), rôle jamais choisi par l'utilisateur |
| En-têtes HTTP | `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` |
| Export CSV | Neutralisation de l'injection de formules Excel |

## 🚀 Installation

Prérequis : **PHP 8.2+** (extensions `pdo_sqlite`, `intl`, `gd`, `fileinfo`) et **Composer**.

```bash
composer install
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n      # données de démo + images
php -S 127.0.0.1:8000 -t public                # ou : symfony serve
```

Ouvrir http://127.0.0.1:8000

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@grandwashington.tn` | `Admin123!` |
| Client | `client@grandwashington.tn` | `Client123!` |

### Mode production (pour la démo)

Dans `.env.local` :

```
APP_ENV=prod
APP_DEBUG=0
```

puis `php bin/console cache:clear`. (Après chaque modification du code en prod, refaire `cache:clear`.)

> Les fixtures sont un outil de développement : en mode prod, les recharger avec
> `php bin/console doctrine:fixtures:load -n --env=dev`.

### MySQL (XAMPP / WAMP) au lieu de SQLite

Dans `.env.local` :

```
DATABASE_URL="mysql://root:@127.0.0.1:3306/grand_washington?serverVersion=8.0.32&charset=utf8mb4"
```

puis `php bin/console doctrine:database:create`, `doctrine:migrations:migrate` et `doctrine:fixtures:load`.

## 🧭 Parcours de démonstration conseillé

1. **Accueil** → recherche avec dates → liste filtrée par disponibilité
2. **Fiche chambre** → choisir des services → estimation du prix en direct → *Ajouter au panier*
3. **Panier** → se connecter (client) → *Confirmer la réservation*
4. **Mon compte** → réservation « En attente » → détail → annulation possible
5. **Admin** (`/admin`) → tableau de bord → passer la réservation en « Confirmée »
6. **Admin → Chambres** → créer une chambre avec photo (upload)
7. **Sécurité** : tenter `/mon-compte/reservations/<référence d'un autre client>` → **403** ; saisir `<script>alert(1)</script>` dans un avis → affiché comme texte

## 👥 Travail en équipe (Git)

Chaque membre committe avec **son propre compte Git** :

```bash
git config user.name "Prénom Nom"
git config user.email "email@exemple.com"
```

Répartition proposée pour la soutenance :

| Membre | Partie présentée |
|---|---|
| Erij Yacoubi | **Site client** : recherche, fiche chambre, panier, compte client, inscription/connexion, sécurité (CSRF, Voter, CSP) |
| Membre 2 | **Back-office** : CRUD chambres/catégories/équipements/services, upload, réservations, clients, avis, statistiques |

## 📁 Structure

```
src/
  Controller/          Site client (Home, Room, Cart, Account, Security)
  Controller/Admin/    Back-office (Dashboard + CRUD)
  Entity/              7 entités Doctrine
  Form/                Formulaires (+ Form/Admin)
  Repository/          Requêtes (QueryBuilder paramétré)
  Security/            UserChecker + ReservationVoter
  Service/             Cart (panier), FileUploader, StatsProvider
  EventSubscriber/     En-têtes de sécurité, liste blanche de tri
templates/             Twig (site, admin, erreurs)
public/css, public/js  Styles et scripts (sans script inline)
```

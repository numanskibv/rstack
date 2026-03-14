# RStack

Self-hosted deployment platform voor Docker applicaties, vergelijkbaar met Laravel Forge of Coolify.
Beheer je eigen servers, definieer stack-templates en deploy applicaties via Docker Compose.

Gebouwd op Laravel 12, Livewire 4 (Volt) en Flux UI.

---

## Vereisten

- PHP 8.3+
- Composer
- Node.js 20+ en NPM
- SQLite (development) of MySQL (productie)
- Een of meerdere Linux-servers met Docker en Docker Compose geïnstalleerd

---

## Installatie

```bash
# 1. Installeer PHP- en JS-afhankelijkheden
composer install
npm install && npm run build

# 2. Omgevingsbestand aanmaken
cp .env.example .env
php artisan key:generate

# 3. Database aanmaken en vullen
php artisan migrate --seed
```

De seeder maakt een testgebruiker (admin) en de drie standaard stack-templates aan.

---

## Beveiliging

### Registratie op uitnodiging (domeinfilter)

Registratie is open, maar alleen voor e-mailadressen van **toegestane domeinen**.
Een admin beheert de lijst via **Admin → Toegestane domeinen**.

Voorbeeld: voeg `mbouutrecht.nl` toe en iedereen met een `@mbouutrecht.nl` adres kan zich registreren.
Onbekende domeinen worden geweigerd.

### Two-factor authenticatie (2FA) verplicht

Om servers en projecten toe te voegen moet een gebruiker **2FA ingeschakeld hebben**.
Zonder 2FA wordt de gebruiker automatisch doorgestuurd naar de beveiligingsinstellingen.

2FA instellen: **Instellingen → Beveiliging → Two-factor authenticatie**

### Standaard testgebruiker (seeder)

| E-mail           | Wachtwoord | Rol   |
|------------------|------------|-------|
| test@example.com | password   | Admin |

### Handmatig gebruiker aanmaken

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name'     => 'Naam',
    'email'    => 'email@example.com',
    'password' => bcrypt('wachtwoord'),
    'is_admin' => false, // true voor beheerderstoegang
]);
```

### Admin-rechten toekennen aan bestaande gebruiker

Via Tinker:

```bash
php artisan tinker
```

```php
App\Models\User::where('email', 'jouw@email.com')->update(['is_admin' => true]);
```

Of via de interface: **Admin → Gebruikers → Admin maken**

---

## Admin-paneel

Het admin-paneel is zichtbaar in de sidebar voor gebruikers met `is_admin = true`.

### Gebruikers beheren (Admin → Gebruikers)

Overzicht van alle gebruikers met per gebruiker:

| Kolom | Inhoud |
|-------|--------|
| Naam + e-mail | Identiteit en initialen-avatar |
| 2FA | Aan / Uit status |
| Rol | Admin of Gebruiker |
| Servers | Aantal servers in beheer |
| Projects | Totaal projecten |
| Running | Actief draaiende projecten |
| Failed | Gefaalde projecten |
| Lid sinds | Relatieve tijd |
| Actie | Admin-rechten toekennen of intrekken |

### Toegestane domeinen (Admin → Toegestane domeinen)

Beheer welke e-maildomeinen zich mogen registreren.
Voeg een domein toe met een optionele omschrijving. Verwijderen kan direct vanuit de lijst.

---

## SSH-configuratie

RStack verbindt via SSH met Docker-servers om deployments uit te voeren.
Stel de volgende waarden in via `.env`:

```env
RSTACK_SSH_KEY_PATH=/home/user/.ssh/id_rsa
RSTACK_REMOTE_PROJECT_ROOT=/srv/rstack/projects
RSTACK_SSH_TIMEOUT=120
```

| Variabele | Omschrijving | Standaard |
|-----------|-------------|-----------|
| `RSTACK_SSH_KEY_PATH` | Pad naar de privésleutel | `storage/app/ssh/id_rsa` |
| `RSTACK_REMOTE_PROJECT_ROOT` | Basismap voor projectbestanden op de server | `/srv/rstack/projects` |
| `RSTACK_SSH_TIMEOUT` | SSH-time-out in seconden | `120` |

De bijbehorende **publieke sleutel** moet aanwezig zijn in `~/.ssh/authorized_keys` op elke beheerde server.

---

## Servers toevoegen

1. Activeer 2FA (vereist)
2. Ga naar **Servers → Server toevoegen**
3. Vul naam, IP-adres, SSH-gebruiker en poort in
4. Zorg dat de SSH-sleutel van RStack toegang heeft tot de server

---

## Stack-templates

Templates staan in `storage/stacks/{naam}/`. Elke template kan de volgende bestanden bevatten:

```
Dockerfile
docker-compose.yml
nginx.conf          (optioneel)
.env.template       (variabelen die automatisch worden ingevuld)
```

### Meegeleverde templates

| Stack     | Runtime                      | Omschrijving       |
|-----------|------------------------------|--------------------|
| `laravel` | PHP 8.3-fpm + Nginx + MySQL  | Laravel applicatie |
| `node`    | Node 20                      | Node.js applicatie |
| `static`  | Nginx Alpine                 | Statische website  |

### Eigen template toevoegen

1. Maak een map `storage/stacks/{naam}/`
2. Voeg `Dockerfile`, `docker-compose.yml` en `.env.template` toe
3. Voeg de stack toe via de database of seeder
4. De template is direct beschikbaar bij het aanmaken van een project

---

## Projecten aanmaken

1. Activeer 2FA (vereist)
2. Ga naar **Projects → Project toevoegen**
3. Kies een server en een stack
4. Vul naam, domein en omgevingsvariabelen in
5. RStack maakt automatisch:
   - Een unieke slug en poortnummer (startend bij 8001)
   - Een projectmap lokaal (`storage/app/projects/{slug}/`)
   - Een kopie van de stack-template
   - Een `.env` bestand met platform- en gebruikersvariabelen

---

## Deployment

Een deployment wordt uitgevoerd via `DeploymentService::deploy()`:

1. Project moet status `ready` hebben
2. Er wordt een deployment-record aangemaakt met status `running`
3. RStack verbindt via SSH met de server
4. In de projectmap (`/srv/rstack/projects/{slug}`) wordt `docker compose up -d` uitgevoerd
5. De output wordt opgeslagen als deployment-log
6. Bij succes: status `deployed` + timestamp, project wordt `running`
7. Bij fout: status `failed` + foutmelding in log, project wordt `failed`

---

## Architectuur

```
Gebruiker → RStack Panel (Livewire Volt)
               |
           Services
               |
        +------+------+
        |             |
   Eloquent       ProjectProvisioner
   Models             |
     |            Lokaal bestandssysteem
   Database
               |
           DeploymentService
               |
           SSH → Docker-server → Containers
```

### Services

| Service | Verantwoordelijkheid |
|---------|---------------------|
| `ProjectService` | Project CRUD, atomische slug + poorttoewijzing |
| `ServerService` | Server CRUD |
| `StackService` | Stack CRUD + template-validatie |
| `PortService` | Automatische poorttoewijzing (start bij 8001) |
| `ProjectProvisioner` | Lokale bestandssysteem-provisioning |
| `DeploymentService` | SSH-verbinding, Docker-uitvoering, deployment-lifecycle |
| `AllowedDomainService` | Beheer van toegestane registratiedomeinen |

### Database-tabellen

| Tabel | Inhoud |
|-------|--------|
| `users` | Gebruikers, `is_admin` vlag, 2FA-velden |
| `servers` | Docker-hosts, gekoppeld aan `user_id` |
| `stacks` | Deployment-templates |
| `projects` | Gedeployde applicaties, gekoppeld aan `user_id`, server en stack |
| `deployments` | Deployment-runs met log en status |
| `allowed_domains` | Toegestane e-maildomeinen voor registratie |

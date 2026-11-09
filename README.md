# Radiance Audio — business & artist platform

A lightweight PHP + SQLite application for a business-music service and independent artists. It has a responsive public website and distinct business, artist and administrator workspaces. There is **no consumer-listening account, pricing page, dependency installation, build step or MySQL setup**. The visual structure takes inspiration from Audiimax, but the copy and AI-generated images are Radiance placeholders that you should replace with your own verified content.

> **Status: functional development MVP, not production-ready.** The core workflows use a real server, database, passwords and private audio files. This is not a finished or audited commercial service. Email verification, notifications, password/code recovery, automated audio analysis, automated rights verification, malware scanning, royalties, payments and music licensing are not implemented. Do not collect real personal information or offer commercial music until the security, operational and legal work below is completed.

## What's implemented

### Public pages

Home, For Business, For Artists, About Us, FAQ and Contact. The artist and business pages include their own onboarding steps, FAQ and account entry points; About explains the concept and its values. The home page includes an inquiry form. The layouts and section structure are inspired by the reference pages, with Radiance-specific placeholder wording and no pricing section. Includes the 3-step Radiance Audio ecosystem flow (Emerging African artists → Radiance Audio platform → Local African businesses) and Business/Artist benefits. Contact messages are stored in the database and appear in the administrator workspace; **no email notification is sent**.

### Business workflow

- Register with email and password, business category, state and atmosphere. A new business account starts **Pending** and cannot sign in until an administrator approves it. Approved businesses can sign in and out.
- Change business details and password. Recommendations update from the approved/visible catalog.
- Recommendations score category-matching genres (+3), state (+2) and mood (+2); up to 12 appear. Admin-curated playlists for the business category or all businesses appear separately.
- Play approved real uploads through an authenticated audio endpoint, including HTTP byte ranges for seeking. Placeholder catalog entries are explicitly labelled and have no audio. A business is responsible for obtaining any applicable permissions and licences before public performance.

### Artist workflow

1. Submit a track **before** creating an artist account. MP3 and WAV are accepted, up to 25 MB subject to server configuration. A declaration of rights is required. The file is stored privately outside the intended web root.
2. Save the private tracking code shown **once** after submission. Use the code and submission email to check status. The server stores only a hash of the code. No email is sent and there is no code recovery yet.
3. An administrator listens and reviews rights, audio quality, metadata and genre. These are **manual checks**, not automatic verification. The administrator can approve, reject or request changes and add a note. Decisions are recorded in review history.
4. After track approval, the artist uses the code and email to request an account. The code is consumed. This account also starts **Pending** and needs a separate administrator approval before the artist can sign in. Once approved, the artist can see their submissions, listen to their own files, submit more tracks and change their password.

An approved real track enters the business catalog only after its artist account is also approved; rejecting either the track or account hides it. A reviewer can edit its verified genre, mood and visibility. The code does not prove ownership or establish any music rights. If an applicant makes several initial submissions using the same email, only one can be used to create that account; automatic linking of other pre-account submissions is not implemented. A track marked “Needs changes” cannot currently be replaced in place: submit a new track with a new tracking code.

### Administrator workflow

The admin entry point is intentionally **not linked from the public site**: `admin.php` in the public folder. Unlike a hidden-only demo, access to the dashboard and review actions requires a signed-in admin role. Set up your own administrator using the CLI instructions below. An admin can listen to submissions, record checks and decisions, approve or reject business and artist accounts, change genres/moods/visibility, create/edit/delete category playlists, view contacts and member details, and review recent track decisions. Account decisions are recorded in the database. The Admin, Business and Artist portals are organized into clean interactive tabs (no long single-page scrolling). In the Admin portal, switch between **Overview**, **Account Approvals**, **Track Reviews**, **Catalog**, **Playlists**, **Messages**, and **History & Security**. An approved artist’s approved tracks become visible automatically; returning an account to Pending/Rejected hides them. Reapproving an artist republishes all their approved tracks, including any previously hidden through catalog controls, so review visibility afterward. Playlist order follows the selected catalog order; drag-and-drop ordering is not implemented.

The workspace live preview has a **temporary, publicly known test administrator**:

- Email: `admin@example.test`
- Password: `RadianceDemo2026!`

Use these only to explore the workspace preview. The downloadable project ZIP excludes its database and test account; create your own admin. If you download the whole workspace instead, the `storage/radiance.sqlite` file may contain this test administrator and any preview data. **Do not deploy that database or these credentials.**

## Requirements

- PHP 8.1+ with `pdo_sqlite`, `sqlite3`, `fileinfo` and `mbstring` enabled.
- A writable private directory for the SQLite database and uploads. PHP sessions must work.
- PHP configured for uploads. For the advertised 25 MB maximum, set `upload_max_filesize=25M` and `post_max_size=27M` (and check the host's limits).
- A modern browser. No external fonts, JavaScript libraries or CDNs are required.

SQLite is used for simplicity. **MySQL and phpMyAdmin are not used**, so you do not need to create or import a database. Confirm your GoDaddy plan supports PDO SQLite and writable private storage. A host that offers PHP but disables SQLite will need a migration to MySQL/MariaDB before this project can run there.

## Run in XAMPP on Windows (Easiest — No Terminal Required)

Because your folder is already at `C:\xampp\htdocs\radiance-platform`, you can run everything directly through XAMPP Apache:

1. **Enable SQLite in XAMPP (one-time check):**
   - Open the **XAMPP Control Panel** → click **Config** next to Apache → click **PHP (`php.ini`)** (or open `C:\xampp\php\php.ini` in VS Code).
   - Search (`Ctrl+F`) for `pdo_sqlite` and `sqlite3`, and remove the leading semicolon `;` if present so they look like:
     ```ini
     extension=pdo_sqlite
     extension=sqlite3
     extension=fileinfo
     extension=mbstring
     ```
   - Save `php.ini` and click **Start** (or **Stop** then **Start**) next to **Apache** in the XAMPP Control Panel.
2. **Open the site in your browser:**
   - Public site: `http://localhost/radiance-platform/public/` (or `http://localhost/radiance-platform/`)
   - Admin page: `http://localhost/radiance-platform/public/admin.php` (or `http://localhost/radiance-platform/admin.php`)
3. **Create your first admin:**
   - The first time you open `admin.php` on a fresh install, you will see a **First-time admin setup** form right in your browser (or you can use the terminal command below).

---

## Using the Terminal in Windows / XAMPP (`php` command not found fix)

On Windows, XAMPP installs PHP at `C:\xampp\php\php.exe` and does **not** add `php` to the Windows `PATH` automatically. That is why typing `php -v` in PowerShell shows `CommandNotFoundException`.

In your VS Code PowerShell terminal at `C:\xampp\htdocs\radiance-platform>`, run this command first to add XAMPP's PHP to your current terminal session:

```powershell
$env:Path += ";C:\xampp\php"
```

Then all standard `php` commands work in that terminal:

```powershell
php -v
php -m
php -d extension=pdo_sqlite -d extension=sqlite3 app/setup-admin.php
```

Or, without changing `PATH`, call `C:\xampp\php\php.exe` directly:

```powershell
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe -m
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 app\setup-admin.php
```

If you prefer PHP's built-in development server instead of Apache in XAMPP:

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=mbstring -d upload_max_filesize=25M -d post_max_size=27M -S 127.0.0.1:8001 -t public
```

Then open `http://127.0.0.1:8001/` (and `http://127.0.0.1:8001/admin.php` for admin).

## Deployment on GoDaddy or similar shared hosting

This is a deployment guide, not a claim that every GoDaddy plan supports the required PHP configuration. First check the specific plan for PHP 8.1+, the extensions above, writable private files, upload limits, HTTPS, backups, and preferably SSH/CLI. Use a staging domain first.

1. Back up your existing website. Create a fresh project from the ZIP; **never upload the preview database or its publicly known admin credentials**.
2. Arrange the server files so only the public files are web-accessible. For example, if `public_html` is the document root:

   ```text
   account-home/                  # not web-accessible
     app/                         # project app folder
     storage/                     # writable by PHP, not publicly accessible
       uploads/
     public_html/                 # contents of project public/ folder
       index.php
       admin.php
       stream.php
       assets/
   ```

   The PHP files expect `app` and `storage` next to the public document root. The default storage path is the sibling `storage` directory. If you must use a different private path, set the `RADIANCE_STORAGE` environment variable for both the web process and CLI. Do not put the database, uploads or application source under `public_html`.
3. Create the first admin with `php app/setup-admin.php` over SSH, using a unique long password. If SSH is unavailable, create a **fresh** database locally with your own admin and upload that database into the private `storage` folder over SFTP; verify PHP can write to it and its directory. Do not create a public setup script.
4. Configure HTTPS, PHP sessions and the upload limits. SQLite needs write access to the database **and its directory**. Use the most restrictive permissions that allow your hosting PHP user to write. Do not set world-writable permissions. Confirm the database and uploads return 404/403 from the public internet.
5. Visit the site and `https://your-domain/admin.php`. Test registration, both account approvals, track upload/approval, login, streaming and logout on staging. Ensure rejected/hidden tracks cannot play and private files are inaccessible anonymously. Run a security review before accepting real users.

## Project layout

```text
radiance-platform/
  app/
    bootstrap.php          Database, sessions, helpers, seed placeholders
    actions.php            Server-side form handlers and authorization
    setup-admin.php        CLI-only first-admin setup
    .htaccess              Apache deny rule, local defense in depth
  public/                  Only this folder should be web-accessible
    index.php              Public pages and separate workspaces
    sections.php           Public editorial page sections
    admin.php              Unlisted admin entry point (role-protected)
    stream.php             Authenticated, byte-range audio streaming
    assets/                CSS and AI-generated placeholder images
    .htaccess
  storage/                 Private, writable SQLite database and uploads
    .htaccess              Apache deny rule, local defense in depth
  README.md
```

On first run, the application creates its tables and six clearly marked fictional catalog records without audio. For an existing installation from the previous version, the automatic account-status migration preserves existing accounts as Approved; review and change them manually if necessary. New business and artist registrations start Pending. These help test recommendations and can be hidden in the admin catalog. All generated files should be kept private. Back up the SQLite database **and** uploads together; test restores. Take consistent backups with the application stopped or SQLite's online backup facilities.

## Security and launch checklist

The MVP uses prepared SQL statements, escaped HTML output, password hashing with PHP `password_hash`, session ID regeneration on login, role checks, CSRF tokens, basic IP-based throttling, randomized private file names and limited type/size checks. These are useful foundations, **not a security guarantee**. The IP throttle is basic and unsuitable for distributed attacks or untrusted proxy configurations. The admin URL being unlisted is not a security measure.

Before launch, have a qualified developer/security reviewer implement and test:

- Email ownership verification, invitation delivery, password and lost tracking-code recovery; users currently must save their code and remember their password.
- MFA for admins, stronger rate limiting/abuse prevention, account management, session expiration and comprehensive audit logging. Review access controls and upload/stream endpoints; use HTTPS and secure server/session configuration. Secure cookies depend on the server correctly reporting HTTPS.
- A malware scan and robust server-side audio decoding/analysis. File extension and MIME checks do **not** prove that a file is safe, playable, clean, or good quality. For example, use FFmpeg/ffprobe in an isolated worker to measure duration, loudness, silence and clipping, then have a human review the results. This MVP does not perform those measurements.
- Full rights verification, agreements for the sound recording and composition, commercial/public-performance licences, territory and usage terms, royalties, reporting, disputes and takedowns. A checkbox and admin review cannot by themselves establish ownership or grant rights. Consult a qualified music lawyer and applicable collecting societies before public use.
- A privacy notice, consent and retention/deletion workflow, data subject requests and compliance with applicable Nigerian data protection rules. Protect backups and avoid retaining unnecessary personal information.
- Reliable email delivery, monitoring, error handling, deployment automation, proper migrations, restore tests, quota controls, background jobs and security/functional testing. SQLite is suitable for a small MVP; assess locking, capacity and a migration to a managed database as usage grows.

## Placeholder content and rights

All team names, catalog tracks and company descriptions are illustrative and must be replaced. Images in `public/assets` are AI-generated placeholders. No claim is made that Radiance currently has customers, artists, partnerships, royalty terms, commercial licences or operating history. The reference website's wording, trademarks, imagery and pricing are not included as Radiance facts.

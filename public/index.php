<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/actions.php';
require __DIR__ . '/sections.php';

db();
handle_post();

$p = (string) ($_GET['p'] ?? 'home');
if ($p === 'team' || $p === 'journal') {
    $p = 'about';
}

$pages = [
    'home',
    'business',
    'artists',
    'about',
    'faq',
    'contact',
    'business-register',
    'account-pending',
    'submit',
    'submitted',
    'status',
    'claim',
    'login',
    'admin-login',
    'artist-dashboard',
    'business-dashboard',
    'admin',
];

if (!in_array($p, $pages, true)) {
    http_response_code(404);
    $p = 'home';
}

$u = user();
if (in_array($p, ['artist-dashboard', 'business-dashboard', 'admin'], true)) {
    $u = require_role([
        'artist-dashboard'   => 'artist',
        'business-dashboard' => 'business',
        'admin'              => 'admin',
    ][$p]);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function url(string $page, array $params = []): string
{
    $query = array_merge(['p' => $page], $params);
    return 'index.php?' . http_build_query($query);
}

function linkto(string $page, string $label, string $class = '', array $params = []): string
{
    return '<a class="' . e($class) . '" href="' . e(url($page, $params)) . '">' . $label . '</a>';
}

function hidden(string $action): string
{
    return '<input type="hidden" name="csrf" value="' . e(token()) . '">'
         . '<input type="hidden" name="action" value="' . e($action) . '">';
}

function brand(): string
{
    return '<a class="brand" href="' . e(url('home')) . '">'
         . '<span class="logo">' . radiance_logo_svg(32) . '</span>'
         . '<span><b>Radiance Audio</b><small>ARTISTS · LOCAL BUSINESSES · AFRICA</small></span>'
         . '</a>';
}

function status_badge(string $status): string
{
    $key = strtolower(str_replace(' ', '-', $status));
    return '<span class="badge status-' . e($key) . '">' . e($status) . '</span>';
}

function header_public(): void
{
    global $p, $u;
    ?>
    <div class="topline">
        <div class="wrap top-inner">
            <span><b>Radiance Audio</b> &nbsp;·&nbsp; Connecting African artists with new audiences through local businesses</span>
            <div class="top-links">
                <?= linkto('about', 'About') ?>
                <?= linkto('contact', 'Contact') ?>
                <?php if ($u && $u['account_status'] === 'Approved'): ?>
                    <?= linkto($u['role'] === 'admin' ? 'admin' : $u['role'] . '-dashboard', 'Open Portal →', 'top-button') ?>
                <?php else: ?>
                    <?= linkto('login', 'Log in') ?>
                    <?= linkto('business-register', 'Get started →', 'top-button') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <header class="public-header">
        <div class="wrap nav-inner">
            <?= brand() ?>
            <nav aria-label="Main navigation">
                <?= linkto('home', 'Home', $p === 'home' ? 'active' : '') ?>
                <?= linkto('business', 'For Business', $p === 'business' ? 'active' : '') ?>
                <?= linkto('artists', 'For Artists', $p === 'artists' ? 'active' : '') ?>
                <?= linkto('about', 'About Us', $p === 'about' ? 'active' : '') ?>
                <?= linkto('faq', 'FAQ', $p === 'faq' ? 'active' : '') ?>
                <?= linkto('contact', 'Contact', $p === 'contact' ? 'active' : '') ?>
            </nav>
            <div class="nav-status"><i></i> LOCAL-FIRST SOUND</div>
        </div>
    </header>
    <?php
}

function footer_public(): void
{
    ?>
    <footer class="footer">
        <div class="wrap">
            <div class="footer-grid">
                <div>
                    <?= brand() ?>
                    <p>Connecting emerging African artists with cafés, bars, gyms, hotels and shops across local communities.</p>
                </div>
                <div>
                    <b>PLATFORMS</b>
                    <?= linkto('business', 'For businesses') ?>
                    <?= linkto('artists', 'For artists') ?>
                    <?= linkto('about', 'How Radiance works') ?>
                </div>
                <div>
                    <b>PORTALS</b>
                    <?= linkto('login', 'Business & artist sign in') ?>
                    <?= linkto('business-register', 'Request business account') ?>
                    <?= linkto('submit', 'Submit artist track') ?>
                </div>
                <div>
                    <b>SUPPORT</b>
                    <?= linkto('status', 'Check track status') ?>
                    <?= linkto('claim', 'Claim artist account') ?>
                    <?= linkto('faq', 'FAQ') ?>
                    <?= linkto('contact', 'Contact team') ?>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© <?= date('Y') ?> RADIANCE AUDIO. ALL RIGHTS RESERVED.</span>
                <span>EMERGING ARTISTS · LOCAL SPACES ✳</span>
            </div>
        </div>
    </footer>
    <?php
}

function section_intro(string $eyebrow, string $title, string $body = ''): void
{
    echo '<div class="section-intro"><div><div class="eyebrow">' . e($eyebrow) . '</div><h2>' . $title . '</h2></div>'
       . ($body ? '<p>' . e($body) . '</p>' : '')
       . '</div>';
}

function card(string $no, string $title, string $body, string $page, string $label): void
{
    echo '<div class="feature-card"><div class="card-num">' . e($no) . '<span>↗</span></div>'
       . '<h3>' . e($title) . '</h3><p>' . e($body) . '</p>'
       . linkto($page, e($label) . ' →', 'text-link') . '</div>';
}

function submission_form(bool $logged = false): void
{
    global $u;
    ?>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= hidden('submit') ?>
        <label>Artist name
            <input name="artist" required maxlength="150" value="<?= $logged ? e($u['name']) : '' ?>" placeholder="Your artist or band name">
        </label>
        <?php if (!$logged): ?>
            <label>Email address
                <input type="email" name="email" required maxlength="254" placeholder="you@example.com">
            </label>
        <?php endif; ?>
        <label>Song title
            <input name="title" required maxlength="150" placeholder="Title of your recording">
        </label>
        <label>State
            <select name="state"><?= select_options(states(), $logged ? ($u['state'] ?? 'Lagos') : 'Lagos') ?></select>
        </label>
        <label>Claimed genre
            <select name="genre"><?= select_options(genres()) ?></select>
        </label>
        <label class="wide">Track notes <span class="optional">optional</span>
            <textarea name="description" maxlength="2000" placeholder="Production credits, instruments or context for our review team"></textarea>
        </label>
        <label class="wide">Audio file (MP3 or WAV, up to 25 MB)
            <input type="file" name="audio" accept=".mp3,.wav,audio/mpeg,audio/wav" required>
            <small>Stored privately outside the public web folder for administrator review.</small>
        </label>
        <label class="check wide">
            <input type="checkbox" name="rights" required>
            I confirm that I own or hold the necessary permissions to submit this sound recording and composition for review.
        </label>
        <div class="wide">
            <button class="button primary" type="submit">Submit track for review ↗</button>
        </div>
    </form>
    <?php
}

function password_form(): void
{
    ?>
    <div class="panel">
        <h3>Change password</h3>
        <p class="muted">Use a strong password of at least 12 characters.</p>
        <form method="post" class="form-grid">
            <?= hidden('change-password') ?>
            <label>Current password
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <label>New password (min 12 chars)
                <input type="password" name="new_password" required minlength="12" maxlength="200" autocomplete="new-password">
            </label>
            <div class="wide">
                <button class="button outline small">Update password ↗</button>
            </div>
        </form>
    </div>
    <?php
}

function tracks_table(array $tracks): void
{
    if (!$tracks) {
        echo '<div class="empty">No tracks available in this view yet.</div>';
        return;
    }
    foreach ($tracks as $t) {
        ?>
        <div class="track-row">
            <div class="track-icon">♫</div>
            <div class="track-info">
                <b><?= e($t['title']) ?></b>
                <small><?= e($t['artist']) ?> · <?= e($t['state']) ?> · <span class="tag-pill"><?= e($t['genre']) ?></span> · <?= e($t['mood']) ?></small>
            </div>
            <?php if ((int) $t['is_demo']): ?>
                <span class="badge status-demo">DEMO METADATA · UPLOAD TRACK FOR AUDIO</span>
            <?php elseif (!empty($t['filename'])): ?>
                <audio controls preload="none" src="stream.php?track=<?= (int) $t['id'] ?>">Audio unavailable</audio>
            <?php endif; ?>
        </div>
        <?php
    }
}

function recommended(array $u): array
{
    $all = db()->query('SELECT * FROM tracks WHERE published=1 ORDER BY id DESC')->fetchAll();
    $map = [
        'Café / coffee shop'  => ['Soul', 'Jazz', 'Highlife', 'Instrumental'],
        'Restaurant'          => ['Soul', 'Afropop', 'Highlife', 'Jazz'],
        'Bar / lounge'        => ['Alté', 'Afrobeats', 'Afropop'],
        'Gym / fitness'       => ['Afrobeats', 'Afropop'],
        'Salon / spa'         => ['Soul', 'Alté', 'Instrumental'],
        'Retail / boutique'   => ['Afropop', 'Afrobeats', 'Alté'],
        'Hotel / hospitality' => ['Soul', 'Jazz', 'Highlife', 'Instrumental'],
        'Office / coworking'  => ['Instrumental', 'Jazz', 'Soul'],
    ];
    $tags = $map[$u['category']] ?? [];
    usort($all, function ($a, $b) use ($u, $tags) {
        $score = fn($t) => (($t['state'] === $u['state']) ? 2 : 0)
            + (($t['mood'] === $u['mood']) ? 2 : 0)
            + (in_array($t['genre'], $tags, true) ? 3 : 0);
        return $score($b) <=> $score($a) ?: $b['id'] <=> $a['id'];
    });
    return array_slice($all, 0, 12);
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0d1b2a">
    <title>Radiance Audio · Connecting African artists with local businesses</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= in_array($p, ['artist-dashboard', 'business-dashboard', 'admin'], true) ? 'portal-body' : 'site-body' ?>">
<?php if (!in_array($p, ['artist-dashboard', 'business-dashboard', 'admin'], true)): ?>
    <?php header_public(); ?>
<?php else: ?>
    <header class="workspace-head">
        <div class="wrap nav-inner">
            <?= brand() ?>
            <div class="workspace-user">
                <span class="role-chip role-<?= e($u['role']) ?>"><?= e(strtoupper($u['role'])) ?> PORTAL</span>
                <span class="user-name"><?= e($u['name']) ?></span>
                <?= linkto('home', 'Public site', 'nav-sublink') ?>
                <form method="post">
                    <?= hidden('logout') ?>
                    <button class="nav-logout">Log out ↗</button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>

<?php if ($flash): ?>
    <div class="wrap flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<main>
<?php if ($p === 'home'): ?>
    <section class="hero wrap">
        <div class="hero-copy">
            <div class="eyebrow">EMERGING AFRICAN ARTISTS · LOCAL BUSINESSES</div>
            <h1>Connecting African artists<br>with <em>new audiences</em><br>through local spaces.</h1>
            <p>Bring your café, bar, gym, hotel or shop to life with authentic local sounds. Radiance Audio curates emerging independent musicians for the places people love to spend time.</p>
            <div class="actions">
                <?= linkto('business-register', 'Request business account ↗', 'button primary') ?>
                <?= linkto('submit', 'Submit artist track', 'button outline') ?>
            </div>
            <div class="hero-note"><i></i> ADMIN-CURATED CATALOG · DIRECT VENUE STREAMING</div>
        </div>
        <div class="hero-visual">
            <img src="assets/venue.jpg" alt="Customers enjoying music in a local café">
            <div class="image-label"><i></i> DISTINCT, LOCAL-FIRST SOUND</div>
            <div class="floating-card">
                <span><?= radiance_logo_svg(28) ?></span>
                <div>
                    <small>RADIANCE AUDIO PLATFORM</small>
                    <b>Better atmosphere. Real artist growth.</b>
                </div>
                <span>↗</span>
            </div>
        </div>
    </section>

    <div class="ticker">
        <span>EMERGING AFRICAN ARTISTS</span><b>✳</b>
        <span>UNITY CAFÉ · MEMBER’S BAR · CITY GYM</span><b>✳</b>
        <span>BETTER VENUE ATMOSPHERE</span><b>✳</b>
        <span>LOCAL &amp; GLOBAL AUDIENCE</span><b>✳</b>
        <span>CURATED FOR BUSINESS</span>
    </div>

    <?php ecosystem_section(); ?>

    <section class="section wrap">
        <?= section_intro(
            'TWO DEDICATED PORTALS',
            'Built for venues and<br><em>the artists behind the sound.</em>',
            'Radiance separates business streaming and artist submissions into dedicated, administrator-approved workspaces.'
        ) ?>
        <div class="cards three">
            <?php
            card('01 / FOR BUSINESSES', 'Curated venue soundtracks', 'Request a business account, choose your venue category and mood, and stream approved local tracks.', 'business', 'Explore business portal');
            card('02 / FOR ARTISTS', 'A stage inside everyday spaces', 'Submit your track for review, receive a tracking code, and claim your artist portal once approved.', 'artists', 'Explore artist portal');
            card('03 / QUALITY & CURATION', 'Reviewed before playback', 'Every track submission and every business or artist account is reviewed by an administrator before going live.', 'about', 'How curation works');
            ?>
        </div>
    </section>

    <section class="artist-callout">
        <div class="wrap split">
            <div>
                <div class="eyebrow gold-text">ARE YOU AN INDEPENDENT AFRICAN MUSICIAN?</div>
                <h2>Put your music inside<br><em>the spaces that move cities.</em></h2>
                <p>Submit your recording for review. Once approved by our curation team, request your artist account to track your catalog and share more music.</p>
                <div class="actions">
                    <?= linkto('submit', 'Submit a track ↗', 'button primary') ?>
                    <?= linkto('artists', 'How artist onboarding works', 'button outline-light') ?>
                </div>
            </div>
            <div class="photo">
                <img src="assets/studio.jpg" alt="Independent artist recording in studio">
            </div>
        </div>
    </section>

    <?php home_contact(); ?>

<?php elseif ($p === 'artists'): ?>
    <section class="hero wrap page-hero">
        <div>
            <div class="eyebrow">FOR INDEPENDENT ARTISTS</div>
            <h1>Your music, heard<br>in <em>real local spaces.</em></h1>
            <p>Connect with new listeners across cafés, lounges, gyms, hotels and boutiques. Share your original recordings with the Radiance Audio curation team.</p>
            <div class="actions">
                <?= linkto('submit', 'Submit your music ↗', 'button primary') ?>
                <?= linkto('status', 'Check track status', 'button outline') ?>
                <?= linkto('claim', 'Claim artist account', 'button outline') ?>
            </div>
        </div>
        <div class="hero-visual">
            <img src="assets/studio.jpg" alt="Independent artist recording music">
        </div>
    </section>
    <?php journey('artist'); ?>
    <section class="split-section">
        <div class="wrap split">
            <div>
                <div class="eyebrow">BEFORE YOU SUBMIT</div>
                <h2>What our curation team<br><em>looks for.</em></h2>
                <p>To keep the Radiance catalog high-quality and authentic, every submission is reviewed manually before an artist account can be claimed and approved.</p>
                <?= linkto('submit', 'Start track submission ↗', 'button primary') ?>
            </div>
            <div class="info-box">
                <small>ARTIST SUBMISSION CHECKLIST</small>
                <ul class="line-list">
                    <li><span>01</span> Artist or band name, contact email and state</li>
                    <li><span>02</span> Original MP3 or WAV recording (up to 25 MB)</li>
                    <li><span>03</span> Confirmation of recording and composition permissions</li>
                    <li><span>04</span> Save your one-time tracking code to check status &amp; claim your account</li>
                </ul>
            </div>
        </div>
    </section>
    <?php audience_faq('artist'); ?>
    <section class="section wrap">
        <div class="center-cta">
            <div class="eyebrow">RADIANCE · ARTIST PORTAL</div>
            <h2>Ready to introduce<br><em>your sound?</em></h2>
            <p>Start by submitting your track or sign in if your artist account is already approved.</p>
            <div class="actions center">
                <?= linkto('submit', 'Submit your music ↗', 'button primary') ?>
                <?= linkto('status', 'Check submission status', 'button outline') ?>
                <?= linkto('login', 'Artist sign in', 'button outline') ?>
            </div>
        </div>
    </section>

<?php elseif ($p === 'business'): ?>
    <section class="hero wrap page-hero">
        <div>
            <div class="eyebrow">FOR BUSINESSES &amp; VENUES</div>
            <h1>Give your venue a<br><em>distinct, local-first sound.</em></h1>
            <p>From morning coffee at a neighborhood café to evening sets at a member’s bar or high-energy sessions at a gym, stream emerging African artists matched to your space.</p>
            <div class="actions">
                <?= linkto('business-register', 'Request business account ↗', 'button primary') ?>
                <?= linkto('login', 'Business sign in', 'button outline') ?>
            </div>
        </div>
        <div class="hero-visual">
            <img src="assets/venue.jpg" alt="Customers enjoying a welcoming café">
        </div>
    </section>
    <?php journey('business'); ?>
    <?php business_places(); ?>
    <?php audience_faq('business'); ?>
    <section class="section wrap">
        <div class="center-cta">
            <div class="eyebrow">RADIANCE · FOR BUSINESS</div>
            <h2>Better atmosphere and<br><em>customer loyalty start here.</em></h2>
            <p>Tell us about your venue and request an approved business account.</p>
            <div class="actions center">
                <?= linkto('business-register', 'Create a business account ↗', 'button primary') ?>
                <?= linkto('contact', 'Talk to the team', 'button outline') ?>
            </div>
        </div>
    </section>

<?php elseif ($p === 'about'): ?>
    <section class="about-hero">
        <div class="about-inner">
            <div class="eyebrow">ABOUT RADIANCE AUDIO</div>
            <h1>Connecting African artists<br>with <em>local businesses</em><br>and everyday audiences.</h1>
            <p>Radiance Audio bridges independent musicians and local venues—giving businesses a distinctive atmosphere while helping emerging artists build real-world recognition and fan growth.</p>
        </div>
    </section>
    <section class="section wrap">
        <?= section_intro(
            'OUR CORE PILLARS',
            'Three principles behind<br><em>the Radiance platform.</em>',
            'Designed for clarity, curated quality, and genuine local discovery.'
        ) ?>
        <div class="cards three">
            <?php
            card('PILLAR / 01', 'Emerging African talent', 'Highlight independent musicians, bands and producers creating distinctive local sounds.', 'artists', 'For artists');
            card('PILLAR / 02', 'Venue-matched curation', 'Match music to the exact setting, mood and location of cafés, bars, gyms and shops.', 'business', 'For businesses');
            card('PILLAR / 03', 'Human approval gates', 'Both artist tracks and member accounts are reviewed by administrators before access or publication.', 'faq', 'Read the FAQ');
            ?>
        </div>
    </section>
    <?php about_more(); ?>

<?php elseif ($p === 'faq'): ?>
    <section class="section wrap narrow">
        <div class="eyebrow">FREQUENTLY ASKED QUESTIONS</div>
        <h1 class="page-heading">How Radiance Audio<br><em>works in practice.</em></h1>
        <div class="faq-list">
            <?php
            $faqs = [
                ['Who can use Radiance Audio?', 'Radiance Audio is built strictly for local businesses and independent artists. There are no general consumer accounts or user-created playlists.'],
                ['How do business accounts work?', 'Businesses register with their venue category, state and preferred atmosphere. Once an administrator approves the account, the business can sign in and stream recommended tracks and curated playlists.'],
                ['How do artist accounts work?', 'Artists first submit a track and receive a one-time tracking code. After an administrator approves the track, the artist uses that code to request an account, which is then approved by the administrator.'],
                ['When does an artist’s song appear to businesses?', 'Only when both the song submission AND the artist’s account are marked Approved by an administrator.'],
                ['Who creates playlists on the platform?', 'Playlists are created and assigned to venue categories exclusively by administrators inside the private admin portal.'],
            ];
            foreach ($faqs as [$q, $a]):
            ?>
                <details>
                    <summary><?= e($q) ?><span>+</span></summary>
                    <p><?= e($a) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

<?php elseif ($p === 'contact'): ?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow">GET IN TOUCH</div>
            <h1 class="page-heading">Let’s talk about<br><em>your venue or music.</em></h1>
            <p class="lede">Send a question or partnership inquiry directly to the Radiance Audio team. Messages appear inside the administrator portal.</p>
        </div>
        <div class="form-panel">
            <h3>Send us a message</h3>
            <form method="post" class="form-grid">
                <?= hidden('contact') ?>
                <label>Your name<input name="name" required maxlength="150"></label>
                <label>Email address<input type="email" name="email" required maxlength="254"></label>
                <label class="wide">I am a
                    <select name="role">
                        <option>Business</option>
                        <option>Artist</option>
                        <option>Other</option>
                    </select>
                </label>
                <label class="wide">Your message<textarea name="message" required maxlength="3000"></textarea></label>
                <div class="wide"><button class="button primary">Send message ↗</button></div>
            </form>
        </div>
    </section>

<?php elseif ($p === 'business-register'): ?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow">RADIANCE FOR BUSINESS</div>
            <h1 class="page-heading">Request your<br><em>business account.</em></h1>
            <p class="lede">Tell us about your venue, location and preferred atmosphere. An administrator will review and approve your account before you can sign in.</p>
        </div>
        <div class="form-panel">
            <h3>Create business account</h3>
            <p class="muted">All fields are required. Your account starts in Pending status until approved.</p>
            <form method="post" class="form-grid">
                <?= hidden('business-register') ?>
                <label class="wide">Business / venue name<input name="name" required maxlength="150" placeholder="e.g. Unity Café"></label>
                <label class="wide">Work email address<input name="email" type="email" required maxlength="254" placeholder="you@venue.com"></label>
                <label class="wide">Password (at least 12 characters)
                    <input type="password" name="password" minlength="12" maxlength="200" required autocomplete="new-password">
                </label>
                <label>Venue category
                    <select name="category"><?= select_options(categories()) ?></select>
                </label>
                <label>State
                    <select name="state"><?= select_options(states(), 'Lagos') ?></select>
                </label>
                <label class="wide">Preferred atmosphere
                    <select name="mood"><?= select_options(moods(), 'Balanced') ?></select>
                </label>
                <div class="wide">
                    <button class="button primary">Request business account ↗</button>
                    <p>Already approved? <?= linkto('login', 'Sign in here', 'text-link') ?></p>
                </div>
            </form>
        </div>
    </section>

<?php elseif ($p === 'account-pending'): ?>
    <section class="section wrap narrow">
        <div class="form-panel center-panel">
            <div class="eyebrow">ACCOUNT REQUEST RECEIVED</div>
            <h1 class="page-heading">Awaiting administrator<br><em>approval.</em></h1>
            <p class="lede">Your account request has been saved with status <b>Pending</b>. Once an administrator approves it in the admin portal, you can sign in to your workspace.</p>
            <div class="actions center">
                <?= linkto('login', 'Go to sign in ↗', 'button primary') ?>
                <?= linkto('home', 'Return home', 'button outline') ?>
            </div>
        </div>
    </section>

<?php elseif ($p === 'submit'): ?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow">STEP 1 · ARTIST SUBMISSION</div>
            <h1 class="page-heading">Share your track<br><em>for review.</em></h1>
            <p class="lede">Artists submit an initial track before creating an account. Keep the one-time tracking code displayed after upload—you will need it to check your review status and request your artist account.</p>
        </div>
        <div class="form-panel">
            <h3>Submit your recording</h3>
            <?php submission_form(); ?>
            <p>Already submitted? <?= linkto('status', 'Check track status', 'text-link') ?> or <?= linkto('claim', 'claim artist account', 'text-link') ?>.</p>
        </div>
    </section>

<?php elseif ($p === 'submitted'):
    $receipt = $_SESSION['receipt'] ?? null;
    unset($_SESSION['receipt']);
?>
    <section class="section wrap narrow">
        <div class="eyebrow">SUBMISSION RECEIVED</div>
        <h1 class="page-heading">Save your private<br><em>tracking code.</em></h1>
        <?php if ($receipt): ?>
            <div class="notice">
                <b>Important:</b> Copy and save this tracking code right now. It is shown only once and is required to check your review status and claim your artist account after track approval.
            </div>
            <div class="form-panel">
                <small>SUBMISSION EMAIL</small>
                <p><b><?= e($receipt['email']) ?></b></p>
                <small>ONE-TIME TRACKING CODE</small>
                <p class="tracking-code"><?= e($receipt['code']) ?></p>
            </div>
            <div class="actions">
                <?= linkto('status', 'Check submission status ↗', 'button primary') ?>
                <?= linkto('artists', 'Back to artists page', 'button outline') ?>
            </div>
        <?php else: ?>
            <p class="lede">Your one-time code was displayed when you submitted. Use that saved code to check your status.</p>
            <?= linkto('status', 'Check submission status ↗', 'button primary') ?>
        <?php endif; ?>
    </section>

<?php elseif ($p === 'status'):
    $result = $_SESSION['status_result'] ?? null;
    unset($_SESSION['status_result']);
?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow">STEP 2 · TRACK STATUS</div>
            <h1 class="page-heading">Check your<br><em>submission status.</em></h1>
            <p class="lede">Enter your submission email and private tracking code to see if an administrator has approved your recording.</p>
            <?php if ($result): ?>
                <div class="form-panel result-card">
                    <small>SUBMISSION RESULT</small>
                    <h3><?= e($result['title']) ?> · <?= e($result['artist_name']) ?></h3>
                    <?= status_badge($result['status']) ?>
                    <?php if ($result['note']): ?>
                        <p><b>Reviewer note:</b> <?= e($result['note']) ?></p>
                    <?php endif; ?>
                    <?php if ($result['status'] === 'Approved'): ?>
                        <p>Your track is approved! Next, use the same email and tracking code to request your artist account.</p>
                        <?= linkto('claim', 'Request artist account ↗', 'button primary') ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-panel">
            <h3>Lookup submission</h3>
            <form method="post" class="form-grid">
                <?= hidden('status') ?>
                <label class="wide">Submission email<input type="email" name="email" required></label>
                <label class="wide">Private tracking code<input name="code" required autocomplete="off"></label>
                <div class="wide"><button class="button primary">Check status ↗</button></div>
            </form>
        </div>
    </section>

<?php elseif ($p === 'claim'): ?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow">STEP 3 · CLAIM ARTIST ACCOUNT</div>
            <h1 class="page-heading">Request your<br><em>artist portal.</em></h1>
            <p class="lede">Once your track submission is marked <b>Approved</b>, enter your email and tracking code below to create your artist account. An administrator will then approve the account so you can sign in.</p>
        </div>
        <div class="form-panel">
            <h3>Create artist account</h3>
            <form method="post" class="form-grid">
                <?= hidden('claim') ?>
                <label class="wide">Submission email<input type="email" name="email" required></label>
                <label class="wide">Private tracking code<input name="code" required autocomplete="off"></label>
                <label class="wide">Choose password (at least 12 characters)
                    <input type="password" name="password" required minlength="12" maxlength="200" autocomplete="new-password">
                </label>
                <div class="wide"><button class="button primary">Request artist account ↗</button></div>
            </form>
        </div>
    </section>

<?php elseif ($p === 'login' || $p === 'admin-login'):
    $needsAdmin = $p === 'admin-login' && (int) db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 0;
?>
    <section class="section wrap form-layout">
        <div>
            <div class="eyebrow"><?= $p === 'admin-login' ? 'PRIVATE ADMINISTRATOR PORTAL' : 'PORTAL SIGN IN' ?></div>
            <h1 class="page-heading">
                <?= $needsAdmin ? 'Create your<br><em>admin account.</em>' : 'Sign in to your<br><em>workspace.</em>' ?>
            </h1>
            <p class="lede">
                <?= $needsAdmin
                    ? 'No administrator account exists in this database yet. Create the first admin account below to unlock the admin portal.'
                    : ($p === 'admin-login'
                        ? 'Sign in with your administrator credentials to review accounts, submissions, catalog tracks and playlists.'
                        : 'Sign in to your approved Business or Artist portal.')
                ?>
            </p>
        </div>
        <div class="form-panel">
            <?php if ($needsAdmin): ?>
                <h3>First-time administrator setup</h3>
                <form method="post" class="form-grid">
                    <?= hidden('setup-admin') ?>
                    <label class="wide">Administrator name<input name="name" required maxlength="150" placeholder="Radiance Admin"></label>
                    <label class="wide">Email address<input type="email" name="email" required maxlength="254" placeholder="admin@yourdomain.com"></label>
                    <label class="wide">Password (at least 12 characters)
                        <input type="password" name="password" required minlength="12" maxlength="200" autocomplete="new-password">
                    </label>
                    <div class="wide"><button class="button primary">Create admin &amp; enter portal ↗</button></div>
                </form>
            <?php else: ?>
                <h3><?= $p === 'admin-login' ? 'Administrator sign in' : 'Sign in' ?></h3>
                <form method="post" class="form-grid">
                    <?= hidden('login') ?>
                    <?php if ($p === 'admin-login'): ?>
                        <input type="hidden" name="role" value="admin">
                    <?php else: ?>
                        <label class="wide">Portal type
                            <select name="role">
                                <option value="business">Business Portal</option>
                                <option value="artist">Artist Portal</option>
                            </select>
                        </label>
                    <?php endif; ?>
                    <label class="wide">Email address<input type="email" name="email" required autocomplete="username"></label>
                    <label class="wide">Password<input type="password" name="password" required autocomplete="current-password"></label>
                    <div class="wide"><button class="button primary">Sign in ↗</button></div>
                </form>
                <?php if ($p !== 'admin-login'): ?>
                    <p>Need an account? <?= linkto('business-register', 'Request business account', 'text-link') ?> · <?= linkto('submit', 'Submit artist track', 'text-link') ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

<?php elseif ($p === 'business-dashboard'):
    $recs = recommended($u);
    $q = db()->prepare('SELECT * FROM playlists WHERE category=? OR category=? ORDER BY id DESC');
    $q->execute([$u['category'], 'All businesses']);
    $playlists = $q->fetchAll();
    $tab = (string) ($_GET['tab'] ?? 'discover');
    if (!in_array($tab, ['discover', 'playlists', 'settings'], true)) {
        $tab = 'discover';
    }
?>
    <section class="workspace wrap" data-portal="business">
        <div class="portal-banner navy-banner">
            <div>
                <div class="eyebrow gold-text">APPROVED BUSINESS PORTAL</div>
                <h1><?= e($u['name']) ?></h1>
                <p>Streaming curated sounds matched to your venue profile.</p>
            </div>
            <div class="portal-pills">
                <span><b>Category:</b> <?= e($u['category']) ?></span>
                <span><b>Atmosphere:</b> <?= e($u['mood']) ?></span>
                <span><b>State:</b> <?= e($u['state']) ?></span>
            </div>
        </div>

        <div class="portal-tabs" role="tablist" aria-label="Business portal sections">
            <button type="button" class="portal-tab <?= $tab === 'discover' ? 'active' : '' ?>" data-tab-target="discover" role="tab">
                Recommended Tracks <span class="tab-count"><?= count($recs) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'playlists' ? 'active' : '' ?>" data-tab-target="playlists" role="tab">
                Curated Playlists <span class="tab-count"><?= count($playlists) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'settings' ? 'active' : '' ?>" data-tab-target="settings" role="tab">
                Venue &amp; Security Settings
            </button>
        </div>

        <div class="tab-pane <?= $tab === 'discover' ? 'active' : '' ?>" data-tab-pane="discover" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">MATCHED TO YOUR VENUE</div>
                    <h2>Recommended tracks</h2>
                    <p class="muted">Ranked automatically by your venue category (<?= e($u['category']) ?>), preferred mood (<?= e($u['mood']) ?>) and location (<?= e($u['state']) ?>).</p>
                </div>
            </div>
            <div class="panel">
                <?php tracks_table($recs); ?>
            </div>
        </div>

        <div class="tab-pane <?= $tab === 'playlists' ? 'active' : '' ?>" data-tab-pane="playlists" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">CURATED BY RADIANCE ADMIN</div>
                    <h2>Playlists for <?= e($u['category']) ?></h2>
                    <p class="muted">Playlists assigned to your business category or all businesses.</p>
                </div>
            </div>
            <?php if (!$playlists): ?>
                <div class="panel empty">No curated playlists have been published for your venue category yet.</div>
            <?php endif; ?>
            <?php foreach ($playlists as $pl):
                $q = db()->prepare('SELECT t.* FROM playlist_tracks pt JOIN tracks t ON t.id=pt.track_id WHERE pt.playlist_id=? AND t.published=1 ORDER BY pt.position');
                $q->execute([$pl['id']]);
            ?>
                <div class="panel playlist">
                    <div class="playlist-head">
                        <div>
                            <span class="tag-pill"><?= e($pl['category']) ?></span>
                            <h3><?= e($pl['name']) ?></h3>
                            <?php if ($pl['description']): ?><p class="muted"><?= e($pl['description']) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <?php tracks_table($q->fetchAll()); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-pane <?= $tab === 'settings' ? 'active' : '' ?>" data-tab-pane="settings" role="tabpanel">
            <div class="two-col-grid">
                <div class="panel">
                    <h3>Venue profile &amp; atmosphere</h3>
                    <p class="muted">Updating these settings refreshes your recommended tracks immediately.</p>
                    <form method="post" class="form-grid">
                        <?= hidden('business-profile') ?>
                        <label class="wide">Business name
                            <input name="name" required maxlength="150" value="<?= e($u['name']) ?>">
                        </label>
                        <label>Venue category
                            <select name="category"><?= select_options(categories(), $u['category']) ?></select>
                        </label>
                        <label>State
                            <select name="state"><?= select_options(states(), $u['state']) ?></select>
                        </label>
                        <label class="wide">Preferred atmosphere
                            <select name="mood"><?= select_options(moods(), $u['mood']) ?></select>
                        </label>
                        <div class="wide">
                            <button class="button primary small">Save venue settings ↗</button>
                        </div>
                    </form>
                </div>
                <?php password_form(); ?>
            </div>
        </div>
    </section>

<?php elseif ($p === 'artist-dashboard'):
    $q = db()->prepare('SELECT * FROM submissions WHERE user_id=? ORDER BY id DESC');
    $q->execute([$u['id']]);
    $subs = $q->fetchAll();
    $pendingSubs = count(array_filter($subs, fn($x) => $x['status'] === 'Pending'));
    $approvedSubs = count(array_filter($subs, fn($x) => $x['status'] === 'Approved'));
    $tab = (string) ($_GET['tab'] ?? 'tracks');
    if (!in_array($tab, ['tracks', 'submit', 'settings'], true)) {
        $tab = 'tracks';
    }
?>
    <section class="workspace wrap" data-portal="artist">
        <div class="portal-banner terracotta-banner">
            <div>
                <div class="eyebrow gold-text">APPROVED ARTIST PORTAL</div>
                <h1><?= e($u['name']) ?></h1>
                <p>Manage your submitted recordings and share new tracks with local venues.</p>
            </div>
            <div class="portal-pills">
                <span><b>Submitted:</b> <?= count($subs) ?></span>
                <span><b>Pending review:</b> <?= $pendingSubs ?></span>
                <span><b>Approved:</b> <?= $approvedSubs ?></span>
            </div>
        </div>

        <div class="portal-tabs" role="tablist" aria-label="Artist portal sections">
            <button type="button" class="portal-tab <?= $tab === 'tracks' ? 'active' : '' ?>" data-tab-target="tracks" role="tab">
                My Tracks <span class="tab-count"><?= count($subs) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'submit' ? 'active' : '' ?>" data-tab-target="submit" role="tab">
                + Submit New Track
            </button>
            <button type="button" class="portal-tab <?= $tab === 'settings' ? 'active' : '' ?>" data-tab-target="settings" role="tab">
                Account Security
            </button>
        </div>

        <div class="tab-pane <?= $tab === 'tracks' ? 'active' : '' ?>" data-tab-pane="tracks" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">YOUR CATALOG &amp; REVIEW STATUS</div>
                    <h2>Submitted recordings</h2>
                    <p class="muted">Approved tracks from your approved account are eligible for business recommendations and curated playlists.</p>
                </div>
                <button type="button" class="button primary small" data-jump-tab="submit">+ Submit another track</button>
            </div>
            <div class="panel">
                <?php if (!$subs): ?>
                    <div class="empty">You have not submitted any tracks yet.</div>
                <?php endif; ?>
                <?php foreach ($subs as $s): ?>
                    <div class="submission-summary">
                        <div>
                            <b><?= e($s['title']) ?></b>
                            <p><span class="tag-pill"><?= e($s['verified_genre'] ?: $s['genre']) ?></span> · <?= e($s['state']) ?> · Submitted <?= e($s['created_at']) ?></p>
                            <?php if ($s['note']): ?>
                                <p class="reviewer-note"><b>Reviewer note:</b> <?= e($s['note']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?= status_badge($s['status']) ?>
                        <audio controls preload="none" src="stream.php?submission=<?= (int) $s['id'] ?>"></audio>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane <?= $tab === 'submit' ? 'active' : '' ?>" data-tab-pane="submit" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">NEW RELEASE</div>
                    <h2>Submit another track for review</h2>
                    <p class="muted">Upload an MP3 or WAV recording (up to 25 MB). It will be linked directly to your artist account.</p>
                </div>
            </div>
            <div class="panel">
                <?php submission_form(true); ?>
            </div>
        </div>

        <div class="tab-pane <?= $tab === 'settings' ? 'active' : '' ?>" data-tab-pane="settings" role="tabpanel">
            <div class="two-col-grid">
                <div class="panel">
                    <h3>Artist account summary</h3>
                    <div class="detail-list">
                        <div><small>ARTIST NAME</small><b><?= e($u['name']) ?></b></div>
                        <div><small>EMAIL ADDRESS</small><b><?= e($u['email']) ?></b></div>
                        <div><small>STATE</small><b><?= e($u['state'] ?: 'Not set') ?></b></div>
                        <div><small>ACCOUNT STATUS</small><?= status_badge($u['account_status']) ?></div>
                    </div>
                </div>
                <?php password_form(); ?>
            </div>
        </div>
    </section>

<?php elseif ($p === 'admin'):
    $db = db();
    $submissions = $db->query('SELECT * FROM submissions ORDER BY id DESC')->fetchAll();
    $catalog = $db->query('SELECT * FROM tracks ORDER BY id DESC')->fetchAll();
    $pls = $db->query('SELECT * FROM playlists ORDER BY id DESC')->fetchAll();
    $inquiries = $db->query('SELECT * FROM inquiries ORDER BY id DESC LIMIT 100')->fetchAll();
    $members = $db->query("SELECT id,name,email,role,category,state,account_status,created_at FROM users WHERE role <> 'admin' ORDER BY CASE WHEN account_status='Pending' THEN 0 ELSE 1 END, id DESC LIMIT 200")->fetchAll();
    $logs = $db->query('SELECT l.*, s.title, u.name AS reviewer FROM review_logs l JOIN submissions s ON s.id=l.submission_id JOIN users u ON u.id=l.admin_id ORDER BY l.id DESC LIMIT 100')->fetchAll();
    $accountLogs = $db->query('SELECT ar.*, u.name AS member_name, u.role AS member_role, a.name AS admin_name FROM account_reviews ar JOIN users u ON u.id=ar.user_id JOIN users a ON a.id=ar.admin_id ORDER BY ar.id DESC LIMIT 100')->fetchAll();

    $pendingAccounts = array_values(array_filter($members, fn($m) => $m['account_status'] === 'Pending'));
    $pendingSubmissions = array_values(array_filter($submissions, fn($s) => $s['status'] === 'Pending'));
    $publishedTracksCount = count(array_filter($catalog, fn($t) => (int) $t['published'] === 1));

    $edit = null;
    $selected = [];
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId) {
        $q = $db->prepare('SELECT * FROM playlists WHERE id=?');
        $q->execute([$editId]);
        $edit = $q->fetch();
        if ($edit) {
            $q = $db->prepare('SELECT track_id FROM playlist_tracks WHERE playlist_id=? ORDER BY position');
            $q->execute([$editId]);
            $selected = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
        }
    }

    $tab = (string) ($_GET['tab'] ?? ($editId ? 'playlists' : 'overview'));
    $validTabs = ['overview', 'accounts', 'reviews', 'catalog', 'playlists', 'inquiries', 'audit'];
    if (!in_array($tab, $validTabs, true)) {
        $tab = 'overview';
    }
?>
    <section class="workspace wrap" data-portal="admin">
        <div class="portal-banner navy-banner">
            <div>
                <div class="eyebrow gold-text">PRIVATE ADMINISTRATOR PORTAL</div>
                <h1>Curation &amp; Approvals</h1>
                <p>Organized by workflow tab—approve business and artist accounts, review tracks, manage catalog visibility and curate playlists.</p>
            </div>
            <div class="portal-pills">
                <span><b>Pending Accounts:</b> <?= count($pendingAccounts) ?></span>
                <span><b>Pending Tracks:</b> <?= count($pendingSubmissions) ?></span>
                <span><b>Live Catalog:</b> <?= $publishedTracksCount ?></span>
            </div>
        </div>

        <div class="portal-tabs" role="tablist" aria-label="Administrator portal tabs">
            <button type="button" class="portal-tab <?= $tab === 'overview' ? 'active' : '' ?>" data-tab-target="overview" role="tab">
                Overview
            </button>
            <button type="button" class="portal-tab <?= $tab === 'accounts' ? 'active' : '' ?>" data-tab-target="accounts" role="tab">
                Account Approvals
                <span class="tab-count <?= count($pendingAccounts) ? 'urgent' : '' ?>"><?= count($pendingAccounts) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'reviews' ? 'active' : '' ?>" data-tab-target="reviews" role="tab">
                Track Reviews
                <span class="tab-count <?= count($pendingSubmissions) ? 'urgent' : '' ?>"><?= count($pendingSubmissions) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'catalog' ? 'active' : '' ?>" data-tab-target="catalog" role="tab">
                Catalog <span class="tab-count"><?= count($catalog) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'playlists' ? 'active' : '' ?>" data-tab-target="playlists" role="tab">
                Playlists <span class="tab-count"><?= count($pls) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'inquiries' ? 'active' : '' ?>" data-tab-target="inquiries" role="tab">
                Messages <span class="tab-count"><?= count($inquiries) ?></span>
            </button>
            <button type="button" class="portal-tab <?= $tab === 'audit' ? 'active' : '' ?>" data-tab-target="audit" role="tab">
                History &amp; Security
            </button>
        </div>

        <!-- TAB 1: OVERVIEW -->
        <div class="tab-pane <?= $tab === 'overview' ? 'active' : '' ?>" data-tab-pane="overview" role="tabpanel">
            <div class="stat-grid four">
                <div class="stat-card accent-orange">
                    <small>PENDING ACCOUNTS</small>
                    <b><?= count($pendingAccounts) ?></b>
                    <span><?= count($members) ?> total registered accounts</span>
                    <button type="button" class="tab-link-btn" data-jump-tab="accounts">Review accounts →</button>
                </div>
                <div class="stat-card accent-teal">
                    <small>PENDING TRACKS</small>
                    <b><?= count($pendingSubmissions) ?></b>
                    <span><?= count($submissions) ?> total submissions</span>
                    <button type="button" class="tab-link-btn" data-jump-tab="reviews">Review tracks →</button>
                </div>
                <div class="stat-card accent-navy">
                    <small>PUBLISHED TRACKS</small>
                    <b><?= $publishedTracksCount ?></b>
                    <span><?= count($catalog) ?> total in catalog</span>
                    <button type="button" class="tab-link-btn" data-jump-tab="catalog">Manage catalog →</button>
                </div>
                <div class="stat-card accent-gold">
                    <small>CURATED PLAYLISTS</small>
                    <b><?= count($pls) ?></b>
                    <span><?= count($inquiries) ?> contact messages</span>
                    <button type="button" class="tab-link-btn" data-jump-tab="playlists">Manage playlists →</button>
                </div>
            </div>

            <div class="two-col-grid">
                <div class="panel">
                    <div class="pane-header compact">
                        <div>
                            <div class="eyebrow">ACTION QUEUE · ACCOUNTS</div>
                            <h3>Accounts awaiting approval</h3>
                        </div>
                        <button type="button" class="button outline small" data-jump-tab="accounts">View all (<?= count($members) ?>)</button>
                    </div>
                    <?php if (!$pendingAccounts): ?>
                        <div class="empty">All business and artist accounts have been reviewed.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($pendingAccounts, 0, 5) as $m): ?>
                            <div class="queue-item">
                                <div>
                                    <b><?= e($m['name']) ?></b>
                                    <span class="role-chip role-<?= e($m['role']) ?>"><?= e(ucfirst($m['role'])) ?></span>
                                    <small><?= e($m['email']) ?> · <?= e($m['category'] ?: $m['state']) ?></small>
                                </div>
                                <form method="post" class="inline-actions">
                                    <?= hidden('account-review') ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <button name="account_status" value="Approved" class="button primary small">Approve</button>
                                    <button name="account_status" value="Rejected" class="button outline small">Reject</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="pane-header compact">
                        <div>
                            <div class="eyebrow">ACTION QUEUE · SUBMISSIONS</div>
                            <h3>Tracks awaiting review</h3>
                        </div>
                        <button type="button" class="button outline small" data-jump-tab="reviews">Open review desk (<?= count($submissions) ?>)</button>
                    </div>
                    <?php if (!$pendingSubmissions): ?>
                        <div class="empty">No pending track submissions in the queue.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($pendingSubmissions, 0, 5) as $s): ?>
                            <div class="queue-item">
                                <div>
                                    <b><?= e($s['title']) ?></b> · <?= e($s['artist_name']) ?>
                                    <small><?= e($s['genre']) ?> · <?= e($s['state']) ?> · <?= e($s['created_at']) ?></small>
                                </div>
                                <button type="button" class="button primary small" data-jump-tab="reviews">Review track →</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB 2: ACCOUNT APPROVALS -->
        <div class="tab-pane <?= $tab === 'accounts' ? 'active' : '' ?>" data-tab-pane="accounts" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">MEMBER ACCESS CONTROL</div>
                    <h2>Business &amp; Artist Account Approvals</h2>
                    <p class="muted">Both business and artist accounts require administrator approval before sign-in. Approving an artist publishes their approved tracks; changing an artist away from Approved hides their tracks immediately.</p>
                </div>
                <div class="filter-bar" data-filter-group="accounts">
                    <button type="button" class="filter-pill active" data-filter="all">All (<?= count($members) ?>)</button>
                    <button type="button" class="filter-pill" data-filter="Pending">Pending (<?= count($pendingAccounts) ?>)</button>
                    <button type="button" class="filter-pill" data-filter="Approved">Approved</button>
                    <button type="button" class="filter-pill" data-filter="Rejected">Rejected</button>
                    <button type="button" class="filter-pill" data-filter="business">Businesses</button>
                    <button type="button" class="filter-pill" data-filter="artist">Artists</button>
                </div>
            </div>

            <div class="panel">
                <?php if (!$members): ?>
                    <div class="empty">No business or artist accounts registered yet.</div>
                <?php endif; ?>
                <?php foreach ($members as $m): ?>
                    <div class="account-row" data-filter-item="accounts" data-status="<?= e($m['account_status']) ?>" data-role="<?= e($m['role']) ?>">
                        <div class="account-meta">
                            <div class="account-title">
                                <b><?= e($m['name']) ?></b>
                                <span class="role-chip role-<?= e($m['role']) ?>"><?= e(ucfirst($m['role'])) ?></span>
                                <?= status_badge($m['account_status']) ?>
                            </div>
                            <small>
                                <?= e($m['email']) ?>
                                <?= $m['category'] ? ' · ' . e($m['category']) : '' ?>
                                <?= $m['state'] ? ' · ' . e($m['state']) : '' ?>
                                · Registered <?= e($m['created_at']) ?>
                            </small>
                        </div>
                        <form method="post" class="account-review-form">
                            <?= hidden('account-review') ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <label class="sr-only" for="account-<?= (int) $m['id'] ?>">Decision</label>
                            <select name="account_status" id="account-<?= (int) $m['id'] ?>">
                                <?= select_options(['Pending', 'Approved', 'Rejected'], $m['account_status']) ?>
                            </select>
                            <button class="button primary small">Save decision</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 3: TRACK REVIEWS -->
        <div class="tab-pane <?= $tab === 'reviews' ? 'active' : '' ?>" data-tab-pane="reviews" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">ARTIST INTAKE &amp; VERIFICATION</div>
                    <h2>Track Submission Reviews</h2>
                    <p class="muted">Listen to each uploaded file and verify rights, audio quality and metadata before approving. A track goes live to businesses once both the submission and the artist’s account are Approved.</p>
                </div>
                <div class="filter-bar" data-filter-group="reviews">
                    <button type="button" class="filter-pill active" data-filter="all">All (<?= count($submissions) ?>)</button>
                    <button type="button" class="filter-pill" data-filter="Pending">Pending (<?= count($pendingSubmissions) ?>)</button>
                    <button type="button" class="filter-pill" data-filter="Approved">Approved</button>
                    <button type="button" class="filter-pill" data-filter="Needs changes">Needs changes</button>
                    <button type="button" class="filter-pill" data-filter="Rejected">Rejected</button>
                </div>
            </div>

            <?php if (!$submissions): ?>
                <div class="panel empty">No artist track submissions yet.</div>
            <?php endif; ?>
            <?php foreach ($submissions as $s): ?>
                <div class="panel review-card" data-filter-item="reviews" data-status="<?= e($s['status']) ?>">
                    <div class="review-heading">
                        <div>
                            <h3><?= e($s['title']) ?> <span class="muted">by <?= e($s['artist_name']) ?></span></h3>
                            <p class="muted">
                                <?= e($s['email']) ?> · State: <?= e($s['state']) ?> · Claimed genre: <b><?= e($s['genre']) ?></b>
                                · <?= number_format($s['size'] / 1048576, 2) ?> MB · <?= e($s['created_at']) ?>
                            </p>
                            <?php if ($s['description']): ?>
                                <p class="track-desc"><?= e($s['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?= status_badge($s['status']) ?>
                    </div>
                    <audio controls preload="none" src="stream.php?submission=<?= (int) $s['id'] ?>"></audio>
                    <form method="post" class="form-grid review-form">
                        <?= hidden('review') ?>
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <label>Verified genre
                            <select name="genre"><?= select_options(genres(), $s['verified_genre'] ?: $s['genre']) ?></select>
                        </label>
                        <label>Venue atmosphere
                            <select name="mood"><?= select_options(moods(), $s['mood'] ?: 'Balanced') ?></select>
                        </label>
                        <label>Review decision
                            <select name="status"><?= select_options(['Approved', 'Needs changes', 'Rejected'], $s['status'] === 'Pending' ? 'Approved' : $s['status']) ?></select>
                        </label>
                        <label>Reviewer note (visible to artist)
                            <input name="note" maxlength="1000" value="<?= e($s['note']) ?>" placeholder="Optional feedback or approval note">
                        </label>
                        <div class="wide checks">
                            <label class="check"><input type="checkbox" name="rights_check" <?= $s['status'] === 'Approved' ? 'checked' : '' ?>> Rights declaration checked</label>
                            <label class="check"><input type="checkbox" name="quality_check" <?= $s['status'] === 'Approved' ? 'checked' : '' ?>> Audio playback checked</label>
                            <label class="check"><input type="checkbox" name="metadata_check" <?= $s['status'] === 'Approved' ? 'checked' : '' ?>> Artist &amp; genre metadata checked</label>
                        </div>
                        <div class="wide">
                            <button class="button primary small">Save track decision ↗</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TAB 4: CATALOG -->
        <div class="tab-pane <?= $tab === 'catalog' ? 'active' : '' ?>" data-tab-pane="catalog" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">CATALOG CLASSIFICATION</div>
                    <h2>Tracks &amp; Visibility</h2>
                    <p class="muted">Reclassify genre or mood and toggle whether a track is visible in business recommendations and playlists.</p>
                </div>
            </div>
            <div class="panel">
                <?php foreach ($catalog as $t): ?>
                    <form method="post" class="catalog-form">
                        <?= hidden('track') ?>
                        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                        <div>
                            <b><?= e($t['title']) ?> · <?= e($t['artist']) ?></b>
                            <small><?= e($t['state']) ?><?= ((int) $t['is_demo']) ? ' · Seed demo entry (no audio)' : ' · Uploaded audio track' ?></small>
                        </div>
                        <select name="genre" aria-label="Verified genre"><?= select_options(genres(), $t['genre']) ?></select>
                        <select name="mood" aria-label="Mood"><?= select_options(moods(), $t['mood']) ?></select>
                        <label class="check"><input type="checkbox" name="published" <?= (int) $t['published'] ? 'checked' : '' ?>> Visible</label>
                        <button class="button outline small">Update</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 5: PLAYLISTS -->
        <div class="tab-pane <?= $tab === 'playlists' ? 'active' : '' ?>" data-tab-pane="playlists" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">EDITORIAL CURATION</div>
                    <h2>Curated Business Playlists</h2>
                    <p class="muted">Create and manage playlists assigned to specific business categories or all businesses.</p>
                </div>
            </div>
            <div class="two-col-grid">
                <div class="panel">
                    <h3><?= $edit ? 'Edit playlist: ' . e($edit['name']) : 'Create a new playlist' ?></h3>
                    <form method="post" class="form-grid">
                        <?= hidden('playlist') ?>
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                        <label>Playlist title
                            <input name="name" required maxlength="150" value="<?= e($edit['name'] ?? '') ?>" placeholder="e.g. Morning Café Highlife &amp; Soul">
                        </label>
                        <label>Target business category
                            <select name="category"><?= select_options(array_merge(['All businesses'], categories()), $edit['category'] ?? 'All businesses') ?></select>
                        </label>
                        <label class="wide">Description
                            <input name="description" maxlength="500" value="<?= e($edit['description'] ?? '') ?>" placeholder="What atmosphere or time of day is this playlist built for?">
                        </label>
                        <div class="wide">
                            <b class="field-title">SELECT CATALOG TRACKS</b>
                            <div class="track-checks">
                                <?php foreach ($catalog as $t): ?>
                                    <label class="check">
                                        <input type="checkbox" name="tracks[]" value="<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], $selected, true) ? 'checked' : '' ?>>
                                        <span><?= e($t['title'] . ' · ' . $t['artist']) ?> <small>(<?= e($t['genre']) ?><?= (int) $t['published'] ? '' : ' · hidden' ?>)</small></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="wide actions">
                            <button class="button primary small"><?= $edit ? 'Update playlist ↗' : 'Create playlist ↗' ?></button>
                            <?php if ($edit): ?>
                                <a class="button outline small" href="<?= e(url('admin', ['tab' => 'playlists'])) ?>">Cancel edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div>
                    <?php if (!$pls): ?>
                        <div class="panel empty">No playlists created yet. Use the form on the left to build your first business playlist.</div>
                    <?php endif; ?>
                    <?php foreach ($pls as $pl): ?>
                        <div class="panel admin-playlist">
                            <div>
                                <span class="tag-pill"><?= e($pl['category']) ?></span>
                                <h3><?= e($pl['name']) ?></h3>
                                <p class="muted"><?= e($pl['description'] ?: 'No description') ?></p>
                            </div>
                            <div class="inline-actions">
                                <a class="button outline small" href="<?= e(url('admin', ['tab' => 'playlists', 'edit' => (int) $pl['id']])) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this playlist?')">
                                    <?= hidden('playlist-delete') ?>
                                    <input type="hidden" name="id" value="<?= (int) $pl['id'] ?>">
                                    <button class="button outline small">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- TAB 6: MESSAGES -->
        <div class="tab-pane <?= $tab === 'inquiries' ? 'active' : '' ?>" data-tab-pane="inquiries" role="tabpanel">
            <div class="pane-header">
                <div>
                    <div class="eyebrow">INBOUND CONTACT</div>
                    <h2>Messages from Businesses &amp; Artists</h2>
                    <p class="muted">Inquiries submitted through the public Contact forms.</p>
                </div>
            </div>
            <div class="panel">
                <?php if (!$inquiries): ?>
                    <div class="empty">No contact messages received yet.</div>
                <?php endif; ?>
                <?php foreach ($inquiries as $i): ?>
                    <div class="message">
                        <div class="account-title">
                            <b><?= e($i['name']) ?></b>
                            <span class="tag-pill"><?= e($i['role']) ?></span>
                            <small><?= e($i['email']) ?> · <?= e($i['created_at']) ?></small>
                        </div>
                        <p><?= nl2br(e($i['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 7: AUDIT HISTORY & SECURITY -->
        <div class="tab-pane <?= $tab === 'audit' ? 'active' : '' ?>" data-tab-pane="audit" role="tabpanel">
            <div class="two-col-grid">
                <div>
                    <div class="panel">
                        <h3>Account approval log</h3>
                        <?php if (!$accountLogs): ?>
                            <div class="empty">No account decisions recorded yet.</div>
                        <?php endif; ?>
                        <?php foreach ($accountLogs as $al): ?>
                            <div class="message">
                                <b><?= e($al['member_name']) ?> (<?= e($al['member_role']) ?>) → <?= status_badge($al['decision']) ?></b>
                                <small>Reviewed by <?= e($al['admin_name']) ?> · <?= e($al['created_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="panel" style="margin-top:20px">
                        <h3>Track review log</h3>
                        <?php if (!$logs): ?>
                            <div class="empty">No track review decisions recorded yet.</div>
                        <?php endif; ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="message">
                                <b><?= e($log['title']) ?> → <?= status_badge($log['decision']) ?></b>
                                <small><?= e($log['reviewer']) ?> · <?= e($log['created_at']) ?> · <?= e($log['verified_genre']) ?> · <?= e($log['mood']) ?></small>
                                <?php if ($log['note']): ?><p><?= e($log['note']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php password_form(); ?>
            </div>
        </div>
    </section>
<?php endif; ?>
</main>

<?php if (!in_array($p, ['artist-dashboard', 'business-dashboard', 'admin'], true)): ?>
    <?php footer_public(); ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('[data-tab-target]');
    const tabPanes = document.querySelectorAll('[data-tab-pane]');

    function activateTab(tabName) {
        tabButtons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab-target') === tabName);
        });
        tabPanes.forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-tab-pane') === tabName);
        });
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-tab-target'));
        });
    });

    document.querySelectorAll('[data-jump-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-jump-tab'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    document.querySelectorAll('[data-filter-group]').forEach(function (bar) {
        const group = bar.getAttribute('data-filter-group');
        const pills = bar.querySelectorAll('[data-filter]');
        const items = document.querySelectorAll('[data-filter-item="' + group + '"]');
        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                const filter = pill.getAttribute('data-filter');
                pills.forEach(function (p) { p.classList.toggle('active', p === pill); });
                items.forEach(function (item) {
                    const match = filter === 'all'
                        || item.getAttribute('data-status') === filter
                        || item.getAttribute('data-role') === filter;
                    item.style.display = match ? '' : 'none';
                });
            });
        });
    });
});
</script>
</body>
</html>

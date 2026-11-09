<?php
declare(strict_types=1);

function radiance_logo_svg(int $size = 34): string
{
    return '<svg class="radiance-wave" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" fill="none" aria-hidden="true">'
        . '<circle cx="4" cy="24" r="2.3" fill="#E5631A"/>'
        . '<rect x="9" y="16" width="3.6" height="16" rx="1.8" fill="#E5631A"/>'
        . '<rect x="15" y="10" width="3.6" height="28" rx="1.8" fill="#E8701E"/>'
        . '<rect x="21" y="5" width="3.8" height="38" rx="1.9" fill="#EC8423"/>'
        . '<rect x="27" y="12" width="3.6" height="24" rx="1.8" fill="#F09C2B"/>'
        . '<rect x="33" y="8" width="3.6" height="32" rx="1.8" fill="#F2AE2E"/>'
        . '<rect x="39" y="17" width="3.6" height="14" rx="1.8" fill="#F4BE35"/>'
        . '<circle cx="45" cy="24" r="2.2" fill="#F4BE35"/>'
        . '</svg>';
}

function ecosystem_section(): void
{
    ?>
    <section class="ecosystem-section">
        <div class="wrap">
            <div class="section-intro">
                <div>
                    <div class="eyebrow">HOW RADIANCE CONNECTS SOUND &amp; SPACE</div>
                    <h2>Connecting African artists with<br><em>new audiences through local businesses.</em></h2>
                </div>
                <p>Emerging musicians share original recordings for review, and approved local venues stream curated, local-first sounds built for their atmosphere.</p>
            </div>

            <div class="eco-flow">
                <article class="eco-step-card">
                    <div class="eco-pill-header">1. Emerging African artists</div>
                    <div class="eco-card-body">
                        <div class="eco-artists-graphic" aria-hidden="true">
                            <span class="note n1">♪</span>
                            <span class="note n2">♫</span>
                            <span class="note n3">♪</span>
                            <div class="artist-fig teal"><i></i><b></b></div>
                            <div class="artist-fig orange"><i></i><b></b></div>
                            <div class="artist-fig gold"><i></i><b></b></div>
                        </div>
                        <p class="eco-caption">Independent musicians, bands and producers submit tracks for review and curation on Radiance.</p>
                        <?= linkto('submit', 'Submit a track ↗', 'text-link') ?>
                    </div>
                </article>

                <div class="eco-arrow" aria-hidden="true">➔</div>

                <article class="eco-phone-card">
                    <div class="phone-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-logo"><?= radiance_logo_svg(44) ?></div>
                        <div class="phone-brand">
                            <b>Radiance</b>
                            <span>A U D I O</span>
                        </div>
                        <p class="phone-sub">Discover new talent</p>
                        <div class="phone-tracks">
                            <div class="phone-track">
                                <span class="dot orange"></span>
                                <div>
                                    <b>Amara K.</b>
                                    <small>Afrobeats · Lagos</small>
                                </div>
                            </div>
                            <div class="phone-track">
                                <span class="dot teal"></span>
                                <div>
                                    <b>Kofi &amp; The Band</b>
                                    <small>Highlife · FCT</small>
                                </div>
                            </div>
                        </div>
                        <?= linkto('business-register', 'Stream in your venue', 'phone-cta') ?>
                    </div>
                    <div class="eco-step-label">2. Radiance Audio platform</div>
                </article>

                <div class="eco-arrow" aria-hidden="true">➔</div>

                <article class="eco-step-card">
                    <div class="eco-pill-header">3. Local African businesses play their music</div>
                    <div class="eco-card-body">
                        <div class="eco-venues-mini">
                            <div class="venue-tile teal">
                                <b>Unity Café</b>
                                <span class="v-icon">☕</span>
                                <small class="speaker-wave">◉ ))</small>
                            </div>
                            <div class="venue-tile terracotta">
                                <b>Member’s Bar</b>
                                <span class="v-icon">🍸</span>
                                <small class="speaker-wave">◉ ))</small>
                            </div>
                            <div class="venue-tile navy">
                                <b>City Gym</b>
                                <span class="v-icon">🏋️</span>
                                <small class="speaker-wave">◉ ))</small>
                            </div>
                        </div>
                        <p class="eco-caption">Cafés, bars, gyms, salons and shops stream approved local artists tailored to their space.</p>
                        <?= linkto('business', 'Explore for business ↗', 'text-link') ?>
                    </div>
                </article>
            </div>

            <div class="impact-divider"><span>Impact</span></div>

            <div class="benefits-grid">
                <article class="benefit-card navy-card">
                    <div>
                        <div class="eyebrow gold-text">FOR VENUES &amp; BRANDS</div>
                        <h3>Business benefits</h3>
                        <ul class="benefit-list">
                            <li>Better atmosphere shaped around your venue type</li>
                            <li>Stronger customer connection and memorable visits</li>
                            <li>Distinct, local-first sound curated for your setting</li>
                        </ul>
                    </div>
                    <div class="chart-bars" aria-hidden="true">
                        <span class="bar b1"></span>
                        <span class="bar b2"></span>
                        <span class="bar b3"></span>
                        <span class="bar b4"></span>
                    </div>
                </article>

                <article class="benefit-card terracotta-card">
                    <div>
                        <div class="eyebrow gold-text">FOR INDEPENDENT MUSICIANS</div>
                        <h3>Artist benefits</h3>
                        <ul class="benefit-list">
                            <li>More exposure in real everyday spaces</li>
                            <li>Reach local and visiting audiences organically</li>
                            <li>Long-term listener discovery and fan growth</li>
                        </ul>
                    </div>
                    <div class="fan-growth" aria-hidden="true">
                        <span>Audience reach</span>
                        <svg viewBox="0 0 160 80" fill="none">
                            <path d="M8 68 L48 52 L84 60 L148 14" stroke="#FFFDF9" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M124 12 L150 12 L148 38" stroke="#FFFDF9" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </article>
            </div>
        </div>
    </section>
    <?php
}

function journey(string $type): void
{
    $artist = $type === 'artist';
    $steps = $artist ? [
        ['Share your track', 'Submit an original MP3 or WAV recording, your artist details, state, genre and a declaration of rights.'],
        ['Hands-on review', 'An administrator listens to your track and checks your metadata, claimed genre and rights confirmation.'],
        ['Request your artist account', 'Once your track is approved, use your one-time tracking code to request an artist account.'],
        ['Account approval', 'An administrator approves your artist account so you can sign in to your private artist portal.'],
        ['Reach local spaces', 'Approved tracks from approved artists appear in business recommendations and curated playlists.'],
    ] : [
        ['Tell us about your venue', 'Request a business account with your category, state and the atmosphere you want to create.'],
        ['Administrator approval', 'The Radiance team reviews and approves your business account before unlocking workspace access.'],
        ['Explore tailored sounds', 'Sign in to browse tracks matched to your business category, preferred mood and location.'],
        ['Stream curated playlists', 'Play approved recordings and category playlists directly from your business portal.'],
        ['Adapt as your space evolves', 'Update your venue atmosphere or category settings anytime to refresh your recommendations.'],
    ];
    ?>
    <section class="journey-band" id="how">
        <div class="journey-wrap">
            <div class="eyebrow">HOW IT WORKS</div>
            <h2>How Radiance works<br>for <em><?= $artist ? 'independent artists' : 'local businesses' ?>.</em></h2>
            <div class="journey-list">
                <?php foreach ($steps as $i => $step): ?>
                    <article class="journey-card">
                        <span><?= sprintf('%02d', $i + 1) ?></span>
                        <div>
                            <h3><?= e($step[0]) ?></h3>
                            <p><?= e($step[1]) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function business_places(): void
{
    ?>
    <section class="section wrap">
        <?= section_intro(
            'BUILT FOR YOUR SPACE',
            'Music for the places<br><em>people spend time.</em>',
            'Different venues move at different rhythms. Radiance helps you match your soundtrack to your setting.'
        ) ?>
        <div class="venue-grid">
            <article class="venue-showcase teal-border">
                <img src="assets/venue.jpg" alt="A welcoming café">
                <div class="venue-body">
                    <div class="eyebrow teal-text">CAFÉS &amp; COFFEE SHOPS</div>
                    <h3>A warm welcome, all day.</h3>
                    <p>Make morning coffee and afternoon conversations feel right at home with soulful, relaxed or upbeat selections.</p>
                </div>
            </article>
            <article class="venue-showcase orange-border">
                <img src="assets/hero.jpg" alt="Independent musician in studio">
                <div class="venue-body">
                    <div class="eyebrow">BARS, LOUNGES &amp; RETAIL</div>
                    <h3>A sound that fits your brand.</h3>
                    <p>Give boutiques, lounges and social spaces a distinctive local soundtrack that customers remember.</p>
                </div>
            </article>
            <article class="venue-showcase navy-border">
                <img src="assets/studio.jpg" alt="Recording artist">
                <div class="venue-body">
                    <div class="eyebrow">GYMS, HOTELS &amp; HOSPITALITY</div>
                    <h3>Set the pace for every session.</h3>
                    <p>Switch seamlessly between focused, relaxed, balanced or upbeat atmospheres tailored to your guests.</p>
                </div>
            </article>
        </div>
    </section>
    <?php
}

function audience_faq(string $type): void
{
    $qs = $type === 'artist' ? [
        ['Who can submit music?', 'Independent artists, bands and producers may submit recordings they own or have permission to share.'],
        ['What happens after I submit a song?', 'Save the private tracking code shown after submission. Once an administrator approves your track, use that code to request your artist account.'],
        ['Do artist accounts require approval?', 'Yes. Both your initial track submission and your artist account request are reviewed and approved by an administrator before sign-in and catalog publication.'],
        ['How do rights and royalties work?', 'This platform MVP handles track review and curation. Commercial licensing and royalty payout terms must be arranged separately before commercial deployment.'],
    ] : [
        ['Which businesses can join Radiance Audio?', 'Cafés, restaurants, bars, lounges, gyms, salons, retail boutiques, hotels, offices and other local venues.'],
        ['How is my business account approved?', 'After you submit the registration form, your account starts as Pending until an administrator approves it in the admin portal.'],
        ['How are music recommendations chosen?', 'Tracks are ranked using your business category, preferred mood and state, alongside playlists curated by the admin team.'],
        ['Can normal listeners create accounts or playlists?', 'No. Radiance Audio is exclusively for approved businesses and approved artists, with playlists curated by administrators.'],
    ];
    ?>
    <section class="faq-band">
        <div class="wrap faq-layout">
            <div>
                <div class="eyebrow"><?= $type === 'artist' ? 'FOR ARTISTS' : 'FOR BUSINESSES' ?> · FAQ</div>
                <h2>Common questions,<br><em>clear answers.</em></h2>
                <p><?= $type === 'artist' ? 'Everything you need to know before submitting your music.' : 'How account approval, curation and venue playback work.' ?></p>
            </div>
            <div class="faq-list">
                <?php foreach ($qs as [$q, $a]): ?>
                    <details>
                        <summary><?= e($q) ?><span>+</span></summary>
                        <p><?= e($a) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function about_more(): void
{
    ?>
    <section class="split-section">
        <div class="wrap split">
            <div class="photo">
                <img src="assets/venue.jpg" alt="People together in a café">
            </div>
            <div>
                <div class="eyebrow">WHAT WE ARE BUILDING</div>
                <h2>A local-first music experience,<br><em>not generic background noise.</em></h2>
                <p>Every memorable venue has its own energy. Radiance Audio connects independent African artists with the cafés, bars, gyms, salons and shops where communities gather every day.</p>
                <p>Every artist submission and business account goes through administrator review, keeping the catalog focused and curated.</p>
                <div class="actions">
                    <?= linkto('business', 'Explore for business ↗', 'button primary') ?>
                    <?= linkto('artists', 'Explore for artists', 'button outline') ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    ecosystem_section();
    journey('business');
    ?>
    <section class="section wrap">
        <div class="center-cta">
            <div class="eyebrow">JOIN THE RADIANCE NETWORK</div>
            <h2>Ready to bring local sound<br><em>into your space?</em></h2>
            <p>Request a business account or submit your track for review.</p>
            <div class="actions center">
                <?= linkto('business-register', 'Request business account ↗', 'button primary') ?>
                <?= linkto('submit', 'Submit artist track', 'button outline') ?>
            </div>
        </div>
    </section>
    <?php
}

function home_contact(): void
{
    ?>
    <section class="contact-band">
        <div class="wrap contact-grid">
            <div>
                <div class="eyebrow">START A CONVERSATION</div>
                <h2>Tell us about<br><em>your venue or sound.</em></h2>
                <p>Whether you run a local business or create independent music, send a note directly to the Radiance team.</p>
                <?= linkto('contact', 'Open full contact page ↗', 'text-link') ?>
            </div>
            <div class="form-panel">
                <h3>Message the Radiance team</h3>
                <form method="post" class="form-grid">
                    <?= hidden('contact') ?>
                    <label>Your name<input name="name" required maxlength="150"></label>
                    <label>Email address<input name="email" type="email" required maxlength="254"></label>
                    <label class="wide">I am a
                        <select name="role">
                            <option>Business</option>
                            <option>Artist</option>
                            <option>Other</option>
                        </select>
                    </label>
                    <label class="wide">Message<textarea name="message" required maxlength="3000"></textarea></label>
                    <div class="wide"><button class="button primary">Send message ↗</button></div>
                </form>
            </div>
        </div>
    </section>
    <?php
}

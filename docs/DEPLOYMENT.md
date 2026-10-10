# Deployment and launch

A step-by-step guide to putting saidur-rahman.com on a small VPS behind Cloudflare. Everything here was prepared in Phase 9; the files it refers to are in `deploy/`. Plan one evening for the setup and one week of soft launch before the link goes on your CV.

What you need before you start:

- A VPS with 2 vCPU and 4 GB RAM running Ubuntu 24.04 (Hetzner, DigitalOcean or Contabo), with your SSH key on it
- The domain `saidur-rahman.com` on Cloudflare
- A mail provider for sending (Zoho, Brevo or Amazon SES) and a mailbox for `hello@saidur-rahman.com`
- An S3-compatible bucket for backups (Backblaze B2 or Amazon S3)
- The GitHub repository with this code on `main`

If you use Laravel Forge or Ploi instead of the manual steps, they do sections 1, 4 and part of 5 for you. Point them at `main`, give them the deploy script content from `deploy/deploy.sh`, add the queue worker and the scheduler from `deploy/`, and still do the rest.

## 1. Prepare the server

Log in as root once, then work as a normal user.

```bash
# A user for the site, with your SSH key
adduser deploy && usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

# Firewall: SSH, web only. Then turn off password logins in /etc/ssh/sshd_config (PasswordAuthentication no).
ufw allow OpenSSH && ufw allow 'Nginx Full' && ufw enable
apt update && apt install -y unattended-upgrades fail2ban && dpkg-reconfigure -plow unattended-upgrades
timedatectl set-timezone UTC     # leave the server on UTC; the site uses APP_TIMEZONE for display and scheduling
```

Install the software. Ubuntu 24.04 ships PHP 8.3, but this project needs **PHP 8.4** (several dependencies require 8.4.1 or newer), so use the Ondřej Surý repository.

```bash
add-apt-repository -y ppa:ondrej/php && apt update
apt install -y nginx mysql-server supervisor git unzip curl \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-exif php8.4-opcache

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Node 22 (to build the front end)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt install -y nodejs

# Production PHP settings (OPcache, upload size). Copy for both FPM and the command line.
cp /var/www/saidur-rahman/deploy/php-fpm-saidur.ini /etc/php/8.4/fpm/conf.d/99-saidur.ini   # after step 4 clones the repo
cp /var/www/saidur-rahman/deploy/php-fpm-saidur.ini /etc/php/8.4/cli/conf.d/99-saidur.ini
systemctl reload php8.4-fpm
```

Database:

```bash
mysql_secure_installation
mysql -e "CREATE DATABASE saidur_rahman CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
          CREATE USER 'saidur'@'localhost' IDENTIFIED BY 'a-long-random-password';
          GRANT ALL ON saidur_rahman.* TO 'saidur'@'localhost'; FLUSH PRIVILEGES;"
```

Let the `deploy` user reload PHP-FPM without a password (the deploy script needs it once per deploy, nothing else):

```bash
echo 'deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm' > /etc/sudoers.d/deploy-fpm
```

## 2. Cloudflare and DNS

1. Add `saidur-rahman.com` to Cloudflare and point the registrar's nameservers at it.
2. DNS: an `A` record for `@` and one for `www`, both to the server's IP, **proxied** (orange cloud). Add `AAAA` records if the server has IPv6.
3. SSL/TLS: set the mode to **Full (strict)**. Under *Origin Server*, create an Origin Certificate for `saidur-rahman.com` and `*.saidur-rahman.com`; save the certificate and key on the server as `/etc/ssl/cloudflare/saidur-rahman.com.pem` and `.key` (key readable by root only, `chmod 600`).
4. SSL/TLS > Edge Certificates: turn on *Always Use HTTPS*. Leave HSTS to the application, which sends it.
5. Turnstile: create a widget for `saidur-rahman.com` (managed mode). Keep the site key and secret key for `.env`.
6. Optional but recommended for `/admin`: Cloudflare Zero Trust > Access, an application on `saidur-rahman.com/admin*` that allows only your email address. This sits in front of the 2FA login. The alternative is the commented `allow`/`deny` block in `deploy/nginx.conf`.

## 3. Email that reaches inboxes

Contact form notifications and auto-replies are sent from `hello@saidur-rahman.com`. Without these DNS records they land in spam. Your mail provider shows the exact values; the shape is:

| Type | Name | Value (example) |
| --- | --- | --- |
| TXT | `@` | `v=spf1 include:<provider's SPF host> ~all` (one SPF record only; merge providers into it) |
| TXT or CNAME | `<selector>._domainkey` | the DKIM key your provider gives you |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:hello@saidur-rahman.com` (move to `p=quarantine` after a few clean weeks) |

Create the mailbox `hello@saidur-rahman.com` (Zoho Mail or Google Workspace) and the SMTP credentials for sending. Send a test to a Gmail address and check "Show original": SPF, DKIM and DMARC should all say PASS.

## 4. Install the application

As the `deploy` user:

```bash
sudo mkdir -p /var/www && sudo chown deploy:deploy /var/www
cd /var/www
git clone git@github.com:<you>/saidur-rahman.git        # add the server's SSH key as a read-only deploy key on GitHub first
cd saidur-rahman

cp .env.production.example .env
nano .env     # fill in: APP_KEY (php artisan key:generate --show), DB_*, MAIL_*, TURNSTILE_*, BACKUP_*, ADMIN_EMAIL, ADMIN_PASSWORD
```

Every setting in `.env.production.example` has a comment. The important ones: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://saidur-rahman.com`, `APP_TIMEZONE` (yours, so "publish at 09:00" is 09:00 for you), `SESSION_SECURE_COOKIE=true` and `TRUSTED_PROXIES=127.0.0.1`.

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate          # only if you did not put APP_KEY in .env; never run it again afterwards
php artisan migrate --force
php artisan db:seed --force       # first time only: creates the admin user and the starter content. Prints a password if ADMIN_PASSWORD was empty
php artisan storage:link
sudo chown -R deploy:www-data storage bootstrap/cache && sudo chmod -R ug+rwX storage bootstrap/cache
php artisan optimize
php artisan sitemap:generate
```

Remove `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env` after seeding. **Never run `key:generate` on a site that has data**: it makes the encrypted 2FA secrets unreadable. Store `APP_KEY` and the backup password in your password manager.

Web server, queue worker and scheduler:

```bash
# Nginx
cd /var/www/saidur-rahman
sudo ./deploy/cloudflare-ips.sh | sudo tee /etc/nginx/snippets/cloudflare-realip.conf >/dev/null
sudo cp deploy/nginx.conf /etc/nginx/sites-available/saidur-rahman.com
sudo ln -sf /etc/nginx/sites-available/saidur-rahman.com /etc/nginx/sites-enabled/saidur-rahman.com
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Queue worker (sends the contact form emails)
sudo cp deploy/supervisor/saidur-rahman-queue.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status

# Scheduler (scheduled articles, sitemap, backups, heartbeats): paste the line from deploy/crontab.txt
crontab -e
```

Then sign in at `https://saidur-rahman.com/admin/login`. You are sent straight to the two-factor setup: scan the QR code, confirm, and save the recovery codes somewhere safe. Change the password under Password.

## 5. Make it yours, then check it

In the admin, replace every piece of sample content: Profile & settings (name, headline, positioning, bio, photo, share image, LinkedIn, numbers, availability), Experience, Education, Certifications, Skills, at least three Projects with real outcomes, two or three Articles, both CV versions, and the Privacy page. Add alt text to every image.

Then run the checklist as code:

```bash
php artisan launch:check
```

It prints a tick, a warning or a cross for each of about 40 things: environment, database, mail, Turnstile, scheduler and queue heartbeats, backups, 2FA, leftover "Sample" text, number of case studies and articles, CV, portrait and alt text. Crosses block launch; warnings are worth reading. `php artisan launch:check --strict` fails on warnings too. The admin dashboard also shows a warning if the scheduler or queue worker stops.

## 6. Backups

Nightly at 02:15 the site dumps the database and zips it with the uploaded images, the private CV files and `.env`, encrypts the zip and sends it off-site. Backups older than 30 days are deleted at 02:00, and an email arrives only when something fails or no backup is under two days old.

1. Create a private bucket (Backblaze B2: a bucket and an application key limited to it; note the S3 endpoint such as `https://s3.us-west-004.backblazeb2.com` and set `BACKUP_AWS_USE_PATH_STYLE_ENDPOINT=true` if your provider needs it).
2. Fill in the `BACKUP_*` values in `.env`, including `BACKUP_ARCHIVE_PASSWORD` (a long random string; keep it in the password manager, it cannot be recovered).
3. Run one by hand and look at the result: `php artisan backup:run` and `php artisan backup:list`.
4. **Do a restore drill before launch.** A backup you have never restored is only a hope.

   ```bash
   # On a scratch machine or a scratch database, never on the live one first
   ./deploy/restore.sh /path/to/2026-10-09-02-15-00.zip            # unpacks and lists, changes nothing
   ./deploy/restore.sh /path/to/2026-10-09-02-15-00.zip --apply    # loads the database and copies the files back
   ```

   `restore.sh` asks for the archive password, reads the database settings from the `.env` on that machine and makes you type the database name before it overwrites anything. The unpacked `.env` shows the `APP_KEY` the backup was made with; the restored site must use the same one. This script was tested locally against a scratch database.

## 7. Deploying changes

From your laptop: commit, push to `main`, then on the server:

```bash
cd /var/www/saidur-rahman && ./deploy/deploy.sh
```

The script pulls `main`, installs PHP and JS dependencies, builds the front end, runs migrations, rebuilds the Laravel caches, clears the full-page cache, regenerates the sitemap, restarts the queue worker, reloads PHP-FPM and runs `launch:check`. It puts the site in maintenance mode (a short 503 page that retries) while it works and always brings it back.

**To roll back:** `git fetch --tags && git checkout phase-8 && ./deploy/deploy.sh` (any tag or commit), and `git checkout main` when you are ready to move forward again. Database migrations are not undone automatically; for a risky migration take `php artisan backup:run` first.

**Automatic deploys (optional).** `.github/workflows/ci.yml` runs Pint, PHPStan and the tests on every push and pull request. Its `deploy` job runs the script over SSH after the tests pass on `main`, but only when the repository variable `DEPLOY_ENABLED` is `true` and the four `DEPLOY_*` secrets listed in the file exist. That job has not been run against a real server. Give the `production` environment a required reviewer if you want to approve each deploy.

## 8. Go-live checklist

Server

- [ ] `php artisan launch:check` shows no crosses
- [ ] `https://saidur-rahman.com` loads, `http://` and `www` redirect to it, and the padlock is valid (Cloudflare Full strict)
- [ ] Queue worker and scheduler running (`sudo supervisorctl status`; the dashboard shows no warning)
- [ ] A backup ran, landed in the bucket, and a restore drill worked

Email

- [ ] SPF, DKIM and DMARC pass for mail from `hello@saidur-rahman.com`
- [ ] Submitting the contact form from your phone delivers the notification to your inbox and the confirmation to the sender, not to spam

Content

- [ ] Professional portrait and a 1200×630 share image uploaded
- [ ] At least 3 case studies with defensible outcomes, 2 to 3 articles, both CVs, privacy page
- [ ] Facts match your LinkedIn and CV; no "Sample" text anywhere

Quality

- [ ] Lighthouse mobile on the live site: 90 or more in all four categories for Home, one case study and one article (`npx lighthouse https://saidur-rahman.com --form-factor=mobile`). Expect better numbers than local runs because the server compresses files and uses HTTP/2; the target for Largest Contentful Paint is 1.5 s
- [ ] No console errors; every form tested from a phone
- [ ] `https://saidur-rahman.com/robots.txt` allows crawling and names the sitemap; `/sitemap.xml` loads

Search and monitoring

- [ ] Google Search Console and Bing Webmaster Tools verified (a DNS TXT record), sitemap submitted
- [ ] An uptime monitor (UptimeRobot or Better Stack) on `https://saidur-rahman.com/` and `https://saidur-rahman.com/up`, alerting your phone
- [ ] The URL added to your LinkedIn headline and featured section, CV header and email signature, after the soft launch week

## 9. Running it

| Symptom | Check |
| --- | --- |
| Contact emails never arrive | `sudo supervisorctl status` (worker running?), `php artisan queue:failed`, `storage/logs/queue-worker.log`, `MAIL_*` in `.env` |
| Scheduled article did not appear | Cron entry present for the `deploy` user? Dashboard warning? `php artisan schedule:list` |
| A change in the admin is not on the site | It should appear at once. `php artisan responsecache:clear` and `php artisan optimize:clear` if you changed views or config by hand |
| White page or 500 | `storage/logs/laravel-*.log`; never turn on `APP_DEBUG` on the live site |
| Visitors all look like one IP | `TRUSTED_PROXIES` and `deploy/cloudflare-ips.sh` |
| Lost the admin phone | Use a recovery code at the two-factor prompt, then set up 2FA again |
| Lost every recovery code and the phone | On the server: `php artisan tinker`, then `User::first()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();` and sign in again to enrol a new device |

Routine: after each deploy read the `launch:check` summary; once a month check the Search Console coverage report, glance at `php artisan backup:list`, and run `sudo apt update && sudo apt upgrade` (or let unattended upgrades do it). Refresh the Cloudflare IP list every few months with `deploy/cloudflare-ips.sh`. Logs rotate daily and keep 14 days.

## 10. Soft launch week

Go live on the domain first without telling anyone. Over the week: send the contact form to yourself from two devices, read the dashboard each morning (is traffic from your own visits showing, are there errors in the logs), ask one or two trusted people to read the site on their phones and tell you what is unclear, fix the content, then add the link to your CV and LinkedIn.

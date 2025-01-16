# Mailcow Status Pages

I needed a way for my colleagues and clients to easily identify email problems within our mailcow infrastructure. I stumbled upon [hacktisch/mailcow-status-tracker](https://github.com/hacktisch/mailcow-status-tracker) and decided to rewrite it in PHP so that it can be integrated with mailcow, the same way you would integrate roundcube.

I was already using [gutmensch/docker-dmarc-report](https://github.com/gutmensch/docker-dmarc-report), which in turn utilizes [techsneeze/dmarcts-report-parser](https://github.com/techsneeze/dmarcts-report-parser) and [techsneeze/dmarcts-report-viewer](https://github.com/techsneeze/dmarcts-report-viewer/), and I decided to include those as well. Actually; only the parser, I rewrote the parts of the viewer I needed.

## Features

* OAuth2 login via Mailcow (mailbox users only!)
* Configurable Admin / Domain Admin / User roles
* Uses the same mariadb instance as mailcow but a different database
* Fetches all data via API, thus you can host it on a non-mailcow server as well
* Customizable logo and footer
* Configurable retention
* Fully compatible schema with `techsneeze/dmarcts-report-parser` and derivatives, making an easy migration.
* Per mailbox email open tracking (untested!!)

## Install

### In a mailcow server

1. ssh to your server and go to your mailcow directory, e.g. `cd /opt/mailcow-dockerized`

2. Download this project

```bash
git clone https://github.com/rallisf1/mailcow-status-app.git
```

3. Create the database by running the following command.

```bash
source mailcow.conf
DBSTATUS=$(LC_ALL=C </dev/urandom tr -dc A-Za-z0-9 2> /dev/null | head -c 28)
echo Database password for user status is $DBSTATUS
docker exec -it $(docker ps -f name=mysql-mailcow -q) mysql -uroot -p${DBROOT} -e "CREATE DATABASE mailcowstatus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker exec -it $(docker ps -f name=mysql-mailcow -q) mysql -uroot -p${DBROOT} -e "CREATE USER 'status'@'%' IDENTIFIED BY '${DBSTATUS}';"
docker exec -it $(docker ps -f name=mysql-mailcow -q) mysql -uroot -p${DBROOT} -e "GRANT ALL PRIVILEGES ON mailcowstatus.* TO 'status'@'%';"
```

__Note: Store this password, you will need it later.__

4. Copy the contents of the web directory to `/opt/mailcow-dockerized/data/web/status`

```bash
mkdir -m 755 data/web/status
cp -r mailcow-status-app/web/* data/web/status
mv data/web/status/env.example data/web/status/.env
```

5. Copy `docker-compose.override.yml` to `/opt/mailcow-dockerized`. This will allow the cron jobs to run via ofelia.

```bash
cp mailcow-status-app/docker-compose.override.yml .
```

__Note: if you already have a `docker-compose.override.yml` file, manually merge the files.__

6. Append `track.lua` to `data/conf/rspamd/lua/rspamd.local.lua` and copy `trackable.php` to `data/conf/rspamd/dynmaps`

```bash
cat mailcow-status-app/track.lua >> data/conf/rspamd/lua/rspamd.local.lua
cp mailcow-status-app/trackable.php data/conf/rspamd/dynmaps
```

__Note: updating mailcow might overwrite `rspamd.local.lua`. These files only affect open tracking.__

7. Enable the read-only API & add the OAuth2 App in Mailcow

    1. Connect to your mailcow instance as an admin
    2. Navigate to System -> Configuration -> Access -> Administrators and Expand the API section
    3. In the `Allow API access from these IPs/CIDR network notations` add your mailcow server's IP addresses and the docker network ranges `172.22.1.0/24` and `fd4d:6169:6c63:6f77::/64`. Check your `mailcow.conf` for the rare case you've changed them.
    4. Check `Activate API` and click `Save changes`
    5. Copy the `API key` in a notepad to save for later
    6. Navigate to System -> Configuration -> Access -> OAuth2 Apps and click on `+ Add OAuth2 client`
    7. In the `Redirect URI` enter `https://your.mailcow.com/status/callback.php`
    8. Copy the `Client ID` and `Client secret` for the next step.

8. Edit the configuration

    1. `docker-compose.override.yml` with your database password provided in step 3, and your dmarc recipient mailbox's IMAP info
    2. `data/web/status/.env` with your database password provided in step 3, your oauth2 and API credentials, your admins' emails and your customization options

9. Visit `https://your.mailcow.com/status` and login for the tables to be initialized

10. Re-compose the docker containers

```bash
docker compose up -d
```

11. (optional) Add an App link to mailcow:

    1. Connect to your mailcow instance as an admin
    2. Navigate to System -> Configuration -> Options -> Customize
    3. Add a new row to `App Links` with the name you like (e.g. `Status`) and the Link `/status/`


### In another server (or locally) without docker

#### Requirements

1. Linux / BSD / Mac
2. PHP 8.2+
3. any web server
4. MySQL / MariaDB

#### Setup

1. Clone this project and copy the contents of the `web` directory to your web/vhost root, e.g. `/var/www/`
2. Create the `mailcowstatus` database and assign a `status` user. Save the password for later.
3. Install [techsneeze/dmarcts-report-parser](https://github.com/techsneeze/dmarcts-report-parser) and configure it to use the database you created and the IMAP account where the DMARC reports come in
4. Do steps 6 and 7 from the mailcow installation above on your mailcow server. Use the IPs of your web server in step 8.3
5. Edit `.env` with your database password, your oauth2 and API credentials, your admins' emails and your customization options
6. Visit the web app and log in for the database tables to get created
7. Set up the cron jobs: DMARC reports every 6 hours, mail logs every 5 minutes, prune daily at 2am. Adjust as you wish.

```
0 */6 * * * sh /path/to/the/dmarcts-report-parser.pl -i >/dev/null 2>&1
*/5 * * * * wget -O - -q http://127.0.0.1/cron/update.php?key=YOUR_CRON_KEY >/dev/null 2>&1
0 2 * * * * wget -O - -q http://127.0.0.1/cron/prune.php?key=YOUR_CRON_KEY >/dev/null 2>&1
```

## How to use Open Tracking

For __open tracking__ to work you need to add the custom attribute `OPEN_TRACKING` with a value of `YES` (in mailbox settings -> Custom attributes) and save

__repeat for any mailboxes you wish to enable this__

## Features under consideration

* A dashboard page with stats and charts
* Multi-server operation, for postfix & rspamd logs. DMARC data can already be collected from multiple (even non mailcow) servers, as this depends to the dmarc dns record on your domains.
* Notification hooks/emails for certain problems
* Change Open Tracking trigger from a custom mailbox attribute to each message's `Priority` header, thus making it a per message feature rather than a per mailbox (although personally I need to be able to control it per mailbox)
* Use mailcow's theme
* Create an install.sh script, for installing in mailcow servers only.
* Use cookies for auth

Please open an issue with the feature you'd like (if one doesn't already exist). PRs are also welcome.

## Tech used

* Vanilla PHP with
    - symfony/http-client
    - vlucas/phpdotenv
    - jwilsson/oauth2-client
* Flyonui for styling
    - based on DaisyUI, based on tailwindCSS

## FAQ

### Some messages or message info are missing from the database.

Sadly, the Mailcow API returns the last X log records regardless of time and continuity. The default settings should cover 150 active mailbox users on a busy day. Adjust the `POSTFIX_COUNT`, `RSPAMD_COUNT` and cron frequency to mitigate for your load.

### What's with the half baked pagination, no sorting etc.?

Counting rows in SQL on every query is wasting too many resources. Keep in mind this was intended to run on low power email servers that also run the whole of Mailcow.

Normally one would want to see the latest logs, or search for something specific. Since search works fine, and you get 50 results per page with back and forth buttons there's no need to add complexity. If you think otherwise feel free to fork it.

### Can I use this without the DMARC reporting?

Although not advised, unless you use some other tool already, yes.

#### In a mailcow server

- Remove the dmarc-parser section in `docker-compose.override.yml`
- Skip step 9.1

#### In another server

- Skip step 3
- Skip the `dmarcts-report-parser` line in step 7

### When clicking the Dmarc tab I get errors about the database tables

Either you are using this without DMARC reporting (as described above), or its cron hasn't run yet, or it resulted in errors. The default is 6 hours, and its tables get created the first time it runs successfully.

### Isn't email open tracking a privacy concern?

Yes, it normally is. But we're not saving any personal information here. Both the recipient's IP and agent info are one-way hashed into a fingerprint just to let us know whenever a different client opens the same email. The nginx logs are rotated regularly enough in the mailcow instance.

To fully be GDPR compliant though we need to tell the users they are being tracked, even anonymously. That's what the `TRACKER_NOTICE` environment variable does in `docker-compose.override.yml` does, when set to 1.

### Open tracking either fails to register multiple recipients or registers the same recipient multiple times.

This app uses a very basic fingerprinting method, which combines just the user's IP and user agent (a.k.a. web/mail client). If multiple recipients have the same IP and exactly the same client version, it will only register once for them. If the same recipient opens the same massage from multiple IPs or clients, it will register once for each combination. It's not perfect, but it's the least instrusive and easiest to implement. If you have a better idea PRs are welcome.

### How do I backup and restore?

In a mailcow instance using `helper-scripts/backup_and_restore.sh` will include this app as well. In another server you'd need the database dump and the 2 configuration files: `.env` and `dmarcts-report-parser.conf`, and the `docker-compose.override.yml` if you customized it.

### Shouldn't this be a part of Mailcow?

Sure, but that's up to the Mailcow developers. I'd be happy to convert the code and open a PR.

## Special thanks to

* hacktisch for [hacktisch/mailcow-status-tracker](https://github.com/hacktisch/mailcow-status-tracker)'s inspiration
* gutmensch for [gutmensch/docker-dmarc-report](https://github.com/gutmensch/docker-dmarc-report), which I used until recently
* techsneeze for both the [techsneeze/dmarcts-report-parser](https://github.com/techsneeze/dmarcts-report-parser) and [techsneeze/dmarcts-report-viewer](https://github.com/techsneeze/dmarcts-report-viewer/)
* mailcow for their incredible [mail server solution](https://github.com/mailcow/mailcow-dockerized)
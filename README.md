# Spottlab connector for WordPress

Connects your WordPress site to [Spottlab](https://spottlab.com), so you hear as soon as it goes down, needs an update or is at risk. It answers only Spottlab, holding your site's token, and changes nothing on the site. The one thing it does besides reading is the email check below, and only when you ask Spottlab for it.

## Install

It needs WordPress 5.8 and PHP 7.2 or newer. From 1.6.1 the plugin says so in its header, so WordPress will not install it, or update to it, on an older PHP.

1. Download `spottlab-connector.zip` from the [latest release](https://github.com/Spottlab/wordpress-connector/releases/latest), or from the Connectors page in Spottlab.
2. In WordPress, open Plugins → Add New Plugin → Upload Plugin, choose the zip and activate it.
3. Open Settings → Spottlab connector and paste the token from your site's page in Spottlab.

With Composer, as in a Bedrock project, instead of the zip:

```sh
composer require spottlab/wordpress-connector
```

It is a `wordpress-plugin` package, so `composer/installers` (which Bedrock has) puts it in the plugins directory, under the plugin's own folder name, `web/app/plugins/spottlab-connector`, whatever the package is called. Activate it and paste the token as above. Versions are this repository's releases, the same ones the zip comes from.

## Updates

From 1.3.0, new versions show on the Plugins page like any other plugin's, and update on their own if you turn on auto-updates for it. They come from this repository's releases and nowhere else.

Each release is signed with Spottlab's release key, which is kept off GitHub and off Spottlab's servers. The plugin offers a release only if its `update.json` is signed with that key, and installs it only if the download is the exact file that was signed. So neither someone who gets into this repository nor someone who gets into Spottlab can get code onto your site.

WordPress does not update a plugin by itself when:

- **it is a must-use plugin** (in `wp-content/mu-plugins`), or `DISALLOW_FILE_MODS` is set, as with Bedrock: replace `spottlab-connector.php` with the new one;
- **you deploy WordPress from git:** update the file in your repository, or your next deploy will put the old one back.

## Plugin and theme updates

Spottlab lists the plugin and theme updates WordPress itself knows of. That list is only as current as WordPress's last check with WordPress.org. WP-Cron runs that check twice a day, and not at all where cron doesn't run. From 1.4.0, when Spottlab asks for its hourly report and WordPress's last check is more than two hours old, the plugin has WordPress check again first, as Dashboard → Updates does.

If WordPress.org can't be reached, the report goes out as before: the same plugin and theme versions, the updates WordPress already knew of, and the reason the check failed. The check gets ten seconds at most, and nothing Spottlab sends can make it run more often.

## WooCommerce

From 1.5.0, on a site where WooCommerce is active, the hourly report also says how the shop is running, so Spottlab can tell you when the site is up but nobody can buy. There is nothing to switch on.

It reports counts, times and settings only:

- when the last order came through, and how many came through or failed in the last day (the failed ones counted by payment gateway);
- how many orders are pending payment, on hold or processing, and when the oldest in each was placed;
- which payment gateways are switched on, by id, and which say they are in test mode;
- whether WooCommerce's database update is waiting, whether the shop is in "coming soon" mode, and which of its shop, cart, checkout and account pages are missing;
- how many scheduled actions are over an hour late or failed in the last day, and how many of the shop's webhooks WooCommerce has switched off;
- how many products are out of stock or low on it, where the shop keeps stock;
- the file names of the theme's WooCommerce templates that are out of date.

It never reports an order itself: not what was bought, what it came to, or who bought it. Like the rest of the report, it only reads.

Test mode is asked of each gateway in the usual ways gateways keep that setting, so a gateway that keeps it some other way is not noticed. A live gateway is never reported as testing.

## Email check

From 1.6.0, the site can send a test email when Spottlab asks for one, so you hear when it stops sending email: an SMTP password or API key that expired, or a mail server that refuses the site. Spottlab asks only for Pro sites where you set up a workflow with the "Emails not sending" trigger: once a day, and hourly while sending fails.

The plugin sends the email with `wp_mail()`, through whatever sends your site's email (WP Mail SMTP, FluentSMTP, PHP's `mail()` and so on), and tells Spottlab whether WordPress handed it on, and the error if not. It shows in your SMTP plugin's email log like any other email.

What Spottlab can ask for is fixed in the plugin:

- the email always goes to `smtp-check@spottlab.com`, written in the plugin and never taken from the request;
- the subject and text are the plugin's own. The only thing Spottlab sends is a check code of 32 hex digits, put in the text and in an `X-Spottlab-Check` header;
- the plugin sends at most one in 50 minutes, however often it is asked.

So even someone who got into Spottlab could only make your site send that one email, to Spottlab, a little more than once an hour. "Handed on" is all this check knows: it cannot see whether the email arrived. With PHP's `mail()`, WordPress hands every email on, so a site without an SMTP plugin passes even when its email goes nowhere.

## Removing it

From 1.6.1, deleting the plugin (Plugins → Delete, or `wp plugin uninstall`) removes what it kept in the database: the token above all, so that installing it again later, or from a backup of the files, needs a token pasted again. With it go when it last sent a test email, the sizes it measured, and what it last heard of the update lists and of its own releases, on every site of a network. Deactivating it keeps them all. A must-use copy is removed by deleting the file, which WordPress does not see, so remove `health_check_api_token` from the options table yourself, or change the site's token in Spottlab.

## Checking a release yourself

Each release has `spottlab-connector.zip` and `update.json`. With both in one folder:

```sh
php -r '
$u = json_decode(file_get_contents("update.json"), true);
$ok = hash_file("sha256", "spottlab-connector.zip") === $u["sha256"]
  && sodium_crypto_sign_verify_detached(base64_decode($u["signature"]), "spottlab-connector\n{$u["version"]}\n{$u["sha256"]}", base64_decode("p6N6HwMxnnOm1kMDhDT/uTJVQrG5bdkIrN2bVdnL2Qk="));
echo $ok ? "Signed by Spottlab\n" : "NOT signed by Spottlab\n";'
```

The key at the end is the `UPDATE_KEY` in `spottlab-connector.php`.

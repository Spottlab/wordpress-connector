# Spottlab connector for WordPress

Connects your WordPress site to [Spottlab](https://spottlab.com), so you hear as soon as it goes down, needs an update or is at risk. It answers only Spottlab, holding your site's token, and changes nothing on the site.

## Install

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

It never reports an order itself: not what was bought, what it came to, or who bought it. Like everything else here, it only reads.

Test mode is asked of each gateway in the usual ways gateways keep that setting, so a gateway that keeps it some other way is not noticed. A live gateway is never reported as testing.

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

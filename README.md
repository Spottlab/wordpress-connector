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

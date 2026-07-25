# Lightsail Performance Guide

This guide covers recommended server-side optimizations for WordPress sites hosted on an Amazon Lightsail instance, to be used alongside FrontPup and Amazon CloudFront. These changes improve browser and CDN caching, reduce origin load, and help your site achieve better PageSpeed/Lighthouse scores.

## Enable the Apache `mod_expires` Module

The Apache `mod_expires` module allows your server to send `Expires` and `Cache-Control: max-age` headers for static assets (images, CSS, JavaScript, fonts, etc.). This tells browsers and CloudFront how long they can cache a file before re-checking with the origin, which reduces repeat requests and speeds up page loads for returning visitors.

### Steps

1. **Connect to your Lightsail instance via SSH**
   - In the [Lightsail console](https://lightsail.aws.amazon.com/), go to your instance.
   - Select the **Connect** tab, then click **Connect using SSH** to open a browser-based terminal.
   - Alternatively, connect from your local machine using the downloaded default key:
     ```
     ssh -i LightsailDefaultKey.pem bitnami@<your-instance-public-ip>
     ```

2. **Enable the module**
   ```
   sudo a2enmod expires
   ```
   > Note: If your instance uses the Bitnami WordPress stack, Apache is managed by Bitnami's helper scripts rather than `a2enmod`/`a2ensite`. If the command above returns "command not found," see [Bitnami note](#bitnami-stack-note) below.

3. **Confirm (or add) the caching rules**
   Check whether `mod_expires` directives already exist in your Apache or virtual host config (commonly `/etc/apache2/apache2.conf`, `/etc/apache2/sites-available/000-default.conf`, or `/opt/bitnami/apache2/conf/httpd.conf` on Bitnami). If they're missing, add a block like this inside the appropriate `<Directory>` or `<VirtualHost>` section:
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/jpg "access plus 1 year"
       ExpiresByType image/jpeg "access plus 1 year"
       ExpiresByType image/png "access plus 1 year"
       ExpiresByType image/gif "access plus 1 year"
       ExpiresByType image/webp "access plus 1 year"
       ExpiresByType image/svg+xml "access plus 1 year"
       ExpiresByType image/x-icon "access plus 1 year"
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType text/javascript "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
       ExpiresByType application/x-javascript "access plus 1 month"
       ExpiresByType font/woff "access plus 1 year"
       ExpiresByType font/woff2 "access plus 1 year"
       ExpiresByType application/font-woff "access plus 1 year"
       ExpiresByType text/html "access plus 0 seconds"
   </IfModule>
   ```

   **Alternative: `.htaccess`**
   If you don't have access to the main Apache config, or your host's `<Directory>` block has `AllowOverride All` (or at least `AllowOverride FileInfo`) set for your WordPress root, you can add the same rules to the `.htaccess` file in your WordPress install directory instead:
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/jpg "access plus 1 year"
       ExpiresByType image/jpeg "access plus 1 year"
       ExpiresByType image/png "access plus 1 year"
       ExpiresByType image/gif "access plus 1 year"
       ExpiresByType image/webp "access plus 1 year"
       ExpiresByType image/svg+xml "access plus 1 year"
       ExpiresByType image/x-icon "access plus 1 year"
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType text/javascript "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
       ExpiresByType application/x-javascript "access plus 1 month"
       ExpiresByType font/woff "access plus 1 year"
       ExpiresByType font/woff2 "access plus 1 year"
       ExpiresByType application/font-woff "access plus 1 year"
       ExpiresByType text/html "access plus 0 seconds"
   </IfModule>
   ```
   `.htaccess` changes take effect on the next request — no `apachectl configtest` or Apache restart is required. This is more convenient, but slightly slower per-request than server config since Apache re-reads `.htaccess` on every hit.

4. **Test the configuration before restarting**
   ```
   sudo apachectl configtest
   ```
   You should see `Syntax OK`. If there are errors, fix them before proceeding so you don't take the site down.

5. **Restart Apache**
   ```
   sudo systemctl restart apache2
   ```
   > On Bitnami stacks, use `sudo /opt/bitnami/ctlscript.sh restart apache` instead (see below).

6. **Verify the headers are being sent**
   From your local machine, check the response headers for a static asset:
   ```
   curl -I https://your-domain.com/wp-content/themes/your-theme/style.css
   ```
   Look for `Cache-Control` and/or `Expires` in the response.

7. **Update your firewall/networking rules if needed**
   Confirm your Lightsail instance's **Networking** tab still allows HTTP (80) and HTTPS (443) traffic before and after the restart — a restart itself won't change firewall rules, but it's worth a quick sanity check if the site becomes unreachable.

### Bitnami Stack Note

Many Lightsail "WordPress" blueprints run on the Bitnami stack, where Apache lives under `/opt/bitnami/apache2` and isn't managed by Debian's `a2enmod`/`a2ensite` tooling. On these instances:

- `mod_expires` is typically already compiled in and just needs to be uncommented in `/opt/bitnami/apache2/conf/httpd.conf`:
  ```
  LoadModule expires_module modules/mod_expires.so
  ```
- Restart Apache with:
  ```
  sudo /opt/bitnami/ctlscript.sh restart apache
  ```
- Confirm which stack you're on with:
  ```
  sudo /opt/bitnami/ctlscript.sh status
  ```
  If this command doesn't exist, you're likely on a standard Ubuntu/Debian AMI and the `a2enmod`/`systemctl` instructions above apply directly.

## Enable gzip Compression (`mod_deflate`)

The Apache `mod_deflate` module compresses text-based responses (HTML, CSS, JavaScript, JSON, SVG, etc.) before they leave the server. Smaller responses mean faster downloads for visitors and less data for CloudFront to pull from your origin.

> The steps below are for the current native Lightsail "WordPress" blueprint, which runs on a standard Debian/Ubuntu Apache install (`apt`/`a2enmod`/`systemctl`). AWS is phasing out the older Bitnami-based blueprints in favor of these, so new instances should follow this path. If you're still on a Bitnami instance, see the [Bitnami note](#bitnami-stack-note) — `mod_deflate` is already compiled in and only needs to be uncommented in `httpd.conf`, following the same pattern shown there for `mod_expires`.

### Steps (native/non-Bitnami blueprints)

1. **Connect to your Lightsail instance via SSH** (see the [mod_expires steps](#steps) above).

2. **Update your package index**
   ```
   sudo apt update
   ```

3. **Ensure Apache and `mod_deflate` are installed**
   `mod_deflate` ships as part of the core `apache2` package, so a plain install (or reinstall) is all that's needed to make it available:
   ```
   sudo apt install apache2
   ```

4. **Enable the module**
   ```
   sudo a2enmod deflate
   ```

5. **Confirm (or add) the compression rules**
   Check whether `mod_deflate` directives already exist (commonly in `/etc/apache2/mods-available/deflate.conf`, or your site's config under `/etc/apache2/sites-available/`). If they're missing, add a block like this:
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
       AddOutputFilterByType DEFLATE application/javascript application/x-javascript
       AddOutputFilterByType DEFLATE application/json application/xml application/rss+xml
       AddOutputFilterByType DEFLATE image/svg+xml
       AddOutputFilterByType DEFLATE font/woff font/woff2 application/font-woff
   </IfModule>
   ```

   **Alternative: `.htaccess`**
   If you don't have access to the main Apache config, or your host's `<Directory>` block has `AllowOverride All` (or at least `AllowOverride FileInfo`) set for your WordPress root, you can add the same rules to the `.htaccess` file in your WordPress install directory instead:
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
       AddOutputFilterByType DEFLATE application/javascript application/x-javascript
       AddOutputFilterByType DEFLATE application/json application/xml application/rss+xml
       AddOutputFilterByType DEFLATE image/svg+xml
       AddOutputFilterByType DEFLATE font/woff font/woff2 application/font-woff
   </IfModule>
   ```
   `.htaccess` changes take effect on the next request — no `apachectl configtest` or Apache restart is required. The module itself (`mod_deflate`) still needs to be enabled server-side via `a2enmod deflate`; `.htaccess` only controls which rules are applied, not which modules are loaded.

6. **Test the configuration before restarting**
   ```
   sudo apachectl configtest
   ```
   You should see `Syntax OK`. If there are errors, fix them before proceeding so you don't take the site down.

7. **Restart Apache**
   ```
   sudo systemctl restart apache2
   ```

8. **Verify compression is working**
   From your local machine, request a page with gzip explicitly accepted and confirm `Content-Encoding: gzip` is returned:
   ```
   curl -H "Accept-Encoding: gzip" -I https://your-domain.com/
   ```

## Additional Recommendations

- **Set explicit `Cache-Control` headers via FrontPup** for HTML responses (`max-age`/`s-maxage`) so CloudFront and browsers cache pages appropriately, complementing the static-asset caching from `mod_expires`.
- **Enable OPcache** for PHP to cache compiled PHP bytecode and reduce CPU load on the instance. On Bitnami, this is typically already enabled; verify with `php -i | grep opcache.enable`.
- **Use a persistent object cache** (e.g., Redis) if your Lightsail plan and traffic warrant it, to reduce database load for dynamic pages.
- **Right-size your Lightsail instance plan** — if CPU credits are frequently exhausted (visible in the Lightsail **Metrics** tab), consider upgrading your plan or moving to a burstable/unlimited bundle.
- **Keep PHP, Apache/Bitnami, and WordPress core/plugins up to date** to benefit from ongoing performance and security improvements.
- **Invalidate CloudFront cache after config changes** using FrontPup's Clear Cache option so cached responses reflect your new headers.

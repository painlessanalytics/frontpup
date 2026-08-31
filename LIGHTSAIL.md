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

## Scaling Options

> **Note:** AWS is phasing out Bitnami-based Lightsail blueprints. Newer Lightsail "WordPress" instances are built on an **Amazon Linux**–based application blueprint rather than Bitnami's Debian/Ubuntu stack. On these instances, expect `yum`/`dnf` package management and no `/opt/bitnami` tree or `ctlscript.sh` helper — the `apt`/`a2enmod`/`systemctl` commands elsewhere in this guide apply directly, similar to the native/non-Bitnami steps above.

Lightsail is intentionally simple, and that simplicity shapes how you scale. Below is the general progression, from simplest/cheapest to most involved.

### Vertical Scaling (the primary way Lightsail scales)

Resizing your instance to a larger bundle (more vCPU, RAM, and SSD) is the main scaling lever on Lightsail, and should be your first move when you see sustained CPU/memory pressure in the **Metrics** tab.

- In the [Lightsail console](https://lightsail.aws.amazon.com/), open your instance, then use **Change plan** (found in the instance's menu) to move to a larger bundle. Lightsail can resize an existing instance in place — you don't need to recreate it.
- You can only move **up** in plan size this way. To move to a smaller plan, take a manual snapshot, create a new smaller instance from it, re-point DNS, and delete the old instance once verified.
- Vertical scaling has a ceiling — the largest Lightsail bundle — after which you need horizontal scaling or a move to EC2.

### Move MySQL Off the Web Instance

Running MySQL alongside Apache/PHP on the same instance means the database competes with your web server for CPU, RAM, and disk I/O. It's also a hard requirement for horizontal scaling, since multiple web instances can't share a database that's local to just one of them.

- **Lightsail Managed Database** — a separate managed MySQL instance with automated backups, patching, and an optional high-availability standby. This is the simplest path and keeps you fully within Lightsail.
- **Dedicated Lightsail instance running only MySQL** — more manual maintenance, but cheaper for small workloads.

Either way, the migration is the same shape: export your existing database (`mysqldump`), provision the new database, import the dump, update `DB_HOST` (and credentials) in `wp-config.php`, and confirm your instance's private networking/firewall rules allow it to reach the database on port 3306 before cutting over.

### Horizontal Scaling

Lightsail supports running multiple instances behind a **Lightsail Load Balancer**, but there's no equivalent to an EC2 Auto Scaling group — instances are added and removed manually, not automatically based on load.

> **Prerequisite:** move MySQL off the web instance first (see above). Horizontal scaling means multiple web instances serving the same site, and they can't each keep their own local copy of the database — all of them need to point at one shared MySQL instance/managed database before you add a Load Balancer.

- Create additional instances (cloning your configured instance via a snapshot is the easiest way to keep them consistent).
- Create a Lightsail Load Balancer and attach each instance as a target, with a health check path (e.g. `/`).
- Because WordPress instances aren't stateless by default, horizontal scaling requires extra work too:
  - **Shared uploads**: `wp-content/uploads` needs to live somewhere all instances can read/write — e.g., an offload plugin pointing at Amazon S3/Lightsail Object Storage, or a shared NFS mount — otherwise media uploaded on one instance won't appear on the others.
  - **Shared database**: each instance must point at the same MySQL database, not its own local copy.
- Plan on manually monitoring load and adding/removing instances (or their Load Balancer registration) yourself as traffic changes.

### CDN: Lightsail's Built-in CDN vs. Amazon CloudFront (CloudFront Recommended)

Lightsail offers its own **Content Delivery Network (CDN) distribution**, which is a simplified, wizard-driven layer on top of CloudFront. It's convenient to turn on, but trades away most of the control this guide (and FrontPup) relies on:

- Fewer cache behaviors and less control over per-path caching rules
- Limited header/cookie/query-string forwarding options
- No access to CloudFront Functions/Lambda@Edge for edge logic
- No support for multiple origins or the fine-grained cache/origin request policies CloudFront exposes directly
- Not addressable through the CloudFront API the way FrontPup's cache invalidation relies on

**Recommendation:** use an Amazon CloudFront distribution directly, as FrontPup is designed to do, rather than the Lightsail CDN option. This gives you full control over cache policies and behaviors, real invalidation via the API (what powers FrontPup's Clear Cache), custom origin/response headers, and room to add origins (e.g., an S3 bucket for static assets) later. If you're currently using the Lightsail CDN distribution, consider replacing it with a CloudFront distribution pointed at your instance as the origin, then managing it through FrontPup.

### Next Step: Upgrade to EC2

Once you've hit the ceiling on vertical scaling, and horizontal scaling on Lightsail feels too manual (no autoscaling, limited networking control, database still capped by Lightsail's managed database tiers), the natural next step is to move off Lightsail entirely using Lightsail's built-in **"Upgrade to EC2"** wizard.

- From the [Lightsail console](https://lightsail.aws.amazon.com/), open your instance and choose **Upgrade to EC2** from the instance menu.
- The wizard walks you through selecting a VPC, snapshotting your instance, and launching an equivalent EC2 instance from that snapshot — your instance's disk contents (including your WordPress install) carry over.
- Moving to EC2 unlocks the full AWS compute ecosystem that Lightsail intentionally abstracts away:
  - **EC2 Auto Scaling groups** and **Application/Network Load Balancers** for real automatic horizontal scaling
  - A much wider selection of instance families/sizes (compute-, memory-, and burstable-optimized types)
  - **Amazon RDS/Aurora for MySQL**, including read replicas, for the database tier
  - **Amazon ElastiCache** for a dedicated object cache
  - **Amazon EFS** for shared file storage across multiple web instances
  - Full VPC control (subnets, security groups, NACLs) and access to Reserved/Spot pricing

**Suggested order of operations:** scale vertically first (cheapest, least disruptive) → move MySQL to its own instance/managed database → add horizontal scaling with a Load Balancer if you still need more capacity → once Lightsail's ceiling is reached, use the Upgrade to EC2 wizard to unlock full autoscaling and managed AWS services.

### Scenarios

Which of the above you actually need depends heavily on the type of site. A few common cases:

- **Blog or podcast site.** Visitors aren't signed in and everyone sees the same content, so CloudFront can cache almost the entire site. Scaling here is primarily vertical, and a well-cached site relies on the CDN, not the origin, to absorb traffic. A 10,000-page site made up of largely static content shouldn't need to scale the origin at all if CloudFront caching is configured correctly — most requests should never reach the Lightsail instance.

- **CMS for a service or business.** A handful of contact/lead forms with the rest of the site being static pages behaves the same as the blog/podcast scenario — CloudFront caching handles nearly all traffic, and scaling is primarily vertical. The difference shows up at organizational scale: a large organization's uptime requirements may eventually call for high availability that a single Lightsail instance can't provide, which means the same eventual migration to EC2 (and Auto Scaling) described above, even though day-to-day traffic and caching needs look like a simple content site.

- **Membership site (1,000+ active members/month).** Logged-in members each get a personalized experience (account data, gated content, dashboards), so CloudFront caching doesn't help for most of the site — those responses are effectively unique per visitor and largely un-cacheable. Expect to need vertical scaling early, and horizontal scaling (with MySQL already moved off the web instance, per above) as active membership grows past what a single instance can serve.

- **Online store / e-commerce site.** Expect to need both vertical and horizontal scaling, plus high availability for the web tier and the database tier — carts, checkout, and inventory can't tolerate a single point of failure the way a cacheable blog can. Any commerce site should plan a roadmap toward the EC2 ecosystem, where Auto Scaling and high availability (e.g., Multi-AZ RDS, an Application Load Balancer across multiple AZs) can be architected in from the start rather than bolted on later.

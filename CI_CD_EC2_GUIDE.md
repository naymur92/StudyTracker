# StudyTracker CI/CD to EC2 (GitHub Actions + Native Deployment)

This guide explains how to set up and use the workflow in `.github/workflows/deploy.yml` for continuous deployment to EC2.

## What the Pipeline Does

**CI (Continuous Integration)** — Runs on all PRs and pushes to `main`:
- Sets up PHP 8.4 + Node.js 22 + MySQL 8.0
- Installs PHP/Node dependencies
- Builds frontend assets (`npm run build`)
- Runs Laravel migrations + tests
- Validates the build succeeds

**CD (Continuous Deployment)** — Runs automatically after CI passes on `main` branch:
- SSHes into EC2 via GitHub Actions
- Pulls latest `main` branch
- Executes `deploy/deploy.sh` (10-step native deployment)
- No Docker image build, no GHCR push (native PHP/npm on EC2)

The deployment is **lightweight on EC2**: all heavy building (npm, composer) happens in GitHub Actions CI or during the deploy.sh pull-and-build step. This works well for t2.micro instances with limited RAM when you have external DB/Redis.

## 1) Prepare EC2 Once

### Step 1a: Run system setup script (first time only)

SSH into your EC2 instance and run:

```bash
# Clone the repo first (if not already cloned)
sudo mkdir -p /var/www && sudo chown -R $USER:$USER /var/www
cd /var/www
git clone <your-repo-url> StudyTracker
cd StudyTracker

# Run one-time EC2 system setup (installs PHP 8.4, Nginx, MySQL, Redis, Composer, Node.js, Supervisor)
sudo bash deploy/ec2-setup.sh
```

This installs:
- PHP 8.4 with required extensions (pdo, pdo_mysql, mbstring, bcmath, gd, zip, intl, redis, etc.)
- Nginx (web server)
- MySQL 8.0 (database)
- Redis (cache)
- Supervisor (process management for queue workers)
- Git, curl, Composer, Node.js 20
- 2GB swap (critical for t2.micro with only 1GB RAM)

### Step 1b: Configure services (one time, or when infrastructure changes)

Run the configuration script:

```bash
sudo bash deploy/ec2-configure.sh
```

This optimizes:
- MySQL for low-RAM servers (innodb pools, max connections)
- Redis memory limits (64MB max, LRU policy)
- PHP-FPM memory and timeout settings
- Nginx configuration
- Supervisor process management
- Cron jobs for Laravel scheduler

### Step 1c: Create production `.env`

Create `.env` in your EC2 project directory with production settings:

```bash
cp .env.example .env
nano .env  # or vim .env
```

Minimum required settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=  # Will auto-generate on first deploy if empty
APP_URL=http://<EC2_PUBLIC_IP>:8080

DB_HOST=<your-db-host>           # Use external DB or 127.0.0.1 for local MySQL
DB_PORT=3306
DB_DATABASE=study_tracker
DB_USERNAME=st_user
DB_PASSWORD=<strong-password>

REDIS_HOST=<your-redis-host>     # Use external Redis or 127.0.0.1 for local Redis
REDIS_PORT=6379

# Frontend API base URL (used at build time)
VITE_API_URL=http://<EC2_PUBLIC_IP>:8080/api
```

**For micro instances with limited resources:**  
Prefer external managed services (RDS for MySQL, ElastiCache for Redis) to avoid running all services on one small server.

**Note:** `.env` is not included in `docker-compose.yml` images. You manage it directly on EC2 and it persists across deployments.

## 2) Add GitHub Repository Secrets

Go to GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **Secrets and variables**.

Create these secrets (required for SSH deployment):

| Secret | Description | Example |
|--------|-------------|---------|
| `EC2_HOST` | EC2 public IP or hostname | `203.0.113.42` or `ec2.example.com` |
| `EC2_USERNAME` | SSH user on EC2 | `ubuntu` or `ec2-user` |
| `EC2_SSH_PRIVATE_KEY` | Contents of your `.pem` key | (full key content, starts with `-----BEGIN`) |
| `EC2_APP_PATH` | Absolute app path on EC2 | `/var/www/StudyTracker` |
| `EC2_SSH_PORT` | (optional) SSH port | `22` (default) |

**How to get your private key:**
```bash
# On your local machine (where you have the .pem file)
cat ~/.ssh/your-key.pem
# Copy the entire output and paste into EC2_SSH_PRIVATE_KEY secret
```

That's it! The workflow uses GitHub's built-in `GITHUB_TOKEN` for checkout, so no extra registry credentials are needed.

> **Why not GHCR/Docker secrets?**  
> This deployment does **not** build Docker images in GitHub Actions. Instead, it pulls code on EC2 and runs `deploy.sh` to build PHP/npm directly. All the heavy build work (Composer, npm) either happens in the CI step (for testing) or on EC2 during deploy.sh (for production).

## 3) Add Branch Protection (Recommended)

Protect your `main` branch to require passing CI before merge:

1. Go to **Settings** → **Branches** → **Branch protection rules**
2. Add rule for `main` branch
3. Check: **Require status checks to pass before merging**
4. Select: **CI (Laravel tests + frontend build)** workflow job

This ensures broken code cannot be merged to main and cause failed deployments.

## 4) First Deployment Test

### Step 1: Commit and push to main

Push any commit to the `main` branch:

```bash
git push origin main
```

### Step 2: Watch CI workflow

1. Open **GitHub repository** → **Actions** tab
2. Find the **"CI/CD - Test, Build, Deploy to EC2 (Native)"** workflow
3. Watch the **ci** job — it should pass (PHP tests, npm build, Laravel tests)

This job verifies the code is correct before it touches production.

### Step 3: Watch CD workflow

Once **ci** passes:
1. The **deploy** job automatically starts
2. It SSHes to your EC2 instance using `EC2_SSH_PRIVATE_KEY`
3. Pulls the latest `main` branch
4. Runs `sudo bash deploy/deploy.sh`

Watch the deploy job logs to see all 10 deployment steps execute.

### Step 4: Verify the app is live

Open your browser:

```
http://<EC2_PUBLIC_IP>:8080
```

You should see the StudyTracker frontend. Check the browser console for API errors if something doesn't work.

### Step 5: First-time .env setup (if needed)

If deploy.sh paused because `.env` didn't exist:
1. SSH into EC2
2. Edit `.env` with your database and service credentials
3. Re-run: `sudo bash deploy/deploy.sh`

The script will continue from where it paused.

## 5) Daily Workflow (Feature Development)

This is what you do every day to deploy changes:

1. **Create feature branch**: `git checkout -b feature/my-feature`
2. **Make changes** and commit: `git commit -m "Add feature"`
3. **Push to GitHub**: `git push origin feature/my-feature`
4. **Open Pull Request** into `main` with a clear title/description
5. **Wait for CI to pass** (watch GitHub Actions tab)
6. **Code review** (if you have team members)
7. **Merge PR** into `main`
8. **CD auto-deploys** to EC2 within seconds (no manual action needed)

Check the **Actions** tab to watch real-time deployment progress. When deploy job finishes, changes are live on EC2.

## 6) Troubleshooting

### GitHub Actions Workflow Failures

| Symptom | Check |
|---------|-------|
| **SSH connection failed** | Verify `EC2_HOST`, `EC2_USERNAME`, and `EC2_SSH_PRIVATE_KEY` are correct in repo secrets |
| **"Repository not found at $APP_DIR"** | Verify `EC2_APP_PATH` is the correct absolute path where you cloned the repo |
| **"Permission denied (publickey)"** | Ensure the `.pem` private key in `EC2_SSH_PRIVATE_KEY` secret matches the EC2 instance's key pair |
| **Git fetch/pull fails** | Ensure EC2 has git and can reach GitHub (check EC2 security group allows outbound HTTPS on port 443) |

### EC2 Deployment Script Failures

SSH into your EC2 and check deployment logs:

```bash
# View latest deploy.sh output
tail -50 /var/log/deploy.log  # (if logging configured)

# Or re-run manually to see full output
cd /var/www/StudyTracker
sudo bash deploy/deploy.sh

# View Laravel logs for app errors
tail -100 storage/logs/laravel.log

# Check if web server is running and listening
sudo systemctl status nginx
curl -i http://127.0.0.1:8080/healthz
```

| Symptom | Solution |
|---------|----------|
| **Step 4: .env creation paused** | SSH to EC2, edit `.env`, run `sudo bash deploy/deploy.sh` again |
| **Step 6: Passport key generation fails** | Ensure `storage/` directory is writable; check PHP-FPM user (`www-data`) permissions |
| **Step 8: Migration fails** | Check database connection in `.env`; verify DB host/port/credentials; check Laravel logs |
| **Step 10: Queue restart fails** | Ensure Supervisor is running: `sudo systemctl status supervisor` |

### App Won't Open at http://<EC2_PUBLIC_IP>:8080

**Most common:** Database is unreachable or migrations didn't complete.

Check logs:
```bash
cd /var/www/StudyTracker
tail -50 storage/logs/laravel.log

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit
```

**If using external RDS:**
- Verify security group allows EC2 to connect on port 3306
- Confirm DB username/password in `.env` is correct
- Test from EC2: `mysql -h <db-host> -u <username> -p<password> study_tracker`

**If using local MySQL:**
- Verify MySQL is running: `sudo systemctl status mysql`
- Check with: `sudo mysql -u root -p`

### Nginx/PHP-FPM Issues

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check Nginx error log
sudo tail -50 /var/log/nginx/error.log

# Reload Nginx after config changes
sudo systemctl reload nginx

# View current Nginx config
sudo nginx -t  # Test syntax
```

### Queue Workers Not Running

If background jobs (emails, reports) aren't processing:

```bash
# Check Supervisor status
sudo systemctl status supervisor

# View Supervisor logs
sudo tail -50 /var/log/supervisor/laravel-worker.log

# Restart queue workers
php artisan queue:restart
sudo supervisorctl restart study_tracker-worker:*

# Monitor queue
php artisan queue:monitor
```

### Low Memory on t2.micro?

If deployments fail with memory errors or app crashes:

```bash
# Check RAM usage
free -h

# Check swap (should be ~2GB from ec2-setup.sh)
swapon -s

# Check disk space
df -h /var/www

# Clear Laravel caches if space is tight
php artisan cache:clear
php artisan view:clear
```

Consider offloading to managed services (RDS for MySQL, ElastiCache for Redis) if memory remains tight.

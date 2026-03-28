# StudyTracker CI/CD to EC2 (GitHub Actions)

This guide explains how to use the workflow in `.github/workflows/deploy.yml`.

## What the pipeline does

1. Runs CI on pull requests and pushes to `main`:

- install PHP/Node dependencies
- run Laravel migrations + tests
- build frontend assets
- validate Docker Compose syntax

2. Runs CD only on push to `main` (after CI passes):

- build Docker image in GitHub Actions
- push image to GHCR
- SSH into EC2
- pull latest `main`
- run `docker compose pull study_tracker_app nginx`
- run `docker compose up -d --no-build study_tracker_app nginx`
- run Laravel migration only

For micro EC2 instances, deployment starts only `study_tracker_app` and `nginx`.

## 1) Prepare EC2 once

Run on your EC2 instance:

```bash
sudo apt update && sudo apt install -y git curl
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
newgrp docker
sudo apt install -y docker-compose-plugin
```

Clone your app on EC2 (example path):

```bash
sudo mkdir -p /var/www && sudo chown -R $USER:$USER /var/www
cd /var/www
git clone <your-repo-url> StudyTracker
cd StudyTracker
```

Create production `.env` in EC2 project path and set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<EC2_PUBLIC_IP>:8080
VITE_API_URL=http://<EC2_PUBLIC_IP>:8080/api
APP_HTTP_PORT=8080
APP_HTTPS_PORT=8443
```

For small servers, prefer managed/external services and set:

```env
DB_HOST=<external-db-host>
DB_PORT=3306
REDIS_HOST=<external-redis-host-or-disable-usage>
REDIS_PORT=6379
```

## 2) Add GitHub repository secrets

Go to GitHub repository -> Settings -> Secrets and variables -> Actions -> Secrets.

Create these secrets:

- `EC2_HOST`: EC2 public IP or hostname
- `EC2_USERNAME`: SSH user (`ubuntu` / `ec2-user`)
- `EC2_SSH_PRIVATE_KEY`: contents of your `.pem` key
- `EC2_APP_PATH`: absolute app path on EC2 (example: `/var/www/StudyTracker`)
- `EC2_SSH_PORT` (optional): SSH port, default `22`
- `GHCR_USERNAME` (optional): only needed when package is private
- `GHCR_PAT` (optional): token with `read:packages` for private GHCR pull

No extra registry secret is required for image push because workflow uses built-in `GITHUB_TOKEN`.

## 3) Add branch protection (recommended)

For `main` branch, require status check from workflow job `CI (Laravel tests + frontend build)`.

## 4) First deployment test

1. Push any commit to `main`.
2. Open GitHub Actions tab.
3. Confirm `CI/CD - Test, Build, Deploy to EC2` workflow:

- `ci` job passes
- `deploy` job builds and pushes image
- `deploy` job SSHes to EC2 and runs pull + restart for app + nginx only

4. Open:

```text
http://<EC2_PUBLIC_IP>:8080
```

## 5) Daily workflow you should follow

1. Create feature branch
2. Open pull request into `main`
3. CI must pass
4. Merge PR
5. CD auto deploys to EC2

## Common failure checks

- SSH failure: verify `EC2_HOST`, user, and private key
- Path failure: verify `EC2_APP_PATH` points to repo root on EC2
- Container failure: run on EC2 `docker compose logs -f`
- App 500 error: check `storage/logs/laravel.log` in the app container

## If Docker is running but :8080 does not open

Most common reason: `study_tracker_app` is not healthy because database is unreachable.

Run on EC2:

```bash
cd /var/www/StudyTracker
docker compose ps
docker compose logs --tail=120 study_tracker_app nginx
```

If `.env` has `DB_HOST=db`, start local DB temporarily:

```bash
docker compose --profile local-infra up -d db study_tracker_app nginx
```

If you use external DB, set `DB_HOST` to the external host and redeploy.

Quick local health check on EC2:

```bash
curl -i http://127.0.0.1:8080/healthz
```

## Micro instance mode

- Keep DB and Redis out of this server when possible (RDS/managed Redis).
- `docker-compose.yml` marks local `db` and `redis` under profile `local-infra`.
- `docker-compose.yml` marks `queue` and `scheduler` under profile `workers`.
- Run local DB/Redis only when needed:

```bash
docker compose --profile local-infra up -d
```

- Run workers only when needed:

```bash
docker compose --profile workers up -d
```

## Heavy work policy

Do not run these on micro EC2 servers:

- `npm install`
- `npm run build`
- `composer install`
- extra Laravel cache rebuild commands during deploy

These are handled in GitHub Actions image build.

## Deployment flow (lightweight on EC2)

```text
GitHub push
-> GitHub Actions builds Docker image
-> GitHub Actions pushes image to GHCR
-> EC2 pulls ready image
-> EC2 restarts app + nginx without build
```

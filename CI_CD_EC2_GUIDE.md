# StudyTracker CI/CD to EC2 (GitHub Actions)

This guide explains how to use the workflow in `.github/workflows/deploy.yml`.

## What the pipeline does

1. Runs CI on pull requests and pushes to `main`:

- install PHP/Node dependencies
- run Laravel migrations + tests
- build frontend assets
- validate Docker Compose syntax

2. Runs CD only on push to `main` (after CI passes):

- SSH into EC2
- pull latest `main`
- run `docker compose up -d --build`
- run Laravel cache + migration commands

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

## 2) Add GitHub repository secrets

Go to GitHub repository -> Settings -> Secrets and variables -> Actions -> Secrets.

Create these secrets:

- `EC2_HOST`: EC2 public IP or hostname
- `EC2_USERNAME`: SSH user (`ubuntu` / `ec2-user`)
- `EC2_SSH_PRIVATE_KEY`: contents of your `.pem` key
- `EC2_APP_PATH`: absolute app path on EC2 (example: `/var/www/StudyTracker`)
- `EC2_SSH_PORT` (optional): SSH port, default `22`

## 3) Add branch protection (recommended)

For `main` branch, require status check from workflow job `CI (Laravel tests + frontend build)`.

## 4) First deployment test

1. Push any commit to `main`.
2. Open GitHub Actions tab.
3. Confirm `CI/CD - Test, Build, Deploy to EC2` workflow:

- `ci` job passes
- `deploy` job runs after `ci`

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

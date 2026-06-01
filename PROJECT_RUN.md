# Project Setup From Scratch

This project uses the following stack:

* Laravel API backend
* Next.js frontend
* TypeScript
* Tailwind CSS
* Docker / Laravel Sail
* MySQL
* Redis
* Mailpit

Project structure:

```bash
dropshipping-store/
├── api/   # Laravel API backend
└── web/   # Next.js frontend
```

---

## 1. Requirements

Before starting, make sure the following tools are installed on Windows:

* Docker Desktop
* WSL 2
* Ubuntu in WSL
* PhpStorm

Docker Desktop must have WSL integration enabled.

Open Docker Desktop and go to:

```text
Settings → Resources → WSL Integration
```

Enable integration for your Ubuntu distribution.

Example:

```text
Ubuntu-26.04
```

---

## 2. Open Ubuntu / WSL Terminal

Open the Ubuntu terminal and check that Docker works:

```bash
docker version
docker compose version
```

If both commands work, Docker is ready.

---

## 3. Create Project Directory

Create the main project folder inside the WSL filesystem:

```bash
cd ~
mkdir -p Code/dropshipping-store
cd Code/dropshipping-store
```

Check the current path:

```bash
pwd
```

Expected result:

```bash
/home/your-user/Code/dropshipping-store
```

Do not create the project inside `/mnt/c/...`.

The project should be stored inside the WSL filesystem for better performance.

---

## 4. Install Basic Ubuntu Packages

```bash
sudo apt update
sudo apt install -y curl git unzip
```

---

## 5. Create Laravel API Backend

From the main project directory:

```bash
cd ~/Code/dropshipping-store
curl -s "https://laravel.build/api?with=mysql,redis,mailpit" | bash
```

This will create the Laravel backend inside:

```bash
~/Code/dropshipping-store/api
```

Go to the backend directory:

```bash
cd ~/Code/dropshipping-store/api
```

Start Laravel Sail:

```bash
./vendor/bin/sail up -d
```

Open Laravel in the browser:

```text
http://localhost
```

If you see a Laravel page, the backend is running.

---

## 6. Run Laravel Migrations

If Laravel shows an error about a missing `sessions` table, run migrations:

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail artisan migrate
```

---

## 7. Install Laravel API Scaffolding

Run:

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail artisan install:api
./vendor/bin/sail artisan migrate
```

This prepares Laravel for API development.

---

## 8. Add Test API Route

Open this file:

```bash
api/routes/api.php
```

Add the following route:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'status' => 'Laravel API works',
    ]);
});
```

Check it in the browser:

```text
http://localhost/api/ping
```

Expected response:

```json
{
  "message": "pong",
  "status": "Laravel API works"
}
```

---

## 9. Install Node.js With NVM

Check if Node.js is already installed:

```bash
node -v
npm -v
```

If Node.js is not installed, install it with NVM:

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.4/install.sh | bash
. "$HOME/.nvm/nvm.sh"
nvm install --lts
```

Check again:

```bash
node -v
npm -v
```

---

## 10. Create Next.js Frontend

Go back to the main project directory:

```bash
cd ~/Code/dropshipping-store
```

Create the Next.js app:

```bash
npx create-next-app@latest web --ts --tailwind --eslint --app --src-dir --use-npm --import-alias "@/*"
```

This will create the frontend inside:

```bash
~/Code/dropshipping-store/web
```

---

## 11. Configure Frontend Environment

Go to the frontend directory:

```bash
cd ~/Code/dropshipping-store/web
```

Create the environment file:

```bash
nano .env.local
```

Add:

```env
NEXT_PUBLIC_API_URL=http://localhost/api
```

Save the file:

```text
Ctrl + O
Enter
Ctrl + X
```

---

## 12. Connect Next.js to Laravel API

Open this file:

```bash
web/src/app/page.tsx
```

Replace its content with:

```tsx
type PingResponse = {
  message: string;
  status: string;
};

async function getPing(): Promise<PingResponse> {
  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/ping`, {
    cache: 'no-store',
  });

  if (!response.ok) {
    throw new Error('Failed to fetch Laravel API');
  }

  return response.json();
}

export default async function Home() {
  const data = await getPing();

  return (
    <main className="min-h-screen p-10">
      <h1 className="text-3xl font-bold">Dropshipping Store</h1>

      <p className="mt-4 text-lg">
        Next.js frontend connected to Laravel API.
      </p>

      <pre className="mt-6 rounded-lg bg-gray-100 p-4 text-sm">
        {JSON.stringify(data, null, 2)}
      </pre>
    </main>
  );
}
```

---

## 13. Start Next.js Frontend

Run:

```bash
cd ~/Code/dropshipping-store/web
npm run dev
```

Open the frontend in the browser:

```text
http://localhost:3000
```

If the page displays the JSON response from Laravel, the frontend and backend are connected successfully.

---

## 14. Project URLs

Laravel base URL:

```text
http://localhost
```

Laravel API:

```text
http://localhost/api/...
```

Test API endpoint:

```text
http://localhost/api/ping
```

Next.js frontend:

```text
http://localhost:3000
```

Mailpit:

```text
http://localhost:8025
```

MySQL:

```text
127.0.0.1:3306
```

Default MySQL credentials for Laravel Sail:

```text
Host: 127.0.0.1
Port: 3306
Database: laravel
Username: sail
Password: password
```

---

## 15. Open Project in PhpStorm

Open the project from the WSL path:

```text
\\wsl.localhost\Ubuntu-26.04\home\your-user\Code\dropshipping-store
```

Do not open the WSL virtual disk file directly.

Do not open:

```text
D:\DevEnv\WSL\Ubuntu-26.04\ext4.vhdx
```

That file is the WSL virtual disk and should not be edited manually.

---

## 16. Daily Development Start

Terminal 1: start Laravel API

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail up -d
```

Terminal 2: start Next.js frontend

```bash
cd ~/Code/dropshipping-store/web
npm run dev
```

Then open:

```text
http://localhost:3000
```

---

## 17. Stop Project

Stop Laravel Docker containers:

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail down
```

Stop Next.js frontend:

```text
Ctrl + C
```

Run `Ctrl + C` in the terminal where `npm run dev` is running.

---

## 18. Useful Laravel Commands

```bash
cd ~/Code/dropshipping-store/api

./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan make:model Product -mcr
```

---

## 19. Useful Next.js Commands

```bash
cd ~/Code/dropshipping-store/web

npm run dev
npm run build
npm run start
npm run lint
```

---

## 20. Docker Commands

Show running containers:

```bash
docker ps
```

Show all containers:

```bash
docker ps -a
```

Show Laravel Sail logs:

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail logs
```

Show Laravel application container logs:

```bash
./vendor/bin/sail logs laravel.test
```

Restart Laravel containers:

```bash
cd ~/Code/dropshipping-store/api
./vendor/bin/sail down
./vendor/bin/sail up -d
```

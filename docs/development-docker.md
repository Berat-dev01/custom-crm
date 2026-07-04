# Development Docker

Development ortaminda Docker zorunludur. Host makinede `php`, `composer`, `npm` veya `php artisan` calistirilmaz.

## Servisler

Docker stack:

- `app`: PHP-FPM, Composer, Artisan ve npm komutlari
- `nginx`: local HTTP
- `mysql`: development database
- `redis`: queue, cache ve rate limit altyapisi
- `queue`: queue worker
- `scheduler`: Laravel scheduler
- `mailpit`: local mail yakalama

Portlar:

- App: `http://localhost:8081`
- MySQL: `3307`
- Redis: `6380`
- Mailpit: `http://localhost:8026`

## Komutlar

```bash
make up
make down
make restart
make build
make ps
make logs
```

Container shell:

```bash
make bash
```

Composer:

```bash
make composer CMD="install"
make composer CMD="validate --strict"
```

Artisan:

```bash
make artisan CMD="about"
make artisan CMD="migrate"
make artisan CMD="migrate:fresh --seed"
make artisan CMD="route:list --path=admin/crm"
```

npm:

```bash
make npm CMD="install"
make npm CMD="run build"
```

Test:

```bash
make test
```

Queue worker:

```bash
make queue
```

## Fresh Setup Akisi

```bash
cp .env.example .env
make up
make composer CMD="install"
make artisan CMD="key:generate"
make fresh
```

## Debug ve Log

Laravel log:

```bash
make artisan CMD="pail"
```

Container log:

```bash
make logs
```

Mail testleri icin Mailpit:

```text
http://localhost:8026
```

## Data Reset

Development verisini sifirlamak icin:

```bash
make fresh
```

Sadece migration calistirmak icin:

```bash
make migrate
```

## Scheduler ve Reminder

Task reminder komutu service provider tarafindan her bes dakikada bir schedule edilir:

```bash
make artisan CMD="crm:tasks:send-reminders"
```

Development stack icinde scheduler container bunu otomatik calistirir.

Production scheduler ayri cron ile kurulur; production'da Docker kullanilmaz.

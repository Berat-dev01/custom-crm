# Production Scheduler

CRM production deployments do not use Docker. Run the Laravel scheduler from the server cron and run queue workers under Supervisor.

Cron:

```cron
* * * * * cd /var/www/crm/current && php artisan schedule:run >> /dev/null 2>&1
```

Supervisor queue worker example:

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crm/current/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/crm-worker.log
```

The CRM task reminder command is scheduled by the package every five minutes:

```bash
php artisan crm:tasks:send-reminders
```

Use Redis for `QUEUE_CONNECTION`, keep `schedule:run` on a single application node, and run at least one queue worker so queued database and mail notifications are delivered.

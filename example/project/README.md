#### Install

```bash
composer install
```

#### Run Listener

```bash
./vendor/bin/aint-queue worker:listen --channel=example
php example/project/bin/demo-app worker:listen -c example/project/config/aint-queue.php --channel=example
```

#### Run Server

```bash
php -S localhost:8000 -t ./public/
```

#### Push job

```bash
curl -v http://localhost:8000

```

#### Check job status

via Console

```bash
./vendor/bin/aint-queue queue:status --channel=example
/var/www/html# bin/aint-queue queue:status --channel=example -c example/project/config/aint-queue.php 
```

or start the dashboard server

```
./vendor/bin/aint-queue queue:dashboard  --addr={$ip}:{$host} --channel=example
```
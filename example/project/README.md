#### Install

```bash
composer install
```

#### Run Listener

```bash
./vendor/bin/aint-queue worker:listen --channel=example
# or
php example/project/bin/demo-app worker:listen -c example/project/config/aint-queue.php --channel=example
```

#### Run Server

```bash
php -S localhost:8000 -t ./public/
```

#### Push job

```bash
curl -v http://localhost:8000
# or
php example/project/bin/demo-client.php
```

#### Check job status

via Console

```bash
./vendor/bin/aint-queue queue:status --channel=example
# or
bin/aint-queue queue:status --channel=example -c example/project/config/aint-queue.php 

php example/project/bin/demo-app queue:status -c example/project/config/aint-queue.php --channel=example
```

or start the dashboard server

```
./vendor/bin/aint-queue queue:dashboard  --addr={$ip}:{$host} --channel=example
```

stop server
```
php example/project/bin/demo-app worker:stop -c example/project/config/aint-queue.php --channel=example
```
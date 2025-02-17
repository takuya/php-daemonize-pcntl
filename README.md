# php-daemonize-pcntl

daemonize function by pcntl_fork.   

## php daemon Daemonize all

Daemonize any process , written by php ( pcntl )


## installing 

from Packagist
```shell
composer require takuya/php-daemonize-pcntl
```
from GitHub
```shell
name='php-daemonize-pcntl'
composer config repositories.$name \
vcs https://github.com/takuya/$name  
composer require takuya/$name:master
composer install
```

## Examples 

### function / Daemonize ( detach tty and parent PID=1)  

```php
<?php

require_once 'vendor/autoload.php';
use Takuya\PhpDaemonize\PhpDaemonize;

/**
* Daemonize function
 */
$m = new PhpDaemonize();
$m->start(function () { sleep(1000); });
$m->stop();

```
### Run command as daemon
```php
<?php

require_once 'vendor/autoload.php';
use Takuya\PhpDaemonize\PhpDaemonize;

/**
* Daemonize function
 */
$m = new PhpDaemonize();
$m->start(function () { pcntl_exec('/usr/bin/sleep',[10]) });
```

## service (init.d)

this package help to make init.d service 

run web server
### my-server.sh
```php
#!/usr/bin/env php
require_once 'vendor/autoload.php';
use Takuya\PhpDaemonize\PhpDaemonize;


$name = 'my-server';
// start stop
PhpDaemonize::run_func(function(){
  pcntl_exec('/usr/bin/php',['artisan','serve'])
},$name);
```

start and stop 
```shell
./my-server.sh start
./my-server.sh stop
```


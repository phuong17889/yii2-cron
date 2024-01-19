Yii Cron extension
==================

[![Latest Stable Version](https://poser.pugx.org/phuongdev89/yii2-cron/v/stable)](https://packagist.org/packages/phuongdev89/yii2-cron) [![Total Downloads](https://poser.pugx.org/phuongdev89/yii2-cron/downloads)](https://packagist.org/packages/phuongdev89/yii2-cron)

Provide a logic and functionality to block console commands until they execute. 
Unlocks commands exhibited at the expiration of the block if the server is down.

## Install

```
composer require phuongdev89/yii2-cron
```
## Usage

### Code
Any command can be converted to daemon
```php
class AwesomeController extends \phuongdev89\cron\commands\DaemonController
{
    public $restartDb = false; //restart db connection every run, default is `false`

    public $db = 'db'; //name of db component need to restart, `db` that mean `Yii::$app->db`, default is `db`

    public $daemonDelay = 15; //delay time between run, in microsecond, default is `15`

    /**
     * Daemon name, unique
     *
     * @return string
     */
    protected function daemonName(): string
    {
        return 'mail-queue';
    }

    /**
     * repeatable function
     */
    public function worker()
    {
        // Some logic that will be repeateble 
    }
}
```
### Command

Start repeat function
```
php yii awesome/start
```

Stop repeat function
```
php yii awesome/stop
```

Restart repeat function
```
php yii awesome/restart
```

Add to crontab:
```
* * * * * php yii awsome/start
```
Yii Cron extension
==================

[![Latest Stable Version](https://poser.pugx.org/phuong17889/yii2-cron/v/stable)](https://packagist.org/packages/phuong17889/yii2-cron) [![Total Downloads](https://poser.pugx.org/phuong17889/yii2-cron/downloads)](https://packagist.org/packages/phuong17889/yii2-cron)

Provide a logic and functionality to block console commands until they execute. 
Unlocks commands exhibited at the expiration of the block if the server is down.

#### Usage
```php
public function behaviors()
{
    return array(
        'LockUnLockBehavior' => array(
            'class' => 'phuong17889\cron\commands\behaviors\LockUnLockBehavior',
            'timeLock' => 0 //Set time lock duration for command in seconds
        )
    );
}
```

Any command can be converted to daemon
```php
class AwesomeCommand extends DaemonController
{
    /**
     * Daemon name
     *
     * @return string
     */
    protected function daemonName(): string
    {
        return 'mail-queue';
    }

    /**
     * Run send mail
     */
    public function worker()
    {
        // Some logic that will be repeateble 
    }
}
```

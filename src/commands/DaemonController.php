<?php

namespace phuongdev89\cron\commands;

use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;
use phuongdev89\cron\exceptions\IsNotRunningException;
use phuongdev89\cron\exceptions\IsRunningException;
use phuongdev89\cron\traits\DaemonTrait;

/**
 * Class DaemonController
 * Daemon controller for console
 *
 * @package phuongdev89\cron\commands
 */
abstract class DaemonController extends Controller
{
    use DaemonTrait;

    /**
     * @var string
     */
    public $defaultAction = 'start';

    /**
     * @throws InvalidConfigException
     */
    public function beforeAction($action)
    {
        // Push each log message to related log target
        Yii::$app->get('log')->flushInterval = 1;
        foreach (Yii::$app->get('log')->targets as $i => $target) {
            Yii::$app->get('log')->targets[$i]->exportInterval = 1;
        }

        return parent::beforeAction($action);
    }

    /**
     * Daemon worker
     */
    abstract protected function worker();

    /**
     * Default action. Starts daemon.
     *
     * @return void
     * @throws Exception
     */
    public function actionStart()
    {
        try {
            $this->stdout(sprintf("[%s] running daemon\n", $this->daemonName()), Console::FG_GREEN);
            $this->startDaemon([$this, 'worker']);
        } catch (IsRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Restart daemon.
     * @throws Exception
     */
    public function actionRestart()
    {
        try {
            $this->stdout(sprintf("[%s] restarting daemon\n", $this->daemonName()), Console::FG_GREEN);
            $this->restartDaemon([$this, 'worker']);
        } catch (IsRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Stops daemon.
     *
     * @return void
     * @throws Exception
     */
    public function actionStop()
    {
        try {
            $this->stopDaemon();
            $this->stdout(sprintf("[%s] stop daemon\n", $this->daemonName()), Console::FG_GREEN);
        } catch (IsNotRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }
}

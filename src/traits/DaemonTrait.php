<?php

namespace phuongdev89\cron\traits;

use Exception;
use Yii;
use phuongdev89\cron\exceptions\IsNotRunningException;
use phuongdev89\cron\exceptions\IsRunningException;
use phuongdev89\cron\helpers\FileOutput;
use yii\base\InvalidConfigException;

/**
 * Trait DaemonTrait
 *
 * @package phuongdev89\cron\commands\traits
 */
trait DaemonTrait
{
    /**
     * @var bool re-open sql connection while run daemon
     */
    public $restartDb = false;

    /**
     * @var string the db name, which will be re-opened
     */
    public $db = 'db';

    /**
     * @var int microsecond delay time
     */
    public $daemonDelay = 15;

    /**
     * @var FileOutput
     */
    private $fileOutput;

    /**
     * Daemon name
     *
     * @return string
     */
    abstract protected function daemonName(): string;

    /**
     * Reload daemon
     *
     * @param callable $worker
     * @throws Exception
     */
    protected function restartDaemon(callable $worker)
    {
        try {
            $this->stopDaemon();
        } finally {
            $this->startDaemon($worker);
        }
    }

    /**
     * Creates daemon.
     * Check is daemon already run and if false then starts daemon and update lock file.
     *
     * @param callable $worker
     *
     * @throws Exception
     */
    protected function startDaemon(callable $worker)
    {
        if ($this->isAlreadyRunning()) {
            throw new IsRunningException(sprintf('[%s] is running already.', $this->daemonName()));
        } else {
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('Error while forking process.');
            } elseif ($pid) {
                exit();
            } else {
                $pid = getmypid();
                $this->addPid($pid);
            }

            // Automatically send every new message to available log routes
            Yii::getLogger()->flushInterval = 1;
            while (true) {
                if ($this->restartDb) {
		            $db = $this->db;
		            Yii::$app->$db->close();
		            Yii::$app->$db->open();
	            }
                // Start daemon method
                call_user_func($worker);
                usleep($this->daemonDelay);
            }
        }
    }

    /**
     * Stop daemon
     *
     * @throws Exception
     */
    protected function stopDaemon()
    {
        if (!$this->isAlreadyRunning()) {
            throw new IsNotRunningException(sprintf('[%s] is not running.', $this->daemonName()));
        }
        if (file_exists($this->getPidsFilePath())) {
            $pids = $this->getPids();
            foreach ($pids as $pid) {
                $this->removePid($pid);
            }
        }
    }

    /**
     * Checks if daemon already running.
     *
     * @return bool
     */
    protected function isAlreadyRunning(): bool
    {
        $result = true;
        $runningPids = $this->getPids();
        if (empty($runningPids)) {
            $result = false;
        } else {
            $systemPids = explode("\n", trim(shell_exec("ps -e | awk '{print $1}'")));
            if (false === empty(array_diff($runningPids, $systemPids))) {
                foreach ($runningPids as $pid) {
                    $this->removePid($pid);
                }
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Add pid
     *
     * @param $pid
     */
    protected function addPid($pid)
    {
        $pids = $this->getPids();
        $pids[] = $pid;
        $this->setPids($pids);
    }

    /**
     * Add pid
     *
     * @param $pid
     */
    protected function removePid($pid)
    {
        // Remove all process
        $children[] = $pid;
        while ($child = exec('pgrep -P ' . reset($children))) {
            array_unshift($children, $child);
        }
        foreach ($children as $child) {
            exec("kill $child 2> /dev/null");
        }

        $pids = array_diff($this->getPids(), [$pid]);
        $this->setPids($pids);
    }

    /**
     * Get pids
     *
     * @return array
     */
    protected function getPids()
    {
        $pids = [];
        if (file_exists($this->getPidsFilePath())) {
            $pids = explode(',', trim(file_get_contents($this->getPidsFilePath())));
        }

        return array_filter($pids);
    }

    /**
     * Set pids
     *
     * @param array $pids
     * @void
     */
    protected function setPids(array $pids)
    {
        $pidsFile = $this->getPidsFilePath();
        file_put_contents($pidsFile, implode(',', $pids));
    }

    /**
     * Pids file path
     *
     * @return string
     */
    protected function getPidsFilePath()
    {
        return $this->getDaemonFilePath('pids.bin');
    }

    /**
     * Gets path to daemon data.
     * Lock file keeps pids of started daemons.
     *
     * @param string $file
     *
     * @return string
     */
    protected function getDaemonFilePath($file): string
    {
        $path = $this->getDaemonDirPath();

        if (false === is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path . '/' . $file;
    }

	/**
	 * @return string
	 */
	protected function getDaemonDirPath(): string
    {
        return Yii::$app->basePath . '/runtime/daemons/' . strtolower($this->daemonName());
    }

    /**
     * @param $text
     *
     * @return void
     * @throws InvalidConfigException
     */
    protected function output($text)
    {
        if (null === $this->fileOutput) {
            $this->fileOutput = new FileOutput($this->getDaemonFilePath('output.log'));
        }

        $this->fileOutput->stdout(trim($text));
    }
}

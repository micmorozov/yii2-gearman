<?php

namespace apollo\yii2\gearman;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use apollo\yii2\gearman\Process;
use apollo\yii2\gearman\Application;

class GearmanController extends Controller
{
    /**
     * @var boolean whether to run the forked process.
     */
    protected $fork = true;

    public $gearmanComponent = 'gearman';


    public function actionStart()
    {
        $app = $this->getApplication();
        foreach ($app as $value) {
            $this->runApplication($value);
        }
    }

    public function actionStop()
    {
        $app = $this->getApplication();
        foreach ($app as $value) {

            $process = $value->getProcess($value->workerId);

            if ($process->isRunning()) {
                $this->stdout("Success: Process is stopped\n", Console::FG_GREEN);
            } else {
                $this->stdout("Failed: Process is not stopped\n", Console::FG_RED);
            }

            $process->stop();

        }

    }

    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
    }

    public function options($id)
    {
        $options = [];
        if (in_array($id, ['start', 'restart'])) {
            $options = ['fork'];
        }

        return array_merge(parent::options($id), $options);
    }

    protected function getApplication()
    {
        $component = Yii::$app->get($this->gearmanComponent);
        return $component->getApplication();
    }

    protected function runApplication(Application $app)
    {
        $fork = (bool)$this->fork;
        if ($fork) {
            $this->stdout("Success: Process is started\n", Console::FG_GREEN);
        } else {
            $this->stdout("Success: Process is started, but not daemonized\n", Console::FG_YELLOW);
        }

        $app->run((bool)$this->fork);
    }
}

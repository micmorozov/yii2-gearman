<?php

namespace micmorozov\yii2\gearman;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use micmorozov\yii2\gearman\Process;
use micmorozov\yii2\gearman\MasterApplication;

class GearmanController extends Controller
{
    /**
     * @var boolean whether to run the forked process.
     */
    protected $fork = true;

    public $gearmanComponent = 'gearman';

    protected function getMaster(){
    	return new MasterApplication($this->gearmanComponent, $this->fork);
    }

    public function actionStart()
    {
    	$master = $this->getMaster();
    	$master->start();
    }

    public function actionStop()
    {
    	$master = $this->getMaster();
    	$master->stop();
    }

    public function actionRestart()
    {
    	$master = $this->getMaster();
    	$master->restart();
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
}

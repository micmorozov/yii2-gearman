<?php
namespace micmorozov\yii2\gearman;

use Yii;
use micmorozov\yii2\gearman\Application;

declare(ticks = 1);
class MasterApplication
{
    /**
     * @var Application
     */
    private static $instance;
    
    private $gearmanComponent;
    private $fork;
    private $process;
    
    private $signalCommand;

    /**
     * @param unknown $gearmanComponent
     * @param unknown $fork
     */
    public function __construct($gearmanComponent, $fork)
    {
        $this->gearmanComponent = $gearmanComponent;
        $this->fork = $fork;
    }
    
    public function start(){
    	//после команды "старт" создадим дочерний поток,
    	//который будет следить за воркерами
    	
    	$this->stop();
    	
    	$pid = pcntl_fork();
    	
    	$observeMaster = (bool)$pid == 0;
    	
    	if($observeMaster){
    		//этот процесс будет следить за дочерними
    		$this->getProcess()->setPid(getmypid());
    		
    		//создаем дочернии процессы-воркеры
    		$apps = $this->getApplication();
    		foreach ($apps as $app) {
    			$parent = $this->startApp($app);
    			
    			//чтобы дочерние больше не порождали процессы
    			if( !$parent )
    				return ;
    		}
    		
    		if( $parent ){
    			//вешаем сигналы на мастера
    			$this->signalHandle();
    			
    			$this->observe();
    		}
    	}
    	else{
    		//родитель ничего не делает
    		return true;
    	}
    }
    
    protected function startApp($app){
    	return $this->runApplication($app);
    }
    
    public function stop(){
    	$process = $this->getProcess();
    	$process->stop();
    }
    
    /**
     * Остановка воркеров
     */
    protected function stopChildren(){
    	$app = $this->getApplication();
    	foreach ($app as $value) {
    		 
    		$process = $value->getProcess($value->workerId);
    		 
    		$process->stop(true);
    	}
    }
    
    public function restart(){
    	$this->stop();
    	$this->start();
    }
    
    protected function getApplication(){
    	$component = Yii::$app->get($this->gearmanComponent);
    	return $component->getApplication();
    }
    
    protected function runApplication(Application $app){
    	$fork = (bool)$this->fork;
    	if ($fork) {
    		//$this->stdout("Success: Process is started\n", Console::FG_GREEN);
    	} else {
    		//$this->stdout("Success: Process is started, but not daemonized\n", Console::FG_YELLOW);
    	}
    
    	$parent = $app->run((bool)$this->fork);
    	
    	return $parent;
    }
    
    protected function recoverChild(){
    	$pid = pcntl_waitpid(-1, $status, WNOHANG);
    	
    	// Пока есть завершенные дочерние процессы
    	while ($pid > 0) {
    		$app = $this->getAppByPid($pid);
    	
    		$parent = $this->startApp($app);
    		
    		if( !$parent )
    			return false;
    	
    		$pid = pcntl_waitpid(-1, $status, WNOHANG);
    	}
    	
    	return true;
    }
    
    protected function getAppByPid($pid){
    	$apps = $this->getApplication();
    	
    	foreach($apps as $app){
    		if( $pid == $app->getPid() )
    			return $app;
    	}
    	
    	return false;
    }
    
    /**
     * Ф-ция отслеживания команды
     */
    protected function observe(){
    	while(1){
    		sleep(5);
    		
    		if( $this->signalCommand == 'kill' ){
    			$this->stopChildren();
    			
    			exit(0);
    		}
    		
    		if( $this->signalCommand == 'signalChild' ){
    			$parent = $this->recoverChild();
    			
    			if( !$parent )
    				break;
    		}
    		
    		$this->signalCommand = '';
    	}
    }
    
    /**
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process)
    {
    	$this->process = $process;
    	return $this;
    }
    
    /**
     * @return Process
     */
    public function getProcess(){
    	if (null === $this->process) {
    		$this->setProcess(new Process(new Config(), "MasterApplication"));
    	}
    	return $this->process;
    }
    
    protected function signalHandle(){
    	pcntl_signal(SIGTERM, [$this, "signalKill"]);
    	pcntl_signal(SIGINT, [$this, "signalKill"]);
    	pcntl_signal(SIGCHLD, [$this, "signalChild"]);
    }
    
    public function signalKill($signo){
    	$this->signalCommand = 'kill';
    }
    
    public function signalChild(){
    	$this->signalCommand = 'signalChild';
    }
}

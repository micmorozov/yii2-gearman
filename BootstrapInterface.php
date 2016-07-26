<?php
namespace micmorozov\yii2\gearman;

interface BootstrapInterface
{
    public function run(Application $application);
}

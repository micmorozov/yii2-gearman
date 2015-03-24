<?php
namespace Apollo\gearman;

interface BootstrapInterface
{
    public function run(Application $application);
}

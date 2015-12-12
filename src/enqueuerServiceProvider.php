<?php

namespace morningtrain\enqueuer;

use Illuminate\Support\ServiceProvider;

class enqueuerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
	
		//Add the public disk for storing the scripts and styles cache
		$this->app['config']["filesystems.disks.public"] = [
			'driver' => 'local',
			'root'   => public_path(),
		];
	
		//Include the enqueuer class
		require_once 'enqueuer.php';
	
		//Add alias for the enqueuer facade.
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Enqueuer', '\morningtrain\enqueuer');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {	
		
    }
}
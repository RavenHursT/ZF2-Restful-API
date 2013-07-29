<?php
/**
 * Created by JetBrains PhpStorm.
 * User: matthewmarcus
 * Date: 4/22/13
 * Time: 5:08 PM
 * To change this template use File | Settings | File Templates.
 */

namespace BackgroundTasks;

use Abstracts\Model\AbstractRESTModule;
use Zend\EventManager\Event;
use \Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\MvcEvent;

class Module extends AbstractRESTModule {

	public function getModuleRootPath(){
		return __DIR__;
	}

	public function onBootstrap(Event $e){
		$e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_FINISH, function($e){
			$log = $e->getApplication()
				->getServiceManager()
				->get('EventLogger\Service\WandiscoLogger');

			print_r($e->getResponse());exit;
		}, -10000); //Spool up worker working loop as the LAST thing we do --mmarcus
	}
}
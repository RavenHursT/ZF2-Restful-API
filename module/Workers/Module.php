<?php
/**
 * Created by JetBrains PhpStorm.
 * User: matthewmarcus
 * Date: 4/22/13
 * Time: 5:08 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Workers;

use Wandisco\Model\AbstractRESTModule;
use Zend\EventManager\Event;

class Module extends AbstractRESTModule{
	protected
		$_gearmanWorker = NULL;

	public function getModuleRootPath(){
		return __DIR__;
	}

	public function onBootstrap(Event $e){
		parent::onBootstrap($e);
		$this->setWorker($this->getServiceManager()->get('mwGearman\Worker\Pecl'));
	}
}
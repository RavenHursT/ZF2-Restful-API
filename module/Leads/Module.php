<?php
/**
 * Created by JetBrains PhpStorm.
 * User: matthewmarcus
 * Date: 4/22/13
 * Time: 5:08 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Leads;

use Wandisco\Model\AbstractRESTModule;
use Zend\EventManager\Event;

class Module extends AbstractRESTModule{
	protected
		$_gearmanClient = NULL;

	public function getModuleRootPath(){
		return __DIR__;
	}

	public function onBootstrap(Event $e){
		parent::onBootstrap($e);
		$this->setGearmanClient($e->getApplication()
			->getServiceManager()
			->get('mwGearman\Client\Pecl')
		);
	}
}
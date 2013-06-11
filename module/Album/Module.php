<?php
/**
 * Created by JetBrains PhpStorm.
 * User: matthewmarcus
 * Date: 4/22/13
 * Time: 5:08 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Album;

use Application\Model\AbstractRESTModule;

class Module extends AbstractRESTModule{

	public function getModuleNamespace(){
		return __NAMESPACE__;
	}

	public function getModuleRootPath(){
		return __DIR__;
	}
}
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

class Module extends AbstractRESTModule{

	public function getModuleRootPath(){
		return __DIR__;
	}
}
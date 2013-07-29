<?php
/**
 * File: Leads.php
 * User: matthewmarcus
 * Date: 7/1/13
 * Time: 4:36 PM
 */

namespace BackgroundTasks\Workers;


use Abstracts\Model\AbstractBaseModel;
use mwGearman\Worker\Pecl;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Leads extends AbstractBaseModel{
	protected
		$_worker = NULL,
		$_id = NULL;

	public function __construct(){
	}

	public function init(){
		$this->_worker = $this->getServiceLocator()
			->get('mwGearman\Worker\Pecl')
			->connect();
		$this->_worker->register('create-lead', array($this, 'createLead'));

		return $this;
	}

	public function work(){

	}

	public function createLead($job){
		die(get_class($job));
	}
}
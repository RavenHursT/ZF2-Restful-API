<?php
/**
 * File: AlbumController.php
 * User: matthewmarcus
 * Date: 5/10/13
 * Time: 1:45 PM
 */

namespace Album\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;


class AlbumController extends AbstractRestfulController{
	public function getList()
	{
		die('getList()');
	}

	public function get($id)
	{
		die('getId($id)');
	}

	public function create($data)
	{
		die('create($data)');
	}

	public function update($id, $data)
	{
		die('update($id, $data)');
	}

	public function delete($id)
	{
		die('delete($id)');
	}
}
<?php
namespace LeadsTest\Model;

use Leads\Model\Leads;
use PHPUnit_Framework_TestCase;

class LeadsTest extends PHPUnit_Framework_TestCase
{
	public function testLeadsInitialState()
	{
		$lead = new Leads();

		$this->assertNull($lead->artist, '"artist" should initially be null');
		$this->assertNull($lead->id, '"id" should initially be null');
		$this->assertNull($lead->title, '"title" should initially be null');
	}

	public function testExchangeArraySetsPropertiesCorrectly()
	{
		$lead = new Lead();
		$data  = array(
			'id'     => 123
		);

		$lead->exchangeArray($data);

//		$this->assertSame($data['artist'], $lead->artist, '"artist" was not set correctly');
		$this->assertSame($data['id'], $lead->id, '"id" was not set correctly');
//		$this->assertSame($data['title'], $lead->title, '"title" was not set correctly');
	}

	public function testExchangeArraySetsPropertiesToNullIfKeysAreNotPresent()
	{
		$lead = new Lead();

		$lead->exchangeArray(array('artist' => 'some artist',
			'id'     => 123,
			'title'  => 'some title'));
		$lead->exchangeArray(array());

		$this->assertNull($lead->artist, '"artist" should have defaulted to null');
		$this->assertNull($lead->id, '"id" should have defaulted to null');
		$this->assertNull($lead->title, '"title" should have defaulted to null');
	}
}
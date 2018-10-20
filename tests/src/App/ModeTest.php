<?php

namespace Friendica\Test\src\App;

use Friendica\App\Mode;
use Friendica\Test\Util\VFSTrait;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ModeTest extends TestCase
{
	use VFSTrait;

	public function setUp()
	{
		parent::setUp(); // TODO: Change the autogenerated stub

		$this->setUpVfsDir();
	}

	public function testItEmpty()
	{
		$mode = new Mode($this->root->url());
		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());
	}

	public function testWithoutConfig()
	{
		$mode = new Mode($this->root->url());

		$this->assertTrue($this->root->hasChild('config/local.ini.php'));

		$this->delConfigFile('local.ini.php');

		$this->assertFalse($this->root->hasChild('config/local.ini.php'));

		$mode->determine();

		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());

		$this->assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithoutDatabase()
	{
		$dba =  \Mockery::mock('alias:Friendica\Database\DBA');
		$dba
			->shouldReceive('connected')
			->andReturn(false);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		$this->assertFalse($mode->has(Mode::DBAVAILABLE));
	}

	public function testWithoutDatabaseSetup()
	{
		$dba =  \Mockery::mock('alias:Friendica\Database\DBA');
		$dba
			->shouldReceive('connected')
			->andReturn(true);
		$dba
			->shouldReceive('fetchFirst')
			->with('SHOW TABLES LIKE \'config\'')
			->andReturn(false);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithMaintenanceMode()
	{
		$dba =  \Mockery::mock('alias:Friendica\Database\DBA');
		$dba
			->shouldReceive('connected')
			->andReturn(true);
		$dba
			->shouldReceive('fetchFirst')
			->with('SHOW TABLES LIKE \'config\'')
			->andReturn(true);

		$conf = \Mockery::mock('alias:Friendica\Core\Config');
		$conf
			->shouldReceive('get')
			->with('system', 'maintenance')
			->andReturn(true);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testNormalMode()
	{
		$dba =  \Mockery::mock('alias:Friendica\Database\DBA');
		$dba
			->shouldReceive('connected')
			->andReturn(true);
		$dba
			->shouldReceive('fetchFirst')
			->with('SHOW TABLES LIKE \'config\'')
			->andReturn(true);

		$conf = \Mockery::mock('alias:Friendica\Core\Config');
		$conf
			->shouldReceive('get')
			->with('system', 'maintenance')
			->andReturn(false);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertTrue($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}
}
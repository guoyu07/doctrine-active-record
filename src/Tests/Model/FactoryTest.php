<?php

namespace Doctrine\ActiveRecord\Tests\Model;

use Doctrine\ActiveRecord\Model\Factory;
use Doctrine\ActiveRecord\Dao\Factory as DaoFactory;
use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class FactoryTest extends UnitTestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var DaoFactory
     */
    protected $daoFactory;

    public function setUp()
    {
        $db = $this->get('dbal.connection');
        $this->daoFactory = new DaoFactory($db);
        $this->factory = new Factory ($this->daoFactory);
    }

    public function testGetDao()
    {
        $this->daoFactory->setFactoryNamespace('');
        $this->daoFactory->setFactoryPostfix('');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Dao\TestDao', $this->factory->getDao('Doctrine\ActiveRecord\Tests\Dao\TestDao'));

        $this->daoFactory->setFactoryNamespace('');
        $this->daoFactory->setFactoryPostfix('Dao');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Dao\TestDao', $this->factory->getDao('Doctrine\ActiveRecord\Tests\Dao\Test'));

        $this->daoFactory->setFactoryNamespace('Doctrine\ActiveRecord\Tests\Dao');
        $this->daoFactory->setFactoryPostfix('Dao');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Dao\TestDao', $this->factory->getDao('Test'));

        $this->daoFactory->setFactoryNamespace('Doctrine\ActiveRecord\Tests\Dao');
        $this->daoFactory->setFactoryPostfix('');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Dao\TestDao', $this->factory->getDao('TestDao'));
    }

    public function testGetModel()
    {
        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Model\UserModel', $this->factory->getModel('Doctrine\ActiveRecord\Tests\Model\UserModel'));

        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('Model');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Model\UserModel', $this->factory->getModel('Doctrine\ActiveRecord\Tests\Model\User'));

        $this->factory->setFactoryNamespace('Doctrine\ActiveRecord\Tests\Model');
        $this->factory->setFactoryPostfix('Model');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Model\UserModel', $this->factory->getModel('User'));

        $this->factory->setFactoryNamespace('Doctrine\ActiveRecord\Tests\Model');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('Doctrine\ActiveRecord\Tests\Model\UserModel', $this->factory->getModel('UserModel'));
    }

    public function testGetFactoryNamespace()
    {
        $this->assertEquals('', $this->factory->getFactoryNamespace());
        $this->factory->setFactoryNamespace('Doctrine\ActiveRecord\Tests\Model');
        $this->assertEquals('\Doctrine\ActiveRecord\Tests\Model', $this->factory->getFactoryNamespace());
    }

    public function testGetFactoryPostfix()
    {
        $this->assertEquals('Model', $this->factory->getFactoryPostfix());
        $this->factory->setFactoryPostfix('');
        $this->assertEquals('', $this->factory->getFactoryPostfix());
    }

    /**
     * @expectedException \Doctrine\ActiveRecord\Exception\FactoryException
     */
    public function testGetDaoException()
    {
        $this->factory->getDao('FooBar');
    }

    /**
     * @expectedException \Doctrine\ActiveRecord\Exception\FactoryException
     */
    public function testGetModelException()
    {
        $this->factory->getModel('FooBar');
    }
}
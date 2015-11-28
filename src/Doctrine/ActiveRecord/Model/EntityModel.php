<?php

namespace Doctrine\ActiveRecord\Model;

use Doctrine\DBAL\Connection as Db;
use Doctrine\ActiveRecord\Exception\Exception;
use Doctrine\ActiveRecord\Exception\ModelException;
use Doctrine\ActiveRecord\Exception\FindException;
use Doctrine\ActiveRecord\Exception\CreateException;
use Doctrine\ActiveRecord\Exception\UpdateException;
use Doctrine\ActiveRecord\Exception\DeleteException;
use Doctrine\ActiveRecord\Exception\NotFoundException;
use Doctrine\ActiveRecord\Dao\Dao;
use Doctrine\ActiveRecord\Dao\EntityDao;

/**
 * EntityModel implements a large number of standard ActiveRecord use-cases that
 * depend on Doctrine\ActiveRecord\Dao\EntiyDao instead of Doctrine\ActiveRecord\Dao\Dao
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
abstract class EntityModel extends Model
{
    /**
     * @param $db Db The current database connection instance
     * @param $dao EntityDao An instance of a DOA to initialize this instance (otherwise, you must call find/search)
     */
    public function __construct(Db $db, EntityDao $dao = null)
    {
        parent::__construct($db, $dao);
    }

    /**
     * Returns entity DAO
     *
     * @throws ModelException
     * @return EntityDao
     */
    protected function getEntityDao()
    {
        $dao = $this->getDao();

        if ($dao instanceof EntityDao) {
            return $dao;
        }

        throw new ModelException('DAO is not an EntityDao');
    }

    /**
     * @param string $name
     * @param Dao|null $dao
     * @return EntityModel|Model
     * @throws Exception
     */
    public function factory($name = '', Dao $dao = null)
    {
        return parent::factory($name, $dao);
    }

    /**
     * Find a record by primary key
     *
     * @param int $id
     * @return $this
     */
    public function find($id)
    {
        $this->getEntityDao()->find($id);

        return $this;
    }

    /**
     * Reload values from database
     *
     * @return $this
     */
    public function reload()
    {
        $this->getEntityDao()->reload();

        return $this;
    }

    /**
     * @param array $cond
     * @param bool $wrapResult
     * @throws FindException
     * @return array
     */
    public function findAll(array $cond = array(), $wrapResult = true)
    {
        $result = $this->getEntityDao()->findAll($cond, $wrapResult);

        if (!is_array($result)) {
            throw new FindException('DAO findAll() return value is not an array');
        }

        if ($wrapResult) {
            $result = $this->wrapAll($result);
        }

        return $result;
    }

    /**
     * Wraps all DAO result entities in model instances
     *
     * @param array $result
     * @return array
     */
    protected function wrapAll(array $result)
    {
        $modelName = $this->getModelName();

        foreach ($result as &$entity) {
            $entity = $this->factory($modelName, $entity);
        }

        return $result;
    }

    /**
     * Perform a search ($options can contain count, offset and/or sort order; the return value array
     * also contains count, offset, sort order plus the total number of results; see DAO documentation)
     *
     * @param array $cond The search conditions as array
     * @param array $options The optional search options as array
     * @throws FindException
     * @return array
     */
    public function search(array $cond, array $options = array())
    {
        $params = $options + array('cond' => $cond);

        $result = $this->getEntityDao()->search($params);

        if (!is_array($result)) {
            throw new FindException('DAO search() return value is not an array');
        }

        if (!isset($options['ids_only']) || $options['ids_only'] == false) {
            $result['rows'] = $this->wrapAll($result['rows']);
        }

        return $result;
    }

    /**
     * Simple version of search(), similar to findAll()
     *
     * @param array $cond The search conditions as array
     * @param mixed $order The sort order (use an array for multiple columns)
     * @return array
     */
    public function searchAll(array $cond = array(), $order = false)
    {
        $options = array(
            'order' => $order,
            'count' => 0,
            'offset' => 0
        );

        $result = $this->search($cond, $options);

        return $result['rows'];
    }

    /**
     * Search a single record; throws an exception if 0 or more than one record are found
     *
     * @param array $cond The search conditions as array
     * @throws NotFoundException
     * @return array
     */
    public function searchOne(array $cond = array())
    {
        $options = array(
            'count' => 1,
            'offset' => 0
        );

        $result = $this->search($cond, $options);

        if ($result['total'] != 1) {
            throw new NotFoundException($result['total'] . ' matching items found');
        }

        return $result['rows'][0];
    }

    /**
     * Returns an array of matching primary keys for the given search condition
     *
     * @param array $cond
     * @param array $options
     * @throws FindException
     * @return array
     */
    public function searchIds(array $cond, array $options = array())
    {
        $params = $options + array('cond' => $cond);

        $params['ids_only'] = true;

        $result = $this->getEntityDao()->search($params);

        if (!is_array($result)) {
            throw new FindException('DAO search() return value is not an array');
        }

        return $result;
    }

    /**
     * Return the ID of the currently loaded entity (throws exception, if empty)
     *
     * @return mixed Primary key
     */
    public function getId()
    {
        return $this->getEntityDao()->getId();
    }

    /**
     * Returns true, if the model instance has an ID assigned (primary key)
     *
     * @return bool
     */
    public function hasId()
    {
        return $this->getEntityDao()->hasId();
    }

    /**
     * Return all model instance values
     * Note: Result is empty for new instances: you must assign values or call find/search first!
     *
     * @return array Model property values
     */
    public function getValues()
    {
        return $this->getEntityDao()->getValues();
    }

    /**
     * Return the common name of this entity (for lists or box titles)
     *
     * Should be overwritten by inherited classes
     *
     * @return string
     */
    public function getEntityTitle()
    {
        return $this->_daoName . ' ' . $this->getId();
    }

    /**
     * Returns true, if this model instance can be deleted
     * (not related to user's specific rights, which can be different)
     *
     * @return bool
     */
    public function isDeletable()
    {
        return true;
    }

    /**
     * Returns true, if this model instance can be updated
     * (not related to user's specific rights, which can be different)
     *
     * @return bool
     */
    public function isUpdatable()
    {
        return true;
    }

    /**
     * Returns true, if new entities can be created in the database
     * (not related to user's specific rights, which can be different)
     *
     * @return bool
     */
    public function isCreatable()
    {
        return true;
    }

    /**
     * Update the data of multiple DAO instances
     *
     * @param array $ids The IDs (primary keys) of the entities to be changed
     * @param array $properties The properties to be changed
     * @throws UpdateException
     * @return object this
     */
    public function batchEdit(array $ids, array $properties)
    {
        $this->getEntityDao()->beginTransaction();

        try {
            foreach ($ids as $id) {
                $dao = $this->daoFactory()->find($id);

                foreach ($properties as $key => $value) {
                    $dao->$key = $value;
                }

                $dao->update();
            }

            $this->getEntityDao()->commit();
        } catch (Exception $e) {
            $this->getEntityDao()->rollBack();

            throw new UpdateException ('Batch edit was not successful: ' . $e->getMessage());
        }


        return $this;
    }

    /**
     * Returns the name of the associated main database table
     * Note: Needed for search filters or security checks
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->getEntityDao()->getTableName();
    }

    /**
     * Magic getter
     *
     */
    public function __get($name)
    {
        return $this->getEntityDao()->$name;
    }

    /**
     * Magic setter
     *
     * Throws exception, because Models should implement use cases and not just
     * change data based on field names. Each specific use case needs a separate
     * method. Alternatively you can use update($values) and create($values), if
     * values are coming from a trusted source, e.g. a form class.
     */
    public function __set($name, $value)
    {
        throw new ModelException (
            'A use case specific method must be implemented to change any ' .
            'model data. Magic setters are therefore not available. ' .
            'Model: ' . $this->getModelName() . ', Property: ' . $name
        );
    }

    /**
     * Returns true, if timestamps are enabled in the associated main DAO
     *
     * @return bool
     */
    public function hasTimestampEnabled()
    {
        return $this->getEntityDao()->hasTimestampEnabled();
    }

    /**
     * Permanently deletes the entity instance
     *
     * @throws DeleteException
     */
    public function delete()
    {
        if (!$this->hasId()) {
            throw new DeleteException('ID not set - did you call find($id) before delete()?');
        }

        if (!$this->isDeletable()) {
            throw new DeleteException('Permission denied: Entity can not be deleted');
        }

        $this->transactional(function () {
            $this->_delete();
        });

        return $this;
    }

    /**
     * Deletes the entity without transaction & permission checks
     */
    protected function _delete()
    {
        $dao = $this->getEntityDao();
        $dao->delete();

        $this->resetDao();
    }

    /**
     * Updates the entity using the given values
     *
     * @param array $values
     * @throws UpdateException
     * @return EntityModel
     */
    public function update(array $values)
    {
        if (!$this->hasId()) {
            throw new UpdateException('ID not set - did you call find($id) before update($values)?');
        }

        if (!$this->isUpdatable()) {
            throw new UpdateException('Permission denied: Entity can not be updated');
        }

        $this->transactional(function () use ($values) {
            $this->_update($values);
        });

        return $this;
    }

    /**
     * Updates the entity without transaction & permission checks
     *
     * @param array $values
     * @throws ModelException
     */
    protected function _update(array $values)
    {
        $dao = $this->getEntityDao();

        $dao->setValues($values);

        $dao->update();
    }

    /**
     * Creates a new entity using the given values
     *
     * @param array $values
     * @throws CreateException
     * @return EntityModel
     */
    public function create(array $values)
    {
        if (!$this->isCreatable()) {
            throw new CreateException('Permission denied: Entity can not be created');
        }

        $this->transactional(function () use ($values) {
            $this->_create($values);
        });

        return $this;
    }

    /**
     * Creates the entity without transaction & permission checks
     *
     * @param array $values
     * @throws ModelException
     */
    protected function _create(array $values)
    {
        $dao = $this->getEntityDao();

        $dao->setValues($values);

        $dao->insert();

        $dao->reload();
    }
}
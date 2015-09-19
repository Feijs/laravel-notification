<?php

namespace Tricki\Notification\Models;

use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract class allowing for Single Table Inheritence
 *
 * @package Tricki/Laravel-notification
 * @author Thomas Rickenbach
 * @author Mike Feijs <mike@feijs.nl>
 */
abstract class AbstractEloquent extends Eloquent\Model
{
	protected $isSuperType = false; // set true in super-class model
	protected $isSubType = false; // set true in inherited models
	protected $typeField = 'type'; //override as needed, only set on the super-class model

	/**
	 * Provide an attributes to object map
	 *
	 * @return Model
	 */

	public function mapData(array $attributes)
	{
		if (!$this->isSuperType)
		{
			return $this->newInstance();
		}
		else
		{
			if (!isset($attributes[$this->typeField]))
			{
				throw new \DomainException($this->typeField . ' not present in the records of a Super Model');
			}
			else
			{
				$class = $attributes[$this->typeField];
				return new $class;
			}
		}
	}

	/**
     * Create a new model instance requested by the builder.
     *
     * @param  array  $attributes
     * @return static
     */
    public function newFromBuilder($attributes = array())
    {
        $model = $this->mapData((array) $attributes)->newInstance(array(), true);

        $model->setRawAttributes((array) $attributes, true);

        return $model;
    }

	/**
     * Get a new query builder for the model.
     * set any type of scope you want on this builder in a child class, and it'll
     * keep applying the scope on any read-queries on this model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $builder = parent::newQuery();

        if ($this->isSubType())
        {
            $builder->where($this->typeField, $this->getType());
        }

        return $builder;
    }

	protected function isSubType()
	{
		return $this->isSubType;
	}

	protected function getType()
	{
		return get_class($this);
	}

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save(array $options = array())
	{
		if ($this->isSubType())
		{
			$this->attributes[$this->typeField] = $this->getType();
		}

		return parent::save($options);
	}

}

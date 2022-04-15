<?php

/*!
 * Tiny ORM Framework
 * 
 * MIT License
 * 
 * Copyright (c) 2020 - 2021 "Ildar Bikmamatov" <support@bayrell.org>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace TinyORM;


class Model implements \ArrayAccess
{
	
	public $__old_data = null;
	public $__new_data = [];
	public $__is_dirty = false;
	
	
	
	/**
	 * Return table name
	 */
	static function getTableName()
	{
		return "";
	}
	
	
	
	/**
	 * Return if auto increment
	 */
	static function isAutoIncrement()
	{
		return false;
	}
	
	
	
	/**
	 * Return list of primary keys
	 */
	static function pk()
	{
		return null;
	}
	
	
	
	/**
	 * Returns true if need to update timestamp
	 */
	static function updateTimestamp()
	{
		return false;
	}
	
	
	
	/**
	 * Return primary key
	 */
	static function getPrimaryData($arr)
	{
		$pk = static::pk();
		if ($pk)
		{
			$res = [];
			foreach ($pk as $key)
			{
				$res[$key] = isset($arr[$key]) ? $arr[$key] : null;
			}
			return $res;
		}
		return null;
	}
	
	
	
	/**
	 * Return first primary key
	 */
	static function firstPk()
	{
		$keys = static::pk();
		if ($keys == null) return null;
		
		$pk = array_shift($keys);
		if ($pk == null) return null;
		
		return $pk;
	}
	
	
	
	/**
	 * To database
	 */
	static function to_database($data, $is_update)
	{
		return $data;
	}
	
	
	
	/**
	 * From database
	 */
	static function from_database($data)
	{
		return $data;
	}
	
	
	
	/**
	 * Create Instance of class
	 */
	static function Instance($data = null)
	{
		$class = static::class;
		$instance = new $class();
		$instance->setNewData($data);
		return $instance;
	}
	
	
	
	/**
	 * Create Instance of class
	 */
	static function InstanceFromDatabase($data = null)
	{
		$data = static::from_database($data);
		$item = static::Instance($data);
		return $item;
	}
	
	
	
	/**
	 * Query to dabase
	 */
	static function query($connection_name = "default")
	{
		$q = (new Query())
			->connect($connection_name)
			->model( static::class )
			->fields(["t.*"])
		;
		return $q;
	}
	
	
	
	/**
	 * Query to dabase
	 */
	static function selectQuery($connection_name = "default")
	{
		$q = static::query($connection_name)
			->kind(Query::QUERY_SELECT)
		;
		return $q;
	}
	
	
	
	/**
	 * Update model
	 */
	static function updateQuery($connection_name = "default")
	{
		$q = static::query($connection_name)
			->kind(Query::QUERY_UPDATE)
		;
		return $q;
	}
	
	
	
	/**
	 * Insert data
	 */
	static function insertQuery($connection_name = "default")
	{
		$q = static::query($connection_name)
			->kind(Query::QUERY_INSERT)
		;
		return $q;
	}
	
	
	
	/**
	 * Delete data
	 */
	static function deleteQuery($connection_name = "default")
	{
		$q = static::query($connection_name)
			->kind(Query::QUERY_DELETE)
		;
		return $q;
	}
	
	
	
	/**
	 * Return item by id
	 */
	static function getById($id, $connection_name = "default")
	{
		$db = app("db_connection_list")->get($connection_name);
		
		$pk = static::firstPk();
		if ($pk == null) return null;
		
		$query = static::selectQuery()
			->addFilter($pk, "=", $id)
			->limit(1)
		;
		
		$item = $query->one();
		
		return $item;
	}
	
	
	
	/**
	 * Find or create
	 */
	static function findOrCreate($filter, $connection_name = "default")
	{
		$item = static::select($connection_name)
			->filter($filter)
			->one()
		;
		if ($item == null)
		{
			$item = static::Instance();
		}
		return $item;
	}
	
	
	
	/**
	 * Save to database
	 */
	function save($connection_name = "default")
	{
		$db = app("db_connection_list")->get($connection_name);
		
		$is_update = $this->isUpdate();
		$new_data = $this->getUpdatedData();
		$new_data = static::to_database($new_data, $is_update);
		
		/* Update */
		if ($is_update)
		{
			if (count($new_data) > 0)
			{
				$primary_data = static::getPrimaryData($this->__old_data);
				if ($primary_data)
				{
					$where = [];
					foreach ($primary_data as $key => $value)
					{
						$where[] = [ $key, "=", $value ];
					}
					if (static::updateTimestamp())
					{
						$new_data["gmtime_updated"] = gmdate("Y-m-d H:i:s", time());
					}
					$db->update
					(
						static::getTableName(),
						$where,
						$new_data
					);
				}
			}
		}
		
		/* Insert */
		else
		{
			if (static::updateTimestamp())
			{
				$new_data["gmtime_created"] = gmdate("Y-m-d H:i:s", time());
				$new_data["gmtime_updated"] = gmdate("Y-m-d H:i:s", time());
			}
			
			$db->insert
			(
				static::getTableName(),
				$new_data
			);
			
			if (static::isAutoIncrement())
			{
				$id = $db->lastInsertId();
				
				$pk = static::firstPk();
				if ($pk != null)
				{
					$this->__new_data[$pk] = $id;
				}
			}
		}
		
		$this->refresh();
		// $this->setNewData($this->__new_data);
		
		return $this;
	}
	
	
	
	/**
	 * Delete item
	 */
	function delete($connection_name = "default")
	{
		$db = app("db_connection_list")->get($connection_name);
		$primary_data = static::getPrimaryData($this->__old_data);
		if ($primary_data)
		{
			$where = [];
			foreach ($primary_data as $key => $value)
			{
				$where[] = [ $key, "=", $value ];
			}
			$db->delete(static::getTableName(), $where);
		}
		
		return $this;
	}
	
	
	
	/**
	 * Refresh model from database by id
	 */
	function refresh($connection_name = "default")
	{
		$db = app("db_connection_list")->get($connection_name);
		
		$item = null;
		$where = static::getPrimaryData($this->__old_data);
		
		if ($where)
		{
			$filter = [];
			foreach ($where as $key => $value)
			{
				$filter[] = [$key, "=", $value];
			}
			
			$item = static::selectQuery()
				->where($filter)
				->one(true)
			;
			$item = static::from_database($item);
		}
		
		$this->setNewData($item);
		
		return $this;
	}
	
	
	
	/**
	 * Returns true if data has loaded from database
	 */
	function hasLoaded()
	{
		return $this->__old_data ? true : false;
	}
	
	
	
	/**
	 * Returns true if object is new
	 */
	function isNew()
	{
		return $this->__old_data ? false : true;
	}
	
	
	
	/**
	 * Returns true if data has loaded from database
	 */
	function isUpdate()
	{
		return $this->__old_data ? true : false;
	}
	
	
	
	/**
	 * Returns true if model is changed
	 */
	function isDirty()
	{
		return $this->__is_dirty;
	}
	
	
	
	/**
	 * Set new data
	 */
	function setNewData($data)
	{
		$this->__is_dirty = false;
		$this->__old_data = $data;
		$this->__new_data = $data;
		if ($this->__new_data == null)
		{
			$this->__new_data = [];
		}
	}
	
	
	
	/**
	 * Returns updated data
	 */
	function getUpdatedData()
	{
		if ($this->__new_data == null) return [];
		
		$res = [];
		foreach ($this->__new_data as $field_name => $new_value)
		{
			if ($this->__old_data == null)
			{
				$res[$field_name] = $new_value;
			}
			else
			{
				if (!array_key_exists($field_name, $this->__old_data))
				{
					$res[$field_name] = $new_value;
				}
				else
				{
					$old_value = $this->__old_data[$field_name];
					if ($new_value != $old_value)
					{
						$res[$field_name] = $new_value;
					}
				}
			}
		}
		
		// var_dump($this->__old_data);
		// var_dump($this->__new_data);
		// var_dump($res);
		
		return $res;
	}
	
	
	
	/**
	 * Restore field to old value
	 */
	function restoreField($field_name)
	{
		if ($this->__new_data == null) return;
		if (!isset($this->__new_data[$field_name])) return;
		if ($this->__old_data != null)
		{
			if (isset($this->__old_data[$field_name]))
			{
				$this->__new_data[$field_name] = $this->__old_data[$field_name];
			}
			else
			{
				unset($this->__new_data[$field_name]);
			}
		}
		else
		{
			unset($this->__new_data[$field_name]);
		}
	}
	
	
	
	/**
	 * Getter and Setter
	 */
	public function get($key, $value = null)
	{
		return $this->__new_data &&
			isset($this->__new_data[$key]) ? $this->__new_data[$key] : $value;
	}
	public function getOld($key, $value = null)
	{
		return $this->__old_data &&
			isset($this->__old_data[$key]) ? $this->__old_data[$key] : $value;
	}
	public function set($key, $value)
	{
		if (!$this->__new_data)
		{
			$this->__new_data = [];
		}
		
		if (!isset($this->__new_data[$key])) $this->__is_dirty = true;
		else if ($this->__new_data[$key] != $value) $this->__is_dirty = true;
		
		$this->__new_data[$key] = $value;
	}
	public function exists($key)
	{
		return $this->__new_data && isset($this->__new_data[$key]);
	}
	public function unset($key)
	{
		if ($this->__new_data && isset($this->__new_data[$key]))
		{
			unset($this->__new_data[$key]);
		}
	}
	
	
	
	/**
	 * To array
	 */
	function toArray()
	{
		return $this->__new_data ? $this->__new_data : [];
	}
	
	
	
	/**
	 * List to array
	 */
	static function listToArray($items)
	{
		return array_map
		(
			function ($model){ return ($model instanceof Model) ? $model->toArray() : $model; },
			$items
		);
	}
	
	
	/**
	 * Array methods
	 */
	public function offsetExists($key)
	{
		return $this->exists($key);
    }
    public function offsetUnset($offset)
	{
		$this->unset($key);
    }
    public function offsetGet($key)
	{
		return $this->get($key);
    }
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
    }
	
	
	
	/**
	 * Magic methods
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	public function __get($key)
	{
		return $this->get($key);
	}
	public function __isset($key)
	{
		return $this->exists($key);
	}
	public function __unset($key)
	{
		$this->unset($key);
	}
}
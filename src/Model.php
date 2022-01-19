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
	static function to_database($data)
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
	 * Return item by id
	 */
	static function getById($id)
	{
		$db = app("db");
		
		$pk = static::firstPk();
		if ($pk == null) return null;
		
		$arr = [];
		$arr[$pk] = $id;
		
		$item = $db->query("select * from `" . static::getTableName() . "` where `" . $pk . "`=:" . $pk, $arr);
		
		if ($item)
		{
			$item = static::from_database($item);
			$item = static::Instance($item);
		}
		else
		{
			$item = null;
		}
		
		return $item;
	}
	
	
	
	/**
	 * Save to database
	 */
	function save()
	{
		$db = app("db");
		
		$is_update = $this->__old_data != null;
		
		$new_data = static::to_database($this->__new_data);
		
		/* Update */
		if ($is_update)
		{
			$where = static::getPrimaryData($this->__old_data);
			if ($where)
			{
				$db->update
				(
					static::getTableName(),
					$where,
					$new_data
				);
			}
		}
		
		/* Insert */
		else
		{
			$db->insert
			(
				static::getTableName(),
				$new_data
			);
			
			if (static::isAutoIncrement())
			{
				$id = $db->insert_id();
				
				$pk = static::firstPk();
				if ($pk != null)
				{
					$this->__new_data[$pk] = $id;
				}
			}
		}
		
		$this->setNewData($this->__new_data);
	}
	
	
	
	/**
	 * Refresh model from database by id
	 */
	function refresh()
	{
		$db = app("db");
		
		$item = null;
		$where = static::getPrimaryData($this->__old_data);
		
		if ($where)
		{
			$where_str = [];
			foreach ($where as $key => $value)
			{
				$where_str[] = "`" . $key . "` = :" . $key;
			}
			$where_str = implode(" and ", $where_str);
			
			$item = $db->get_row("select * from `" . static::getTableName() . "` where " . $where_str, $where);
			$item = static::from_database($item);
		}
		
		$this->setNewData($item);
	}
	
	
	
	/**
	 * Returns true if data has loaded from database
	 */
	function hasLoaded()
	{
		return $this->__old_data ? true : false;
	}
	
	
	
	/**
	 * Set new data
	 */
	function setNewData($data)
	{
		$this->__old_data = $data;
		$this->__new_data = $data;
		if ($this->__new_data == null)
		{
			$this->__new_data = [];
		}
	}
	
	
	
	/**
	 * Getter and Setter
	 */
	public function get($key, $value = null)
	{
		return $this->__new_data && isset($this->__new_data[$key]) ? $this->__new_data[$key] : $value;
	}
	public function getOld($key, $value = null)
	{
		return $this->__old_data && isset($this->__old_data[$key]) ? $this->__old_data[$key] : $value;
	}
	public function set($key, $value)
	{
		if (!$this->__new_data)
		{
			$this->__new_data = [];
		}
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
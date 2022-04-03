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



class Cursor
{
	public $connection = null;
	public $sql = null;
	public $params = null;
	public $st = null;
	public $query = null;
	
	
	
	/**
	 * Return sql
	 */
	function getSQL()
	{
		$sql = $this->connection->getSQL($this->sql, $this->params);
		return $sql;
	}
	
	
	
	/**
	 * Fetch next row
	 */
	function fetch($is_raw = false)
	{
		if ($this->st == null) return null;
		$row = $this->st->fetch(\PDO::FETCH_ASSOC);
		
		if ($row && $this->query && $this->query->_model_class_name && !$is_raw)
		{
			$class_name = $this->query->_model_class_name;
			$row = $class_name::InstanceFromDatabase($row);
		}
		
		return $row;
	}
	
	
	
	/**
	 * Fetch all rows
	 */
	function fetchAll($is_raw = false)
	{
		if ($this->st == null) return [];
		$items = $this->st->fetchAll(\PDO::FETCH_ASSOC);
		
		if ($this->query && $this->query->_model_class_name && !$is_raw)
		{
			$class_name = $this->query->_model_class_name;
			
			$items = array_map(
				function ($item) use ($class_name)
				{
					$item = $class_name::from_database($item);
					$item = $class_name::Instance($item);
					return $item;
				},
				$items
			);
		}
		
		return $items;
	}
	
	
	
	/**
	 * Close cursor
	 */
	function close()
	{
		$this->st->closeCursor();
		$this->st = null;
	}
	
	
	
	
	/**
	 * Returns last insert id
	 */
	function lastInsertId()
	{
		return $this->connection->lastInsertId();
	}
	
	
	
	/**
	 * Returns row count
	 */
	function rowCount()
	{
		return $this->st->rowCount();
	}
	
	
	
	/**
	 * Returns found rows
	 */
	function foundRows()
	{
		return $this->connection->foundRows();
	}
}
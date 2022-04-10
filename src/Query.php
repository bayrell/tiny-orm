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


class Query
{
	const QUERY_RAW = "raw";
	const QUERY_SELECT = "select";
	const QUERY_INSERT = "insert";
	const QUERY_UPDATE = "update";
	const QUERY_DELETE = "delete";
	const QUERY_INSERT_OR_UPDATE = "insert_or_update";
	
	public $_connection = null;
	public $_model_class_name = "";
	public $_kind = "";
	public $_table_name = "";
	public $_table_name_alias = "t";
	public $_fields = null;
	public $_join = null;
	public $_order = null;
	public $_filter = null;
	public $_start = 0;
	public $_limit = -1;
	public $_as_record = true;
	public $_calc_found_rows = false;
	public $_log = false;
	public $_sql = "";
	public $_params = [];
	public $_values = [];
	
	
	
	/**
	 * Calc found rows
	 */
	function raw($sql, $params)
	{
		$this->_kind = static::QUERY_RAW;
		$this->_sql = $sql;
		$this->_params = $params;
		return $this;
	}
	
	
	
	/**
	 * Calc found rows
	 */
	function calcFoundRows($value = true)
	{
		$this->_calc_found_rows = $value;
		return $this;
	}
	
	
	
	/**
	 * Select query
	 */
	function model($class_name)
	{
		if ($class_name == null)
		{
			$this->_model_class_name = null;
		}
		else
		{
			$this->_model_class_name = $class_name;
			$this->_table_name = $class_name::getTableName();
		}
		return $this;
	}
	
	
	
	/**
	 * Set alias
	 */
	function alias($alias_name)
	{
		$this->_table_name_alias = $alias_name;
		return $this;
	}
	
	
	
	/**
	 * Set debug log
	 */
	function debug($value)
	{
		$this->_log = $value;
		return $this;
	}
	
	
	
	/**
	 * Select query
	 */
	function select($table_name)
	{
		$this->_kind = static::QUERY_SELECT;
		$this->_fields = ["*"];
		$this->_table_name = $table_name;
		$this->_table_name_alias = "t";
		return $this;
	}
	
	
	
	/**
	 * Insert query
	 */
	function insert($table_name, $values = [])
	{
		$this->_kind = static::QUERY_INSERT;
		$this->_values = $values;
		$this->_table_name = $table_name;
		$this->_table_name_alias = "t";
		return $this;
	}
	
	
	
	/**
	 * Select query
	 */
	function update($table_name, $values = [])
	{
		$this->_kind = static::QUERY_UPDATE;
		$this->_values = $values;
		$this->_table_name = $table_name;
		$this->_table_name_alias = "t";
		return $this;
	}
	
	
	
	/**
	 * Delete query
	 */
	function delete($table_name)
	{
		$this->_kind = static::QUERY_DELETE;
		$this->_values = $values;
		$this->_table_name = $table_name;
		$this->_table_name_alias = "t";
		return $this;
	}
	
	
	
	/**
	 * Set kind
	 */
	function kind($kind)
	{
		$this->_kind = $kind;
		return $this;
	}
	
	
	
	/**
	 * Set fields
	 */
	function fields($fields)
	{
		$this->_fields = $fields;
		return $this;
	}
	
	
	
	/**
	 * Set values
	 */
	function values($values = [])
	{
		$this->_values = $values;
		return $this;
	}
	
	
	
	/**
	 * Add page
	 */
	function page($page, $limit)
	{
		$this->_start = $page * $limit;
		$this->_limit = $limit;
		return $this;
	}
	
	
	
	/**
	 * Set offset
	 */
	function offset($start, $limit = null)
	{
		$this->_start = $start;
		if ($limit !== null) $this->_limit = $limit;
		return $this;
	}
	
	
	
	/**
	 * Set start
	 */
	function start($start)
	{
		$this->_start = $start;
		return $this;
	}
	
	
	
	/**
	 * Set limit
	 */
	function limit($limit)
	{
		$this->_limit = $limit;
		return $this;
	}
	
	
	
	/**
	 * Set order
	 */
	function orderBy($name, $sort)
	{
		$this->_order[] = [$name, $sort];
		return $this;
	}
	
	
	
	/**
	 * Set filter
	 */
	function where($filter)
	{
		$this->_filter = $filter;
		return $this;
	}
	
	
	
	/**
	 * Set filter
	 */
	function filter($filter)
	{
		$this->_filter = $filter;
		return $this;
	}
	
	
	
	/**
	 * Add filter
	 */
	function addFilter($key, $op, $value)
	{
		$this->_filter[] = [$key, $op, $value];
		return $this;
	}
	
	
	
	/**
	 * Add where
	 */
	function addWhere($key, $op, $value)
	{
		$this->_filter[] = [$key, $op, $value];
		return $this;
	}
	
	
	
	/**
	 * Inner join
	 */
	function innerJoin($table_name, $alias_name, $where)
	{
		$join =
		[
			"kind" => "inner",
			"table_name" => $table_name,
			"alias_name" => $alias_name,
			"where" => $where,
		];
		
		if ($this->_join == null) $this->_join = [];
		
		$this->_join[] = $join;
		
		return $this;
	}
	
	
	
	/**
	 * Left join
	 */
	function leftJoin($table_name, $alias_name, $where)
	{
		$join =
		[
			"kind" => "left",
			"table_name" => $table_name,
			"alias_name" => $alias_name,
			"where" => $where,
		];
		
		if ($this->_join == null) $this->_join = [];
		
		$this->_join[] = $join;
		
		return $this;
	}
	
	
	
	/**
	 * Connect
	 */
	function connect($connection_name = "default")
	{
		$this->_connection = app("db_connection_list")->get($connection_name);
		return $this;
	}
	
	
	
	/**
	 * Set connection
	 */
	function setConnection($conn)
	{
		$this->_connection = $conn;
		return $this;
	}
	
	
	
	/**
	 * Execute query
	 */
	function query()
	{
		$conn = null;
		
		if ($this->_connection) $conn = $this->_connection;
		else $conn = app("db");
		
		if (!$conn) return null;
		
		return $conn->executeQuery($this);
	}
	
	
	
	/**
	 * Execute query
	 */
	function execute()
	{
		$cursor = $this->query();
		if ($cursor)
		{
			$cursor->close();
		}
	}
	
	
	
	/**
	 * Returns sql
	 */
	function getSQL()
	{
		$conn = null;
		
		if ($this->_connection) $conn = $this->_connection;
		else $conn = app("db");
		
		if (!$conn) return null;
		
		$res = $conn->buildSQL($q);
		if (!$res) return null;
		
		list($sql, $params) = $res;
		return $conn->getSQL($sql, $params);
	}
	
	
	
	/**
	 * Fetch one
	 */
	function one($is_raw = false)
	{
		$cursor = $this->query();
		if (!$cursor) return null;
		$item = $cursor->fetch($is_raw);
		$cursor->close();
		return $item;
	}
	
	
	
	/**
	 * Fetch all
	 */
	function all($is_raw = false)
	{
		$cursor = $this->query();
		if (!$cursor) return [];
		$items = $cursor->fetchAll($is_raw);
		$cursor->close();
		return $items;
	}
	
	
	
	/**
	 * Returns count
	 */
	function count()
	{
		$q = clone $this;
		$row = $q
			// ->debug(true)
			->limit(-1)->start(0)
			->fields([ "count(*) as c" ])
			->one(true)
		;
		if ($row) return $row["c"];
		return 0;
	}
	
}
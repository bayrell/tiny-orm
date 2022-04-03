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


class MySQLConnection extends Connection
{
	
	public $host = "";
	public $port = "";
	public $login = "";
	public $password = "";
	public $database = "";
	public $prefix = "";
	public $connect_error = "";
	public $pdo = null;
	public $debug = false;
	public $is_transaction = false;
	
	
	
	/**
	 * Connect
	 */
	function connect()
	{
		$this->connect_error = "";
		try
		{
			$str = 'mysql:host='.$this->host;
			if ($this->port != null) $str .= ':'.$this->port;
			if ($this->database != null) $str .= ';dbname='.$this->database;
			$this->pdo = new \PDO(
				$str, $this->login, $this->password, 
				array(
					\PDO::ATTR_PERSISTENT => false
				)
			);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->pdo->exec("set names utf8");
		}
		catch (\PDOException $e)
		{
			$this->connect_error = 'Failed connected to database!';
		}
		catch (\Excepion $e)
		{
			$this->connect_error = $e->getMessage();
		}
	}
	
	
	
	/**
	 * Connect
	 */
	function isConnected()
	{
		return $this->pdo != null;
	}
	
	
	
	/**
	 * Execute sql query
	 */
	function query($sql, $arr = [])
	{
		if ($this->debug)
		{
			$sql = static::getSQL($sql, $arr);
			echo $sql . "\n";
		}
		
		$st = $this->pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
		$st->execute($arr);
		
		return $st;
	}
	
	
	
	/**
	 * Execute sql query
	 */
	function execute($sql, $arr = [])
	{
		$st = $this->query($sql, $arr);
		$st->closeCursor();
	}
	
	
	
	/**
	 * Get first item
	 */
	function get_row($sql, $arr = [])
	{
		$st = $this->query($sql, $arr);
		$res = $st->fetch(\PDO::FETCH_ASSOC);
		$st->closeCursor();
		return $res;
	}
	
	
	
	/**
	 * Insert query
	 */
	function insert($table_name, $data)
	{
		$keys = [];
		$values = [];
		foreach ($data as $key => $val)
		{
			$keys[] = "`" . $key . "`";
			$values[] = ":" . $key;
		}
		
		/* Build sql */
		$sql = "insert into " . $table_name . 
			" (" . implode(",",$keys) . ") values (" . implode(",",$values) . ")"
		;
		
		/* Run query */
		$st = $this->query($sql, $data);
		return $st;
	}
	
	
	
	/**
	 * Update query
	 */
	function update($table_name, $where, $update)
	{
		$args = [];
		
		/* Build update */
		$update_arr = [];
		foreach ($update as $key => $value)
		{
			$update_arr[] = "`" . $key . "` = :_update_" . $key;
			$args["_update_" . $key] = $value;
		}
		$update_str = implode(", ", $update_arr);
		
		/* Build where */
		$res = $this->convertFilter($where);
		$where_str = implode(" AND ", $res[0]);
		$args = array_merge($args, $res[1]);
		
		/* Build sql */
		$sql = "update $table_name set $update_str where $where_str";
		
		/* Run query */
		$st = $this->query($sql, $args);
		return $st;
	}
	
	
	
	/**
	 * Execute sql query
	 */
	function insert_or_update_atom($table_name, $insert, $update)
	{
		$ins_keys = [];
		$ins_values = [];
		$upd_data = [];
		$data = [];
		
		foreach ($insert as $key => $val)
		{
			$keys[] = "`".$key."`";
			$values[] = ":".$key;
			$data[ $key ] = $val;
		}
		foreach ($update as $key => $val)
		{
			$keys[] = "`" . $key . "`";
			$values[] = ":" . $key;
			$upd_data[] = "`".$key."` = :" . $key;
			$data[ $key ] = $val;
		}
		
		$sql = "insert into " . $table_name . 
			" (" . implode(",",$keys) . ") " .
			" values (" . implode(",",$values) . ") " .
			(
				(count($upd_data) > 0) ? 
					" ON DUPLICATE KEY UPDATE " . implode(",", $upd_data) : ""
			)
		;
		
		$st = $this->query($sql, $data);
		return $st;
	}
	
	
	
	/**
	 * Insert or update
	 **/
	function insert_or_update($table_name, $search, $insert, $update = null, $pk="id")
	{
		if ($update == null) $update = $insert;
		
		/* Build where */
		$res = $this->convertFilter($where);
		$where_str = implode(" AND ", $res[0]);
		$where_args = array_merge($params, $res[1]);
		
		/* Find item */
		$sql = "select * from $table_name where $where_str limit 1";
		$item = $this->get_row($sql, $where_args);
		$item_id = 0;
		
		/* Insert item */
		if ($item == null)
		{
			$this->insert($table_name, $insert);
			$item_id = $this->pdo->lastInsertId();
		}
		
		/* Update item */
		else
		{
			$this->update
			(
				$table_name,
				[
					[ $pk, "=", $item[$pk] ],
				],
				$update,
			);
			$item_id = $item[$pk];
		}
		
		/* Find item by id */
		$item = $this->get_row
		(
			"select * from $table_name where $pk=:id limit 1",
			[
				$pk => $item_id,
			]
		);
		
		return $item;
	}
	
	
	
	/**
	 * Delete item
	 */
	function delete($table_name, $where)
	{
		/* Build where */
		$res = $this->convertFilter($where);
		$where_str = implode(" AND ", $res[0]);
		$where_args = $res[1];
		
		/* Delete item */
		$sql = "delete from $table_name where $where_str limit 1";
		$this->execute($sql, $where_args);
	}
	
	
	
	/**
	 * Execute
	 */
	function foundRows()
	{
		$sql = "SELECT FOUND_ROWS() as c;";
		$st = $this->query($sql);
		$res = $st->fetch(\PDO::FETCH_ASSOC);
		return $res['c'];
	}
	
	
	
	/**
	 * Insert id
	 */
	function lastInsertId()
	{
		$id = $this->pdo->lastInsertId();
		return $id;
	}
	
	
	
	/**
	 * Begin transaction
	 */
	function beginTransaction()
	{
		$this->pdo->beginTransaction();
		$this->is_transaction = true;
	}
	
	
	
	/**
	 * Commit
	 */
	function commit()
	{
		if ($this->is_transaction)
		{
			$this->pdo->commit();
			$this->is_transaction = false;
		}
	}
	
	
	
	/**
	 * rollBack
	 */
	function rollBack()
	{
		if ($this->is_transaction)
		{
			$this->pdo->rollBack();
			$this->is_transaction = false;
		}
	}
	
	

	/**
	 * Returns query sql
	 */
	function getQuerySQL($q)
	{
		/* Select query */
		if ($q->_kind == Query::QUERY_SELECT)
		{
			$sql = "SELECT ";
			$params = $q->_params;
			
			/* Add found rows */
			if ($q->_calc_found_rows == true) $sql .= " SQL_CALC_FOUND_ROWS ";
			
			/* Add fields */
			if ($q->_fields != null) $sql .= implode(", ", array_map($this->escape, $q->_fields));
			else $sql .= " * ";
			
			/* New line */
			$sql .= "\n";
			
			/* Add table name */
			$sql .= " FROM " . $this->prefix . $q->_table_name;
			
			/* Add table alias */
			if ($q->_table_name_alias != "") $sql .= " AS " . $q->_table_name_alias;
			
			/* New line */
			$sql .= "\n";
			
			/* Add joins */
			if ($q->_join != null)
			{
				foreach ($q->_join as $join)
				{
					$kind = $join["kind"];
					$table_name = $join["table_name"];
					$alias_name = $join["alias_name"];
					$where = $join["where"];
					
					if ($kind == "left") $sql .= " LEFT JOIN ";
					else $sql .= " INNER JOIN ";
					
					$sql .= $this->prefix . $table_name;
					if ($alias_name != "") $sql .= " AS " . $alias_name;
					$sql .= " ON (" . $where . ")";
					
					/* New line */
					$sql .= "\n";
				}
			}
			
			/* Add where */
			if ($q->_filter != null)
			{
				$res = $this->convertFilter($q->_filter);
				$where = $res[0];
				$params = array_merge($params, $res[1]);
				
				$where_str = implode(" AND ", $where);
				if ($where_str != "") $sql .= " WHERE " . $where_str;
				
				/* New line */
				$sql .= "\n";
			}
			
			/* Add order */
			if ($q->_order != null)
			{
				$sql .= " ORDER BY " . $q->_order;
				
				/* New line */
				$sql .= "\n";
			}
			
			/* Add order */
			if ($q->_limit >= 0) $sql .= " LIMIT " . $q->_limit;
			if ($q->_limit >= 0 and $q->_start >= 0) $sql .= " OFFSET " . $q->_start;
			
			return [$sql, $params];
		}
		
		else if ($q->_kind == Query::RAW_QUERY)
		{
			return [$q->_sql, $q->_params];
		}
		
		return null;
	}
	
	
	
	/**
	 * Execute query
	 */
	function executeQuery($q)
	{
		$res = $this->getQuerySQL($q);
		if ($res != null)
		{
			$sql = $res[0];
			$params = $res[1];
			
			/* Create cursor */
			$cursor = new Cursor();
			$cursor->connection = $this;
			$cursor->query = $q;
			$cursor->sql = $sql;
			$cursor->params = $params;
			
			/* Log sql */
			if ($q->_log)
			{
				echo $cursor->getSQL() . "\n";
			}
			
			/* Execute */
			$cursor->st = $this->query($sql, $params);
			
			return $cursor;
		}
		
		return null;
	}
	
	
	
	/**
	 * Convert filter
	 */
	function convertFilter($filter, $field_index = 0)
	{
		$params = [];
		$where = [];
		
		$convertKey = function($s)
		{
			return str_replace(".", "_", $s);
		};
		
		foreach ($filter as $item)
		{
			$field_name = $item[0];
			$op = $item[1];
			$value = $item[2];
			
			/* or */
			if ($field_name == "\$or")
			{
				if (is_array($op))
				{
					$arr_where = [];
					foreach ($op as $or)
					{
						list($where_or, $items_or, $field_index) =
							$this->convertFilter($or, $field_index)
						;
						$arr_where[] = implode(" AND ", $where_or);
						$params = array_merge($params, $items_or);
					}
					$where[] = "(" . implode(" OR ", $arr_where) . ")";
				}
				
				continue;
			}
			
			/* Check operation */
			if ( !in_array($op, ["=", "!=", ">=", "<=", "<", ">"]) )
			{
				$op = "=";
			}
			
			if (is_string($value) or is_int($value) or is_bool($value))
			{
				$field_key = $convertKey("where_" . $field_name . "_" . $field_index);
				$where[] = $this->escape($field_name) . " " . $op . " :" . $field_key;
				$params[$field_key] = $value;
				$field_index++;
			}
			
			else if (is_array($value))
			{
				$keys = [];
				foreach ($value as $v)
				{
					$field_key = $convertKey("where_" . $field_name . "_" . $field_index);
					$keys[] = ":" . $field_key;
					$params[$field_key] = $v;
					$field_index++;
				}
				$where[] = $this->escape($field_name) . " in (" . implode(",", $keys) . ")";
			}
			
			else if ($value === null)
			{
				if ($op == "!=") $where[] = $this->escape($field_name) . " is not null";
				else $where[] = $this->escape($field_name) . " is null";
			}
		}
		
		return [$where, $params, $field_index];
	}
}
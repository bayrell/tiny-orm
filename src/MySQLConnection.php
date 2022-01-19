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
			$search = array_keys($arr);
			$search = array_map( function($key){ return ":" . $key; }, $search );
			
			$replace = array_values($arr);
			$replace = array_map( function($value){
				$value = $this->pdo->quote($value);
				return $value;
			}, $replace );
			
			$sql = str_replace($search, $replace, $sql);
			echo $sql . "\n";
		}
		
		$st = $this->pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
		$st->execute($arr);
		
		return $st;
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
		$where_arr = [];
		foreach ($where as $key => $value)
		{
			if ($value == null)
			{
				$where_arr[] = "`" . $key . "` is null";
			}
			else
			{
				$where_arr[] = "`" . $key . "` = :_where_" . $key;
				$args["_where_" . $key] = $where[$key];
			}
		}
		$where_str = implode(" and ", $where_arr);
		
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
	function insert_or_update($table_name, $search, $insert, $update = null)
	{
		if ($update == null) $update = $insert;
		
		$where = array_map
		(
			function ($item)
			{
				return "`" . $item . "` = :" . $item;
			},
			array_keys($search)
		);
		$where_str = implode(" and ", $where);
		
		/* Find item */
		$sql = "select * from $table_name where $where_str limit 1";
		$item = $this->get_row($sql, $search);
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
					"id" => $item["id"],
				],
				$update,
			);
			$item_id = $item["id"];
		}
		
		/* Find item by id */
		$item = $this->get_row
		(
			"select * from $table_name where id=:id limit 1",
			[
				"id" => $item_id,
			]
		);
		
		return $item;
	}
	
	
	
	/**
	 * Execute
	 */
	function found_rows()
	{
		$sql = "SELECT FOUND_ROWS() as c;";
		$st = $this->query($sql);
		$res = $st->fetch(\PDO::FETCH_ASSOC);
		return $res['c'];
	}
	
	
	
	/**
	 * Insert id
	 */
	function insert_id()
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
	
}
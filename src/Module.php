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


class Module
{
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		add_chain("init_di_defs", static::class, "init_di_defs");
		add_chain("init_app", static::class, "init_app");
	}
	
	
	/**
	 * Init defs
	 */
	static function init_di_defs($res)
	{
		$defs = $res->defs;
		
		/* Setup default db connection */
		$defs["db_connection_list"] = \DI\create(\TinyORM\ConnectionList::class);
		$defs["db_connection"] = \DI\create(\TinyORM\MySQLConnection::class);
		$defs["db_query"] = \DI\create(\TinyORM\Query::class);
		
		/* Get default connection */
		$defs["db"] =
			function ()
			{
				$db_list = app("db_connection_list");
				return $db_list->get("default");
			};
		
		/* Connect to database */
		$defs["connectToDatabase"] =
			function ()
			{
				$conn = make("db_connection");
				$conn->host = getenv("MYSQL_HOST");
				$conn->port = getenv("MYSQL_PORT");
				$conn->login = getenv("MYSQL_LOGIN");
				$conn->password = getenv("MYSQL_PASSWORD");
				$conn->database = getenv("MYSQL_DATABASE");
				if (!$conn->port) $conn->port = "3306";
				
				/* Connect */
				$conn->connect();
				
				if (!$conn->isConnected())
				{
					echo "Error: " . $conn->connect_error . "\n";
					exit(1);
				}
				
				$db_list = app("db_connection_list");
				$db_list->add("default", $conn);
				
				call_chain("connectToDatabase", ["conn"=>$conn]);
			};
		
		$res->defs = $defs;
	}
	
	
	
	/* Init application */
	static function init_app()
	{
		app("connectToDatabase");
	}
	
}
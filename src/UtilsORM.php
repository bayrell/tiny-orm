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


class UtilsORM
{
	
	/**
	 * Intersect object
	 */
	static function object_intersect($item, $keys)
	{
		$res = [];
		if (gettype($item) == 'array')
		{
			foreach ($item as $key => $val)
			{
				if (in_array($key, $keys))
				{
					$res[$key] = $val;
				}
			}
		}
		return $res;
	}
	
	
	
	/**
	 * Object contains
	 */
	static function object_contains($obj1, $obj2)
	{
		foreach ($obj2 as $key => $value)
		{
			if (!key_exists($key, $obj1)) continue;
			if ($obj1[$key] != $value) return false;
		}
		return true;
	}
	
	
	
	/**
	 * Object is equal
	 */
	static function object_is_equal($obj1, $obj2, $keys = null)
	{
		if ($keys != null)
		{
			$obj1 = static::object_intersect($obj1, $keys);
			$obj2 = static::object_intersect($obj2, $keys);
		}
		return static::object_contains($obj1, $obj2) && static::object_contains($obj2, $obj1);
	}
	
}
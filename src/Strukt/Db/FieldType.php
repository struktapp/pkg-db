<?php

namespace Strukt\Db;

class FieldType{

	public static $types = array(

		"varchar" => "varchar",
		"str" => "varchar",
		"string" => "varchar",
		"chr" => "char",
		"char" => "char",
		"long_blob" => "longBlob",
		"medium_blob" => "mediumBlob",
		"blob" => "blob",
		"long_text" => "longText",
		"medium_text" => "mediumText",
		"text" => "text",
		"tiny_text" => "tinyText",
		"yr" => "year",
		"year" => "year",
		"timestamp" => "timestamp",
		"datetime" => "datetime",
		"time" => "time",
		"date" => "date",
		"number" => "numeric",
		"numeric" => "numeric",
		"dec" => "decimal",
		"decimal" => "decimal",
		"dbl" => "double",
		"double" => "double",
		"real" => "real",
		"point" => "float",
		"float" => "float",
		"tiny_int" => "tinyInt",
		"small_int" => "smallInt",
		"medium_int" => "mediumInt",
		"big_int" => "bigInt",
		"int" => "int" ,
		"integer" => "integer",
		"enum"=>"enum"
	);

	public static $codes = array(

		"bool"  => 0,
		"int"  => 2,
		"integer"  => 2,
		"double"  => 3,
		"string" => 4,
		"text8"  => 5,
		"text16"  => 6,
		"text32"  => 7,
		"text" => 6,
		"date" => 80,
		"datetime" => 81,
		"time" => 83,
		"timestamp" => 83,
		"point" => 90,
		"linestring" => 91,
		"polygon" => 92,
		"money" => 93,
		"json" => 94,
		"" => 99
	);
}
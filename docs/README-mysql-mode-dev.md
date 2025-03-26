MySQL Mode
===

MySQL sql_mode `"TRADITIONAL"`, a.k.a. "strict mode", is defined by the [MySQL docs](https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html) as:

“give an error instead of a warning” when inserting an incorrect value into a column.

Here's how to ensure that your sql_mode is set to `"TRADITIONAL"`.

First, check your current setting:

```sh
mysql
mysql> SELECT @@GLOBAL.sql_mode;
+-------------------+
| @@GLOBAL.sql_mode |
+-------------------+
|                   |
+-------------------+
1 row in set (0.00 sec)
```

This returned blank, the default, that's bad: your sql_mode is not set to `"TRADITIONAL"`.

So edit the configuration file:

```sh
sudo vim /etc/mysql/my.cnf
```

Add this line in the section labelled [mysqld]: sql_mode="TRADITIONAL" (as fancyPants pointed out)

Then restart the server:

```sh
sudo service mysql restart
```

Then check again:

```sh
mysql
mysql> SELECT @@GLOBAL.sql_mode;
+------------------------------------------------------------------------------------------------------------------------------------------------------+
| @@GLOBAL.sql_mode                                                                                                                                    |
+------------------------------------------------------------------------------------------------------------------------------------------------------+
| STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,TRADITIONAL,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |
+------------------------------------------------------------------------------------------------------------------------------------------------------+
1 row in set (0.00 sec)
```
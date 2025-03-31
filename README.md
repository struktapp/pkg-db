Strukt Db
===

### Transaction

```php

require "bootstrap.php";

useDb("pop");

pdo()->transact(function(){

	$role = db("role");
	$role->name = "superadmin";
	$role->save();

	$user = db("user");
	$user->username = "sadmin@tenure";
	$user->password = sha1("p@55w0rd!!");
	$user->role_id = "abc"; //invalid entry expect a number
	$user->save();
});
```

### Commands

```sh
Database
 model:make      Make model
 db:make-models  Make models from db
 db:make         Make db from models
 db:seeds        Seed database tables iwth JSON set (folder)
 db:wipe         Truncate database
 db:sql          Truncate database
 ```

### SQL

```sql
├── modify(string $table)
│   ├── addSet(string $modify)
│   ├── set(string $modify)
│   │   ├── andWhere(string $condition)
│   │   ├── orWhere(string $condition)
│   │   ├── where(string $condition)
│   │   └── yield():string
│   └── yield():string
└── select(string $fields)
    ├── addSelect(string $fields)
    ├── from(string $tables)
    │   └── leftjoin(string $join)
    ├── groupBy(string $columns)
    ├── limit(int $limit)
    ├── orderBy(string $columns, string $order = "DESC")
    ├── page(int $page, int $perPage=10)
    ├── union(string $sql)
    ├── unionAll(string $sql)
    └── where(string $condition)
        ├── andWhere(string $condition)
        └── orWhere(string $condition)
```
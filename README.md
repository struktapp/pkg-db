Strukt Db
===

## Finders

```php
$user = db("user", $id); // find user by id
```

#### Pop Db

```php
$user = db("user")->findOne(["username"=>$username]); // find user by username
```

#### Red Db

```php
$user = db()->findOne("user", "username = ?", [$username]); // find user by username
$user = db()->findOne("user", "username = :username", ["username"=>$username]);   
$user = db()->findLast("user");// find last user
$sql = "SELECT * FROM user u WHERE u.email LIKE :email";
$rs = db()->getAll($sql, filter(["email"], "gmail.com", false)); // find all gmail users
$rs = db()->findAll("user"); // find all users
$rs = sync($rs); // sync bean to model
```

## Commit

```php
$user_id = commit("user", ["usename"=>"pitsolu","password"=>hashfn()("p@55w0rd")]);// add
$success = commit("user", ["password"=>hashfn()("*p@55w0rd$")], $user_id); // update
```

## Transaction

```php
pdo()->transact(function(){

	$role = db("role");
	$role->name = "superadmin";
	$role->save();

	$user = db("user");
	$user->username = "sadmin@tenure.com";
	$user->password = hash("sha256")("p@55w0rd!!");
	$user->role_id = "abc"; //invalid entry expect a number
	$user->save();
});
```

## Commands

```sh
Database
 model:make      Make model
 db:make-models  Make models from db
 db:make         Make db from models
 db:seeds        Seed database tables iwth JSON set (folder)
 db:wipe         Truncate database
 db:sql          Truncate database
```

## Resultset

```php
$sql = select("u.id, u.username, r.name as role_name, r.created_at")
        ->from("user u")
        ->leftjoin("role r ON r.id = u.role_id")
        ->where("r.name = :role_name")
        ->orderBy("p.name", order:"ASC");

$rs = resultset($sql, ["role_name"=>"admin"])
        ->normalize("created_at:humanize")
        ->yield(); // list of admins
```

## SQL

```php
├── modify(string $table)
│   ├── addSet(string $modify)
│   ├── set(string $modify)
│   │   ├── andWhere(string $condition)
│   │   ├── orWhere(string $condition)
│   │   ├── where(string $condition)
│   │   └── yield():string
│   └── yield():string
└── select(string $fields)
    ├── addSelect(string $fields)
    ├── from(string $tables)
    │   └── leftjoin(string $join)
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
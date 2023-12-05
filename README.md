Strukt PkgDb
===

### transaction

```php

require "bootstrap.php";

useDb("pop");

pdo()->transact(function(){

	$role = db("role",);
	$role->name = "superadmin";
	$role->save();

	$user = db("user");
	$user->username = "sadmin@tenure";
	$user->password = sha1("p@55w0rd!!");
	$user->role_id = "abc"; //invalid entry expect a number
	$user->save();
});
```
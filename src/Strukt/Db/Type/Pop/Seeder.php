<?php 

namespace Strukt\Db\Type\Pop;

class Seeder{

	private $fs;
	private $files = [];
	private $hashfn = null;

	public function __construct(string $path){

		extract(pathinfo($path));

		$this->fs = fs($dirname);
		if(!$this->fs->isDir("."))
			raise("Folder does not exists!");

		foreach(glob($path) as $fpath)
			if(str($fpath)->endsWith(".json"))
				$this->files[] = str($fpath)
									->replace(\Strukt\Fs::ds(sprintf("%s/", $dirname)), "")
									->yield();

		$this->hashfn = fn($password)=>sha1($password);//default hash function
	}

	public function useHashFn(callable $fn){

		$this->hashfn = $fn;
	}

	public function up(){

		foreach($this->files as $file){

			$seed = json($this->fs->cat($file))->decode();

			$generic = [];
			if(array_key_exists("generic", $seed))
				$generic = $seed["generic"];

			foreach($seed["data"] as $idx=>$row){

				foreach($row as $field=>$value){

					if(str($field)->endsWith("_id")){

						$table = str($field)->replace("_id", "")->yield();
						$rel_field = $seed["relations"][$table];
						$set = makeModel($table)->findOne([$rel_field=>$value])->toArray();
						$row[$field] = $set["id"];
					}
				}

				if(array_key_exists("password", $row))
					$row["password"] = $this->hashfn($row["password"]);

				seed($seed["table"], array_merge($row, $generic));
			}
		}
	}

	public function down(){

		$db = db();
		foreach($this->files as $file)
			$db->query($db->createSql()->delete(str($file)->replace(".json","")->yield()));
	}
}
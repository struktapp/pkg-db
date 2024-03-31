<?php 

namespace Strukt\Db\Type\Pop;

class Seeder{

	private $fs;
	private $files = [];
	private $hashfn = null;
	private $caption = null;

	public function __construct(string $path, string $caption = null){

		$this->caption = $caption;

		extract(pathinfo($path));

		$this->fs = fs($dirname);
		if(!$this->fs->isDir("."))
			raise("Folder does not exists!");

		if(str($path)->endsWith(".json")){

			$dirname = dirname($path);
			if(fs()->isPath($path))
				$this->files[] = basename($path);
		}

		if(empty($this->files))
			if($this->fs->isFile("_order.json"))
				$this->files = json($this->fs->cat("_order.json"))->decode();

		if(empty($this->files))
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

		if(negate(is_null($this->caption)))
			echo(str("\nFacet:")->concat(colorln("blue", $this->caption))->concat("\n")->yield());

		foreach($this->files as $file){

			echo(str("table:")->concat(color("yellow", $file))->concat("\n")->yield());
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

				$hashfn = $this->hashfn;
				if(array_key_exists("password", $row))
					$row["password"] = $hashfn($row["password"]);

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
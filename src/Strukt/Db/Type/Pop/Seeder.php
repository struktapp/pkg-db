<?php 

namespace Strukt\Db\Type\Pop;

/**
 * PopDb Seeder 
 * 
 * @author Moderator <pitsolu@gmail.com>
 */
class Seeder{

	private $fs;
	private $files = [];
	private $hashfn = null;
	private $filter = null;

	/**
	 * @param string $path
	 * @param string $filter
	 */
	public function __construct(string $path, string $filter = null){

		$this->filter = $filter;

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
			foreach(fs()->ls($path) as $fpath)
				if(str($fpath)->endsWith(".json"))
					$this->files[] = str($fpath)
									->replace(\Strukt\Fs::ds(sprintf("%s/", $dirname)), "")
									->yield();

		$this->hashfn = fn($password)=>sha1($password);//default hash function
	}

	/**
	 * @param callable $fn
	 * 
	 * @return void
	 */
	public function useHashFn(callable $fn):void{

		$this->hashfn = $fn;
	}

	/**
	 * @return void
	 */
	public function up(){

		if(negate(is_null($this->filter)))
			echo(str("\nFacet:")->concat(colorln("blue", $this->filter))->concat("\n")->yield());

		foreach($this->files as $file){

			$path = ds(sprintf("%s/%s", $this->filter, $file));

			echo(str("table:")->concat(color("yellow", $file))->concat("\n")->yield());

			$seed = json($this->fs->cat($path))->decode();

			$generic = [];
			if(array_key_exists("generic", $seed))
				$generic = $seed["generic"];

			foreach($seed["data"] as $idx=>$row){

				foreach($row as $field=>$value){

					if(str($field)->equals("token")){

						$token = token($value);
						if($token->has("when"))
							if(str($token->get("when"))->equals("now"))
								$row[$field] = $token->set("when", time())->yield();
					}

					if(str($value)->startsWith("strukt[file]://")){

						$value = fs()->cat(str($value)->replace("strukt[file]://","")->yield());
						$row[$field] = $value;
					}

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

	/**
	 * @return void
	 */
	public function down():void{

		$db = db();
		foreach($this->files as $file)
			$db->query($db->createSql()->delete(str($file)->replace(".json","")->yield()));
	}
}
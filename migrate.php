<?php
echo "<pre>";
error_reporting(E_ERROR);
ini_set('display_errors', 1);

/**
* Global Config
*/
$_cfg['lang'] = "ca-ES";						//Default old/new content language
$_cfg['new_userId'] = "81";						//Author ID on the new DB
$_cfg['new_categories_asset_parent_id'] = 8;	//The parent_id of new category assets on the new DB (com_content.category.2 ID)
//Joomfish relation old/new languages Ids
$relations['language_id'][3]['id'] = 4;
$relations['language_id'][3]['tag'] = "ca-ES";
$relations['language_id'][4]['id'] = 3;
$relations['language_id'][4]['tag'] = "es-ES";
/**
* Old DB Config
*/
$_cfg['old_dbHost'] = "";
$_cfg['old_dbUser'] = "";
$_cfg['old_dbPassw'] = "";
$_cfg['old_dbPrefix'] = "";
$_cfg['old_dbName'] = "";

/**
* New DB Config
*/
$_cfg['new_dbHost'] = "";
$_cfg['new_dbUser'] = "";
$_cfg['new_dbPassw'] = "";
$_cfg['new_dbPrefix'] = "";
$_cfg['new_dbName'] = "";

/**
* DB Connections
*/
$db_old = mysql_connect($_cfg['old_dbHost'], $_cfg['old_dbUser'], $_cfg['old_dbPassw']) or die("Error: Cannot connect to Old DB ".$_cfg['old_dbHost']); 
mysql_select_db($_cfg['old_dbName'], $db_old)  or die("Error: Cannot read the Old database ".$_cfg['old_dbName']);
$db_new = mysql_connect($_cfg['new_dbHost'], $_cfg['new_dbUser'], $_cfg['new_dbPassw'], true) or die("Error: Cannot connect to db ".$_cfg['new_dbHost']); 
mysql_select_db($_cfg['new_dbName'], $db_new) or die("Error: Cannot read the New database ".$_cfg['new_dbName']);

/**
* OLD Sections Select
*/
echo "<b>Sections</b>\n";
//Seleccionamos las putas secciones
$query = "SELECT * FROM ".$_cfg['old_dbPrefix']."sections";
$res = mysql_query($query, $db_old);
if(mysql_num_rows($res)){
	$rows = array();
	while($row = mysql_fetch_assoc($res)){
		$rows[] = $row;
	}
	foreach($rows as $row){
		/**
		* NEW Asset Insert
		*/
		//Creamos el puto asset correspondiente a cada puta sección
		$asset = array();
		$asset['parent_id'] = $_cfg['new_categories_asset_parent_id'];	//Metemos el parent custom en cada puto Joomla
		$asset['name'] = md5(uniqid());									//Le damos un puto random, ya que el campo es único y el hijodeputa se queja
		$asset['level'] = 2;											//El level va a ser 2 por que me sale a mi de la polla
		$asset['title'] = $row['title'];								//La única puta cosa que nos interesa de verdad, el puto título
		$asset['rules'] = '{"core.create":{"6":1,"3":1},"core.delete":{"6":1},"core.edit":{"6":1,"4":1},"core.edit.state":{"6":1,"5":1},"core.edit.own":{"6":1,"3":1}}';	//Más mierda del puto Joomla
		//Hacemos el puto Insert del Asset
		$asset_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."assets", $asset);
		//Nos quedamos con el puto Id para luego updatear el Name con el ID correspondiente
		$relations['assetsSections'][$row['id']]['id'] = $asset_id;
		/**
		* NEW Category Insert
		*/
		$category = array();
		$category['asset_id'] = $asset_id;								//El ID del puto asset de mierda
		$category['parent_id'] = 1;										//System ROOT
		$category['lft'] = $row['ordering'];							//El ordering antiguo, que se va a pasar por los cojones el puto Joomla
		$category['level'] = 1;											//Top Level
		$category['path'] = slug($row['name']);							//El Slug para la puta URL
		$category['extension'] = 'com_content';							//La puta extensión
		$category['title'] = $row['title'];								//El título de la puta categoría
		$category['alias'] = slug($row['name']);						//El Slug para la el puto alias
		$category['description'] = $row['description'];					//La puta descripción que nadie usa
		$category['published'] = $row['published'];						//El puto estado
		$category['access'] = 1;										//Acceso a 1 por que me sale de la punta de la polla
		$category['params'] = '{"category_layout":"","image":""}';		//Mierda del puto Joomla
		$category['created_user_id'] = $_cfg['new_userId'];				//El puto autor
		$category['created_time'] = date("Y-m-d H:s:i");				//La puta fecha de creación
		$category['hits'] = $row['count'];								//Los putos hits
		$category['language'] = $_cfg['lang'];							//El puto lenguaje por defecto del nuevo Joomla
		//Creamos la puta categoría
		$category_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."categories", $category);
		//Guardamos la puta relación de IDs
		$relations['sections'][$row['id']]['id'] = $category_id;
		$relations['sections'][$row['id']]['path'] = $category['path'];
		/**
		* NEW Asset Update
		*/
		$asset = array();
		//Seteamos el ID de la categoría al objeto Asset
		$asset['name'] = 'com_content.category.'.$category_id;
		//Updateamos el puto Asset con el ID de la categoría, a si, de manera óptima y tal
		generateQuery("update", $_cfg['new_dbPrefix']."assets", $asset, "WHERE id=".$asset_id);
	}
}

/**
* OLD Categories Select
*/
echo "<b>Categories</b>\n";
//Seleccionamos las putas categorías
$query = "SELECT * FROM ".$_cfg['old_dbPrefix']."categories";
$res = mysql_query($query, $db_old);
if(mysql_num_rows($res)){
	$rows = array();
	while($row = mysql_fetch_assoc($res)){
		$rows[] = $row;
	}
	foreach($rows as $row){
		if($relations['sections'][$row['section']]){
			/**
			* NEW Asset Insert
			*/
			//Creamos el puto asset correspondiente a cada puta categoría
			$asset = array();
			$asset['parent_id'] = $relations['assetsSections'][$row['section']]['id'];	//Buscamos su puto padre con la relación (sección ya metida anteriormente)
			$asset['name'] = md5(uniqid());												//Le damos un puto random, ya que el campo es único y el hijodeputa se queja
			$asset['level'] = 3;														//El level va a ser 3 por que me sale a mi de la polla
			$asset['title'] = $row['title'];											//La única puta cosa que nos interesa de verdad, el puto título
			$asset['rules'] = '{"core.create":{"6":1,"3":1},"core.delete":{"6":1},"core.edit":{"6":1,"4":1},"core.edit.state":{"6":1,"5":1},"core.edit.own":{"6":1,"3":1}}';	//Más mierda del puto Joomla
			//Hacemos el puto Insert del Asset
			$asset_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."assets", $asset);
			//Nos quedamos con el puto Id para luego updatear el Name con el ID correspondiente
			$relations['assetsCategories'][$row['id']]['id'] = $asset_id;
			/**
			* NEW Category Insert
			*/
			$category = array();
			$category['asset_id'] = $asset_id;											//El ID del puto asset de mierda
			$category['parent_id'] = $relations['sections'][$row['section']]['id'];		//System ROOT
			$category['lft'] = $row['ordering'];										//El ordering antiguo, que se va a pasar por los cojones el puto Joomla
			$category['level'] = 2;														//Top Level
			$category['path'] = $relations['sections'][$row['section']]['path']."/".slug($row['name']);		//El Slug para la puta URL
			$category['extension'] = 'com_content';										//La puta extensión
			$category['title'] = $row['title'];											//El título de la puta categoría
			$category['alias'] = slug($row['name']);									//El Slug para la el puto alias
			$category['description'] = $row['description'];								//La puta descripción que nadie usa
			$category['published'] = $row['published'];									//El puto estado
			$category['access'] = 1;													//Acceso a 1 por que me sale de la punta de la polla
			$category['params'] = '{"category_layout":"","image":""}';					//Mierda del puto Joomla
			$category['created_user_id'] = $_cfg['new_userId'];							//El puto autor
			$category['created_time'] = date("Y-m-d H:s:i");							//La puta fecha de creación
			$category['hits'] = $row['count'];											//Los putos hits
			$category['language'] = $_cfg['lang'];										//El puto lenguaje por defecto del nuevo Joomla
			//Creamos la puta categoría
			$category_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."categories", $category);
			//Guardamos la puta relación de IDs
			$relations['categories'][$row['id']]['id'] = $category_id;
			$relations['categories'][$row['id']]['path'] = $category['path'];
			/**
			* NEW Asset Update
			*/
			$asset = array();
			//Seteamos el ID de la categoría al objeto Asset
			$asset['name'] = 'com_content.category.'.$category_id;
			//Updateamos el puto Asset con el ID de la categoría, a si, de manera óptima y tal
			generateQuery($db_new, "update", $_cfg['new_dbPrefix']."assets", $asset, "WHERE id=".$asset_id);
		}
	}
}

/**
* OLD Content Select
*/
echo "<b>Content</b>\n";
//Seleccionamos los putos artículos
$query = "SELECT * FROM ".$_cfg['old_dbPrefix']."content";
$res = mysql_query($query, $db_old);
if(mysql_num_rows($res)){
	$rows = array();
	while($row = mysql_fetch_assoc($res)){
		$rows[] = $row;
	}
	foreach($rows as $row){
		/**
		* NEW Asset Insert
		*/
		//Creamos el puto asset correspondiente a cada artículo
		$asset = array();
		$asset['parent_id'] = $relations['assetsCategories'][$row['catid']]['id'];			//Buscamos su puto padre con la relación (categoría ya metida anteriormente)
		$asset['name'] = md5(uniqid());														//Le damos un puto random, ya que el campo es único y el hijodeputa se queja
		$asset['level'] = 4;																//El level va a ser 4 por que me sale a mi de la polla
		$asset['title'] = $row['title'];													//La única puta cosa que nos interesa de verdad, el puto título
		$asset['rules'] = '{"core.delete":{"6":1},"core.edit":{"6":1,"4":1},"core.edit.state":{"6":1,"5":1}}';	//Más mierda del puto Joomla
		//Hacemos el puto Insert del Asset
		$asset_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."assets", $asset);
		//Nos quedamos con el puto Id para luego updatear el Name con el ID correspondiente
		$relations['assetsContent'][$row['id']]['id'] = $asset_id;
		/**
		* NEW Category Insert
		*/
		$content = array();
		$content['asset_id'] = $asset_id;													//El ID del puto asset de mierda
		$content['title'] = $row['title'];													//El puto título
		$content['alias'] = slug($row['title']);											//El puto título con su mierda de slug
		$content['title_alias'] = $row['title'];											//Ni zorra de que coño es esta puta mierda
		$content['introtext'] = $row['introtext'];											//El puto texto introductorio
		$content['fulltext'] = $row['fulltext'];											//Toda el puto texto
		$content['state'] = $row['state'];													//El puto estado
		$content['mask'] = $row['mask'];													//Ni zorra de que coño es esta puta mierda
		$content['catid'] = $relations['categories'][$row['catid']]['id'];					//El Id de la puta categoría asociada
		$content['created'] = $row['created'];												//Fecha de creación
		$content['created_by'] = $_cfg['new_userId'];										//Quien lo ha creado (ID)
		$content['created_by_alias'] = slug($row['created_by_alias']);						//Quien lo ha creado slugeado
		$content['modified'] = $row['modified'];											//Fecha modificación
		$content['checked_out'] = 0;														//Ponemos el editor a 0 para desbloquerlo
		$content['checked_out_time'] = $row['checked_out_time'];							//Ni zorra de que coño es esta puta mierda
		$content['publish_up'] = $row['publish_up'];										//Fecha Up
		$content['publish_down'] = $row['publish_down'];									//Fecha Down
		$content['images'] = $row['images'];												//Imágenes de mierda
		$content['urls'] = $row['urls'];													//Urls de mierda que las guardo y no las voy ni a usar
		$content['attribs'] = $row['attribs'];												//Atributos que al igual son compatibles de un Joomla pa otro...
		$content['version'] = $row['version'];												//Versión que no sirve para nada
		$content['parentid'] = $row['parentid'];											//Parent id? menuda tontería
		$content['ordering'] = $row['ordering'];											//El ordering antiguo, que se va a pasar por los cojones el puto Joomla
		$content['metakey'] = $row['metakey'];												//Ni zorra de que coño es esta puta mierda
		$content['metadesc'] = $row['metadesc'];											//Ni zorra de que coño es esta puta mierda
		$content['access'] = $row['access'];												//Ni zorra de que coño es esta puta mierda
		$content['hits'] = $row['hits'];													//Los putos hits
		$content['metadata'] = '{"robots":"","author":"","rights":"","xreference":""}';		//Data de mierda del Joomla
		$content['language'] = $_cfg['lang'];												//El puto idioma por defecto en el nuevo Joomla
		//Hacemos el puto Insert del artículo
		$content_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."content", $content);
		//Guardamos la puta relación de IDs
		$relations['content'][$row['id']]['id'] = $content_id;
		$relations['content'][$row['id']]['alias'] = $category['alias'];
		/**
		* NEW Asset Update
		*/
		$asset = array();
		//Seteamos el ID de la categoría al objeto Asset
		$asset['name'] = 'com_content.article.'.$content_id;
		//Updateamos el puto Asset con el ID de la categoría, a si, de manera óptima y tal
		$res = generateQuery($db_new, "update", $_cfg['new_dbPrefix']."assets", $asset, "WHERE id=".$asset_id);
	}
}

/**
* OLD Joomfish Tableinfo Select
*/
echo "<b>Joomfish Tableinfo</b>\n";
//Migramos el Tableinfo del Joomfish
$query = "SELECT * FROM ".$_cfg['old_dbPrefix']."jf_tableinfo";
$res = mysql_query($query, $db_old);
if(mysql_num_rows($res)){
	$rows = array();
	while($row = mysql_fetch_assoc($res)){
		$rows[] = $row;
	}
	foreach($rows as $row){
		//Como esta la tabla identica en las dos versiones del puto Joomla, tal cual me viene la meto
		$res = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."jf_tableinfo", $row);
	}
}

/**
* OLD Joomfish Content Select
*/
echo "<b>Joomfish Content</b>\n";
//Migramos las traducciones del Joomfish
$query = "SELECT * FROM ".$_cfg['old_dbPrefix']."jf_content";
$res = mysql_query($query, $db_old);
if(mysql_num_rows($res)){
	while($row = mysql_fetch_assoc($res)){
		//Agrupamos porque cada traducción tiene como 3 filas
		//De esa manera quedamos un único objeto con todos los campos
		//Haz un puto print_r si no te lo crees.
		$translations[$row['reference_id']][$row['reference_field']] = $row['value'];
		$translations[$row['reference_id']]['reference_table'] = $row['reference_table'];
		$translations[$row['reference_id']]['language_id'] = $row['language_id'];
	}
	//El puto reference Id lo guardamos
	foreach($translations as $reference_id=>$translation){
		//Guardamos el laguage tac de la traducción actual
		$languageTag = $relations['language_id'][$translation['language_id']]['tag'];
		$languageId = $relations['language_id'][$translation['language_id']]['id'];
		//Cojemos el puto Reference Id de los datos nuevos
		$reference_id = $relations[$translation['reference_table']][$reference_id]['id'];
		//Sólo vamos a migrar traducciones de Contenido y Categorías, lo de mas sudo de hacerlo
		if($translation['reference_table']=="content" || $translation['reference_table']=="categories"){
			//Contenido
			if($translation['reference_table']=="content"){
				/**
				* NEW Content Select
				*/
				//Seleccionamos el contenido ya migrado, ya que Ids de las categorías padres ya las tendremos etc (si, se usa la no traducida, cosas del puto Joomla)
				$query = "SELECT * FROM ".$_cfg['new_dbPrefix']."content WHERE id=".$reference_id;
				$res = mysql_query($query, $db_new);
				if(mysql_num_rows($res)){
					$content = mysql_fetch_assoc($res);
					//Cojo el puto asset id para poder clonarlo para la traduccion
					$non_translated_asset_id = $content['asset_id'];
					/**
					* NEW Asset Insert
					*/
					//Ahora que ya tengo el puto Asset Id, lo selecciono para clonarlo
					$query = "SELECT * FROM ".$_cfg['new_dbPrefix']."assets WHERE id=".$non_translated_asset_id;
					$res = mysql_query($query, $db_new);
					if(mysql_num_rows($res)){
						$asset = mysql_fetch_assoc($res);
						//Le cambiamos las cosas necesarias
						unset($asset['id']);
						$asset['name'] = md5(uniqid());
						$asset['title'] = $translation['title'];
						//Hacemos el puto insert
						$asset_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."assets", $asset);
						/**
						* NEW Content Insert (Already saved)
						*/
						//Clonamos el puto contenido
						unset($content['id']);
						$content['asset_id'] = $asset_id;
						$content['title'] = $translation['title'];
						$content['state'] = 1;
						$content['alias'] = $translation['alias'];
						$content['title_alias'] = $translation['title_alias'];
						$content['introtext'] = $translation['introtext'];
						$content['fulltext'] = $translation['fulltext'];
						$content['language'] = $languageTag;
						$content_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."content", $content);
						/**
						* NEW Asset Update
						*/
						$query = "UPDATE ".$_cfg['new_dbPrefix']."assets SET name = 'com_content.article".$content_id."' WHERE id=".$asset_id;
						$res = mysql_query($query, $db_new);
						/**
						* NEW Joomfish Translationmap Insert
						*/
						//Clonamos el puto contenido
						$translationmap = array();
						$translationmap['language'] = $languageTag;
						$translationmap['reference_id'] = $reference_id;
						$translationmap['translation_id'] = $content_id;
						$translationmap['reference_table'] = $translation['reference_table'];
						generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."jf_translationmap", $translationmap);
					}
				}
			//Categories
			}elseif($translation['reference_table']=="categories"){
				/**
				* NEW Categoy Select
				*/
				//Seleccionamos la categoría ya migrada, ya que Ids de las categorías padres ya las tendremos etc (si, se usa la no traducida, cosas del puto Joomla)
				$query = "SELECT * FROM ".$_cfg['new_dbPrefix']."categories WHERE id=".$reference_id;
				$res = mysql_query($query, $db_new);
				if(mysql_num_rows($res)){
					$category = mysql_fetch_assoc($res);
					//Cojo el puto asset id para poder clonarlo para la traduccion
					$non_translated_asset_id = $category['asset_id'];
					/**
					* NEW Asset Insert
					*/
					//Ahora que ya tengo el puto Asset Id, lo selecciono para clonarlo
					$query = "SELECT * FROM ".$_cfg['new_dbPrefix']."assets WHERE id=".$non_translated_asset_id;
					$res = mysql_query($query, $db_new);
					if(mysql_num_rows($res)){
						$asset = mysql_fetch_assoc($res);
						//Le cambiamos las cosas necesarias
						unset($asset['id']);
						$asset['name'] = md5(uniqid());
						$asset['title'] = $translation['title'];
						//Hacemos el puto insert
						$asset_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."assets", $asset);
						/**
						* NEW Category Insert (Already saved)
						*/
						//Clonamos la puta categoría
						unset($category['id']);
						$category['asset_id'] = $asset_id;
						$category['title'] = $translation['title'];
						$category['alias'] = $translation['alias'];
						$category['description'] = $translation['description'];
						$category['language'] = $languageTag;
						$category_id = generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."categories", $category);
						/**
						* NEW Asset Update
						*/
						$query = "UPDATE ".$_cfg['new_dbPrefix']."assets SET name = 'com_content.category".$category_id."' WHERE id=".$asset_id;
						$res = mysql_query($query, $db_new);
						/**
						* NEW Joomfish Translationmap Insert
						*/
						//Clonamos el puto contenido
						$translationmap = array();
						$translationmap['language'] = $languageTag;
						$translationmap['reference_id'] = $reference_id;
						$translationmap['translation_id'] = $category_id;
						$translationmap['reference_table'] = $translation['reference_table'];
						generateQuery($db_new, "insert", $_cfg['new_dbPrefix']."jf_translationmap", $translationmap);
					}
				}
			}
		}
	}
}

function generateQuery($dbLink, $type, $tablename, $array, $where=""){
	strtolower($type);
	foreach($array as $name=>$value) {
		if($type=="insert"){
		    $values1[$name] = "`".$name."`";
		    $values2[$name ]= " '".mysql_real_escape_string(stripslashes($value))."' ";
		}elseif($type=="update"){
			$values[$name] = "`".$name."`='".mysql_real_escape_string(stripslashes($value))."'";
		}
	}
	if($type=="insert"){
		echo "Inserting data in ".$tablename."...";
		$query = "INSERT INTO `".$tablename."` (".implode(" , ", $values1).") VALUES (".implode(" , ",$values2).")";
	}elseif($type=="update"){
		echo "Updating data in ".$tablename."...";
		$query = "UPDATE `".$tablename."` SET ".implode(" , ", $values);
		if($where){
			$query .= " ".$where;
		}
	}
	if($query){
		$res = mysql_query($query, $dbLink); 
		if($res){
			echo "OK\n";
		}else{
			echo "ERROR\n";
			echo mysql_error()."\n";
			echo $query."\n";
			exit;
		}
		return mysql_insert_id();
	}
}

function slug($string) {		
	$characters = array(
		"Á" => "A", "Ç" => "c", "É" => "e", "Í" => "i", "Ñ" => "n", "Ó" => "o", "Ú" => "u", 
		"á" => "a", "ç" => "c", "é" => "e", "í" => "i", "ñ" => "n", "ó" => "o", "ú" => "u",
		"à" => "a", "è" => "e", "ì" => "i", "ò" => "o", "ù" => "u"
	);
	$string = strtr($string, $characters); 
	$string = strtolower(trim($string));
	$string = preg_replace("/[^a-z0-9-]/", "-", $string);
	$string = preg_replace("/-+/", "-", $string);
	if(substr($string, strlen($string) - 1, strlen($string)) === "-") {
		$string = substr($string, 0, strlen($string) - 1);
	}
	return $string;
}
?>
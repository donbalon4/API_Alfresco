<?php 
/*
 *		@author: Daniel Ojeda
 * 		@Version: 1.2
 */

/*
* 		@usage:
*			$conexion = APIAlfresco::getInstance();
*			$conexion->connect($urlRepository,$User,$Pass);
*			$conexion->checkResponse();
*			$conexion->setFolderByPath($folder);
*/

require_once ("cmis_repository_wrapper.php");
require_once ("cmis_service.php");

class APIAlfresco{

	private static $instance;
	public $urlRepository;
	public $user;
	public $pass;
	public $repository;
	public $parentFolder;

	// Singleton

	function __construct()
	{
		
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			$obj = __CLASS__;
			self::$instance = new $obj;
		}
		return self::$instance;
	}


	public function __clone(){
        trigger_error('Clone not allowed.', E_USER_ERROR);
    }

	/*
		Function to connect to alfresco.
		@example
			$url = "http://127.0.0.1:8080/alfresco/cmisatom";
	*/

	public function connect($url,$user,$pass){
		$this->urlRepository = $url;
		$this->user = $user;
		$this->pass = $pass;
		$this->repository = new CMISService($url,$user,$pass);
	}

	public function checkResponse(){
		if ($this->repository->getLastRequest()->code > 299){
	        print "an error has ocurred";
	        exit (255);
    	}
	}

	/*
	Set folder 
	@e.g.:
		$folder = "/folder";
	*/

	public function setFolderByPath($folder,$options = array()){
		$obj = $this->repository->getObjectByPath($folder,$options);
		$propiedad = $obj->properties['cmis:baseTypeId'];
		if($propiedad != "cmis:folder"){
			print "The object is not a folder";
			exit (255);
		}
		else{
			$this->parentFolder = $obj;			
		}
	}

	/*
	Set folder by id
	@e.g.:
		$id = "workspace://SpacesStore/xxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxx"	
	*/

	public function setFolderById($id,$options = array()){
		$obj = $this->repository->getObject($id,$options);
		$propiedad = $obj->properties["cmis:baseTypeId"];
		if ($propiedad != "cmis:folder") {
			print "The object is not a folder";
			exit (255);
		}
		else{
			$this->parentFolder = $obj;
		}
	}

	/*
	Create a folder inside the folder previously setted
	$name = name of the folder to create.
	@e.g.:
		$name = "folder";
	*/

	public function createFolder($name,$properties = array(),$options = array()){
		$exists = $this->existsFolder($name);
		if ($exists){
			print "Error:->The ".$name." folder already exists";
			exit (255);
		}
		else{
			return $this->repository->createFolder($this->parentFolder->id,$name);	
		}
	}

	/*
	
	create file inside the folder previously setted
	($this->parentFolder)
	$name = name of the file to create.
	@e.g.:
		$name = "file.txt";
		$content = "hi this is a file";
	*/

	public function createFile($name,$properties = array(),$content = null,$content_type = "application/octet-stream",$options = array()){
		$exists = $this->FileExists($name);
		if ($exists) {
			print "Error:->the ".$name." file already exists";
			//exit (255);
		}
		else{
			return $this->repository->createDocument($this->parentFolder->id,$name,$properties,$content,$content_type,$options);
		}
	}

	/*
	upload files
	@e.g.:
		$file = "c:/temp/hello.pdf";
	*/
	
	public function uploadFile($file){
		
		$name = basename($file);
		$name = $this->extractSpecialCharacters($name);
		$openFile = fopen($file, "r");
		$content = fread($openFile, filesize($file));
		//You need to activate fileinfo
		$content_type = mime_content_type($file);
		$newFile = $this->createFile($name,array(),$content,$content_type,array());
		if ($newFile) {
			return $newFile;
		}
			
	}
	/*
	Download a file into temp directory and display the content by the browser
	*/
	
	public function displayFile($id){
		$file = $this->getObjectById($id);
		$name = $file->properties["cmis:name"];
		$mime = $file->properties["cmis:contentStreamMimeType"];
		$mime = str_replace("\t", "", $mime);
		$mime = str_replace("\n", "", $mime);
		$length = $file->properties["cmis:contentStreamLength"];
		$content = $this->repository->getContentStream($id);
		$name = str_replace(" ", "_", $name);
		$tempFile = fopen($name, "wb");
		fwrite($tempFile, $content);
		fclose($tempFile);
		$domain = $_SERVER['SERVER_NAME'];
		$path = getcwd()."/".$name;
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-type: '.$mime);        
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($name));
        if (ob_get_contents()) ob_end_clean();
        flush();
        readfile($path);
        unlink($name);
        exit();
	}

	/*
	Download a file by its id
	*/

	public function downloadFile($id){
		$file = $this->getObjectById($id);
		$name = $file->properties["cmis:name"];
		$mime = $file->properties["cmis:contentStreamMimeType"];
		$mime = str_replace("\t", "", $mime);
		$mime = str_replace("\n", "", $mime);
		$length = $file->properties["cmis:contentStreamLength"];
		$content = $this->repository->getContentStream($id);
		$name = str_replace(" ", "_", $name);
		$tempFile = fopen($name, "wb");
		fwrite($tempFile, $content);
		fclose($tempFile);
		$domain = $_SERVER['SERVER_NAME'];
		$path = getcwd()."/".$name;
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
        header('Content-type: '.$mime);
        header("Content-Disposition: attachment; filename=\"" . $name . "\"\n");
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($name));
        if (ob_get_contents()) ob_end_clean();
        flush();
        readfile($path);
        unlink($name);
        exit();
	}

	/*
		download file by its id to a temp folder
	*/
	public function moveFile($id){
		$file = $this->getObjectById($id);
		$name = $file->properties["cmis:name"];
		$length = $file->properties["cmis:contentStreamLength"];
		$content = $this->repository->getContentStream($id);
		$name = str_replace(" ", "_", $name);
		$tempFile = fopen($name, "wb");
		fwrite($tempFile, $content);
		fclose($tempFile);
		$domain = $_SERVER['SERVER_NAME'];
		$path = getcwd()."/".$name;
		return $path;
	}		
	/*
	download zip folder	*/

	public function downloadFolder($id){
		$hasFolders = $this->hasFolders($id);
		//if has no folders
		if (!$hasFolders) {
			$obj = $this->getObjectById($id);
			$folderName = $obj->properties["cmis:name"];
			$path = getcwd()."/".$folderName;
			if (!file_exists($path)) {
				$createFolder = mkdir($path,0777,true);
			}
			else{
				$createFolder = true;
			}
			if ($createFolder) {
				chmod($path, 0777);
				$children = $this->getChildrenId($id);
				$files = array();
				for ($i=0; $i < count($children->objectList); $i++) { 
						$fileName = $children->objectList[$i]->properties["cmis:name"];
						$length = $children->objectList[$i]->properties["cmis:contentStreamLength"];
						$content = $this->repository->getContentStream($children->objectList[$i]->id);
						$tempFile = fopen($path."/".$fileName, "wb");
						fwrite($tempFile, $content);
						fclose($tempFile);
						$files[$i] = $path."/".$fileName;
				}
				$zip = new ZipArchive();
				$zipName = $folderName.".zip";
				if ($zip->open($path."/".$zipName, ZipArchive::CREATE)!==TRUE) {
				    exit("could not open <$zipName>\n");
				}
				for ($i=0; $i < count($files) ; $i++) { 
					$zip->addFile($files[$i],basename($files[$i]));
				}				
				$zip->close();
				$zipPath = $path."/".$zipName;
				header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header('Content-Description: File Transfer');
		        header('Content-type: application/zip');
		        header("Content-Disposition: attachment; filename=\"" . $zipName . "\"\n");
		        header('Content-Transfer-Encoding: Binary');
		        header('Expires: 0');
		        header('Pragma: public');
		        header('Content-Length: ' . filesize($zipPath));
		        if (ob_get_contents()) ob_end_clean();
		        flush();
		        readfile($zipPath);
		        $this->deleteDir($path);
		        exit();
			}
		}
		else{
			print("could not download the folder");
			//exit(255);
		}
	}

	/*
	get the children of the folder previously setted
	*/

	public function getChildrenFolder(){
		return $this->repository->getChildren($this->parentFolder->id);
	}

	/*
	get the children of a folder by its Id
	*/

	public function getChildrenId($id){
		return $this->repository->getChildren($id);
	}

	/*
	get an object by its Id
	*/

	public function getObjectById($id){
		return $this->repository->getObject($id);
	}

	/*
	delete an object by its id
	*/

	public function delete($id,$options = array()){
		return $this->repository->deleteObject($id,$options);
	}

	public function query($consulta){
		return $this->repository->query($consulta);
	}

	/*
	*
	*	Internal Use
	*
	*	check if the folder already exists before to create it
	*/

	private function existsFolder($name){
		$obj = $this->repository->getChildren($this->parentFolder->id);
		$name = str_replace("(", "", $name);
		$name = str_replace(")", "", $name);
		$continue = true;
		$c=0;
		while ($continue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:folder") {
				if ($obj->objectList[$c]->properties["cmis:name"] == $name) {
					$continue = false;
				}
			}
			$c=$c+1;
		}
		if(!$continue){
			return true;	
		} 
		else{
			return false;
		}
	}

	private function FileExists($name){
		$obj = $this->repository->getChildren($this->parentFolder->id);
		$name = str_replace("(", "", $name);
		$name = str_replace(")", "", $name);
		$continue = true;
		$c=0;
		while ($continue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:document") {
				if ($obj->objectList[$c]->properties["cmis:name"] == $name) {
					$continue = false;
				}
			}
			$c=$c+1;
		}
		if(!$continue){
			return true;	
		} 
		else{
			return false;
		}
	}

	private function hasFolders($id){
		$obj = $this->repository->getChildren($id);
		$continue = true;
		$c=0;
		while ($continue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:folder") {
				$continue = false;
			}
			$c = $c+1;
		}
		if (!$continue) {
			return true;
		}
		else{
			return false;
		}
	}

	private function deleteDir($dir){
	    $current_dir = opendir($dir);
	    while($entryname = readdir($current_dir)){
	        if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
	            deldir("${dir}/${entryname}");  
	        }
	        elseif($entryname != "." and $entryname!=".."){
	            unlink("${dir}/${entryname}");
	        }
	    }
	    closedir($current_dir);
	    rmdir(${'dir'});
	}

	private function extractSpecialCharacters($word){
		$word = trim($word);

	    $word = str_replace(
	        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
	        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
	        $word
	    );

	    $word = str_replace(
	        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
	        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
	        $word
	    );

	    $word = str_replace(
	        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
	        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
	        $word
	    );

	    $word = str_replace(
	        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
	        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
	        $word
	    );

	    $word = str_replace(
	        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
	        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
	        $word
	    );

	    $word = str_replace(
	        array('ñ', 'Ñ', 'ç', 'Ç'),
	        array('n', 'N', 'c', 'C',),
	        $word
	    );

	    $word = str_replace(
	        array("\\", "¨", "º", "~",
	             "#", "@", "|", "!", "\"",
	             "$", "%", "&", "/",
	             "(", ")", "?", "'", "¡",
	             "¿", "[", "^", "`", "]",
	             "+", "}", "{", "¨", "´",
	             ">", "< ", ";", ",", ":"),
	        '',
	        $word
	    );
   		return $word;
	}

}
?>
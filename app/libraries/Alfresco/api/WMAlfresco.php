<?php 
/*
 *		@Fecha: 13 Agosto 2014
 *		@Ult. Actualizacion: 13 Agosto 2014
 * 		@Autor: Daniel Ojeda Sandoval
 *      @Email: danielojeda@workmate.cl
 * 		@Version: 1.0
 */

/**
* 
*/

require_once ("../../phpclient/trunk/atom/cmis/cmis_repository_wrapper.php");
require_once ("../../phpclient/trunk/atom/cmis/cmis_service.php");

class WMAlfresco{

	private static $instancia;
	public $urlRepositorio;
	public $usuario;
	public $pass;
	public $repostiorio;
	public $carpetaPadre;
	// Singleton
	

	function __construct()
	{
		# de momento nada
	}

	public static function getInstance() {
		if (!isset(self::$instancia)) {
			$obj = __CLASS__;
			self::$instancia = new $obj;
		}
		return self::$instancia;
	}


	 public function __clone(){
        trigger_error('Clone no se permite.', E_USER_ERROR);
    }

	/*
		funcion para conectar con alfresco
	*/

	public function conectar($url,$usuario,$pass){
		$this->urlRepositorio = $url;
		$this->usuario = $usuario;
		$this->pass = $pass;
		$this->repositorio = new CMISService($url,$usuario,$pass);
		//return $this->repositorio;
	}

	public function checkRespuesta(){
		if ($this->repositorio->getLastRequest()->code > 299){
	        print "Problema con el requerimiento";
	        exit (255);
    	}
	}

	/*
	Setea carpeta sobre la cual trabajaremos en alfresco
	*/
	public function setCarpetaPorPath($carpeta){
		$obj = $this->repositorio->getObjectByPath($carpeta);
		$propiedad = $obj->properties['cmis:baseTypeId'];
		/*echo "<br>obj: ";
		echo $propiedad;
		echo "<br>";*/
		if($propiedad != "cmis:folder"){
			print "El objeto no es una carpeta";
			exit (255);
		}
		else{
			$this->carpetaPadre = $obj;			
		}
	}

	/*
	Crea carpeta
	crea carpeta en la carpeta que ha sido SETEADA
	$nombre = nombre que tendrá la carpeta a crear.
	*/
	public function crearCarpeta($nombre,$propiedades = array(),$opciones = array()){

		//$obj = $this->repositorio->getObjectByPath("/".$this->carpetaPadre->properties["cmis:name"]."/".$nombre);
		$existe = $this->existeCarpeta($nombre);
		/*]echo "<pre>existe:";
		print_r($existe);
		echo "</pre>";*/
		if ($existe){
			print "Error:->La carpeta ".$nombre." ya existe";
			exit (255);
		}
		else{
			return $this->repositorio->createFolder($this->carpetaPadre->id,$nombre);	
		}
	}

	/*
	Crear archivo
	crea archivo en la carpeta que ha sido SETEADA
	($this->carpetaPadre)
	$nombre = nombre del archivo a crear.
	*/
	public function crearArchivo($nombre,$propiedades = array(),$contenido = null,$tipo_contenido = "application/octet-stream",$opciones = array()){

		$obj = $this->repositorio->getChildren($this->carpetaPadre->id);
		echo "<br>obj: ";
		var_dump($obj);
		echo "<br>";
		if (!is_null($obj->id)) {
			print "Error:->El archivo ".$nombre." ya existe";
			exit (255);
		}
		else{
			//return $this->repositorio->createDocument($this->carpetaPadre->id,$nombre,$propiedades,$contenido,$tipo_contenido,$opciones);
		}
	}

	/*
		Verifica si la carpeta existe en la carpeta padre antes de crearla
	*/
	private function existeCarpeta($nombre){
		$obj = $this->repositorio->getChildren($this->carpetaPadre->id);
		$sigue = true;
		$c=0;
		
		while ($sigue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:folder" and $sigue == true) {
				if ($obj->objectList[$c]->properties["cmis:name"] = $nombre) {
					$sigue = false;
				}
			}
			$c=$c+1;
		}
		if(!$sigue){
			return true;	
		} 
		else{
			return false;
		}
	}
}
 ?>
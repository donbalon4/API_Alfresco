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
	public $repositorio;
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
		Función para conectar con alfresco.
		@ejemplo
			$url = "http://127.0.0.1:8080/alfresco/cmisatom";
	*/

	public function conectar($url,$usuario,$pass){
		$this->urlRepositorio = $url;
		$this->usuario = $usuario;
		$this->pass = $pass;
		$this->repositorio = new CMISService($url,$usuario,$pass);
	}

	public function checkRespuesta(){
		if ($this->repositorio->getLastRequest()->code > 299){
	        print "Problema con el requerimiento";
	        exit (255);
    	}
	}

	/*
	Setea carpeta sobre la cual trabajaremos en alfresco
	@Ej:
		$carpeta = "/carpeta";
	*/

	public function setCarpetaPorRuta($carpeta,$opciones = array()){
		$obj = $this->repositorio->getObjectByPath($carpeta,$opciones);
		$propiedad = $obj->properties['cmis:baseTypeId'];
		/*
		echo "<pre>obj: ";
		print_r($obj);
		echo "</pre>";
		*/
		if($propiedad != "cmis:folder"){
			print "El objeto no es una carpeta";
			exit (255);
		}
		else{
			$this->carpetaPadre = $obj;			
		}
	}

	/*
	Setea carpeta (según id) sobre la cual trabajaremos en alfresco
	@Ej:
		$id = "workspace://SpacesStore/xxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxx"	
	*/

	public function setCarpetaPorId($id,$opciones = array()){
		$obj = $this->repositorio->getObject($id,$opciones);
		$propiedad = $obj->properties["cmis:baseTypeId"];
		if ($propiedad != "cmis:folder") {
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
	@Ej:
		$nombre = "carpeta";
	*/

	public function crearCarpeta($nombre,$propiedades = array(),$opciones = array()){
		$existe = $this->existeCarpeta($nombre);
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
	@Ej:
		$nombre = "archivo.txt";
		$contenido = "hola este es un archivo";
	*/

	public function crearArchivo($nombre,$propiedades = array(),$contenido = null,$tipo_contenido = "application/octet-stream",$opciones = array()){
		$existe = $this->existeArchivo($nombre);
		if ($existe) {
			print "Error:->El archivo ".$nombre." ya existe";
			exit (255);
		}
		else{
			return $this->repositorio->createDocument($this->carpetaPadre->id,$nombre,$propiedades,$contenido,$tipo_contenido,$opciones);
		}
	}

	/*
	sube archivos	
	@Ej:
		$archivo = "c:/temp/hola.pdf";
	*/
	
	public function subirArchivo($archivo){
		//deben estar en base 64
		$nombre = basename($archivo);
		$archivoAbierto = fopen($archivo, "r");
		$contenido = fread($archivoAbierto, filesize($archivo));
		//activar extension php fileinfo
		$tipo_contenido = mime_content_type($archivo);
		$nuevoArchivo = $this->crearArchivo($nombre,array(),$contenido,$tipo_contenido,array());
		if ($nuevoArchivo) {
			return $nuevoArchivo;
		}
			
	}

	/*
	Descarga de archivos
	*/

	public function descargarArchivo($id){
		$archivo = $this->getObjetoPorId($id);
		$nombre = $archivo->properties["cmis:name"];
		$mime = $archivo->properties["cmis:contentStreamMimeType"];
		$contenido = $this->repositorio->getContentStream($id);
		$archTemporal = fopen($nombre, "c+");
		fwrite($archTemporal, $contenido);
	}

	/*
	Obtiene los hijos de la carpeta SETEADA
	*/

	public function getHijosCarpeta(){
		return $this->repositorio->getChildren($this->carpetaPadre->id);
	}

	/*
	Obtiene los hijos de una carpeta según Id
	*/

	public function getHijosId($id){
		return $this->repositorio->getChildren($id);
	}

	/*
	Obtiene objeto por su id
	*/

	public function getObjetoPorId($id){
		return $this->repositorio->getObject($id);
	}

	/*
	Borra objeto según su Id
	*/

	public function borrar($id,$opciones = array()){
		return $this->repositorio->deleteObject($id,$opciones);
	}

	/*
	*
	*	USO INTERNO
	*
		Verifica si la carpeta existe en la carpeta padre antes de crearla
	*/

	private function existeCarpeta($nombre){
		$obj = $this->repositorio->getChildren($this->carpetaPadre->id);
		$nombre = str_replace("(", "", $nombre);
		$nombre = str_replace(")", "", $nombre);
		$sigue = true;
		$c=0;
		while ($sigue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:folder") {
				if ($obj->objectList[$c]->properties["cmis:name"] == $nombre) {
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

	private function existeArchivo($nombre){
		$obj = $this->repositorio->getChildren($this->carpetaPadre->id);
		$nombre = str_replace("(", "", $nombre);
		$nombre = str_replace(")", "", $nombre);
		$sigue = true;
		$c=0;
		while ($sigue and $c < count($obj->objectList)) {
			if ($obj->objectList[$c]->properties["cmis:objectTypeId"] == "cmis:document") {
				if ($obj->objectList[$c]->properties["cmis:name"] == $nombre) {
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
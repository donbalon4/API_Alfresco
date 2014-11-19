<?php
 
 /**
 * ##### CONEXION ENTRE UNA APLICACION PHP Y UN SERVIDOR ALFRESCO
 *
 * 		 @Fecha: 15 Octubre 2009
 *		 @Ult. Actualizacion: 27 Octubre 2009
 * 		 @Autor: Javier Garcia Marques
 *       @Email: javier.garcia.ext@sadiel.es
 * 		 @Version: 1.3
 *
 * HISTORIAL
 * =======================================================================================================================================
 *		V1.1 - Añadida la librería Alfresco/Service/ContentData.php
 *			 - Añadida la variable pública total_coincidencias
 *			 - Añadido metodo uploadFile($nodo, $nombre, $titulo, $descripcion, $tipo, $file)
 *			 - Añadido metodo saveChanges();
 *			 - Añadido metodo getTotal() a la lista de GETTERS
 *		V1.2 - Añadido el metodo buscarArchivo($nodo, $archivo)
 *		V1.3 - Añadido el metodo getDatos($nodo, $archivo)
 *
 * DESCRIPCION
 * =======================================================================================================================================
 *		Clase para conectar una aplicacion PHP con un servidor ALFRESCO
 *
 * REQUISITOS
 * =======================================================================================================================================
 *		Libreria PHP-API para Alfresco (Logger, Service, WebService)
 *		PHP 5.0
 *		Apache server
 *		MySQL 5.1.37
 *		Alfresco Enterprise 3.0
 *      Include path -> include_path= ".C:\php\PEAR;C:\Alfresco" 
 *
 * PATRONES
 * =======================================================================================================================================
 *		Uso del patron de diseño Singleton para garantizar la correcta y unica instanciacion de la clase
 *
 * PROPIEDADES DE CLASE
 * =======================================================================================================================================
 *		$istancia				privada y estatica		Guarda la istancia de la clase
 *		$repositoryUrl  		publica					URL para conectar con Alfresco
 *		$userName       		publica					Nombre de usuario
 *		$password				publica					Contraseña de usuario
 *		$ticket					publica					Ticket ID de conexion
 *		$session				publica					ID de inicio de sesion Alfresco
 *		$repository				publica					Referencia al repositorio de Alfresco
 *		$spacesStore			publica					Referencia al Space store de Alfresco
 *		$total_coincidencias	publica					Total de coincidencias encontradas en el metodo upload
 *
 * METODOS
 * =======================================================================================================================================
 *		getIstance()														Metodo para obtener la instancia de la clase 
 *																			para evitar la duplicacion de objetos (Singleton)
 *													
 *		__construct()														Constructor. La unica manera de instanciar es con getIstance()
 *
 *		__clone()															Metodo para evitar que se puedan clonar istancias.
 *
 *		connectRepository($url, $user, $pass)								Metodo para conectar, autentificar y referenciar una sesion 
 *																			alfresco y el space store
 *										 									Parametros:
 *										 										$url  -> Direccion URL donde tengo alojado Alfresco
 *										 										$user -> Nombre de usuario de inicio de sesion
 *										 										$pass -> Contraseña de usuario de inicio de sesion
 *
 *		uploadFile($nodo, $nombre, $titulo, $descripcion, $tipo, $file)		Metodo para subir archivos locales al repositorio
 *																			Parametros:
 *																				$nodo -> Nodo de destino
 *																				$nombre -> Nombre del archivo
 *																				$titulo -> Titulo del archivo
 *																				$descripcion -> Descripcion del archivo
 *																				$tipo -> Tipo de archivo
 *																				$file -> Archivo
 *
 *		buscarArchivo($nodo, $archivo)										Metodo para buscar archivos en el repositorio
 *																			Parametros:
 *																				$nodo -> Nodo de destino
 *																				$archivo -> Nombre del archivo
 *
 *		saveChanges()														Metodo para guardar los cambios realizados en el repositorio
 *
 *		Getters:
 *			getRepositoryUrl()		getPassword()		getSession()		getSpacesStore()	getDatos($nodo, $archivo)
 *			getUserName()			getTicket()			getRepository()		*getInstace()*
 *
 * =======================================================================================================================================
 * #### USO
 * =======================================================================================================================================
 *		require_once "Alfresco/Service/Conexion.php";
 *		$conexion = Conexion::getIstance();
 *		$conexion->connectRepository("http://localhost:8080/alfresco/api", "admin", "admin");
 **/
 
// Alfresco PHP - API
require_once "Repository.php";
require_once "Session.php";
require_once "SpacesStore.php";
require_once "ContentData.php";
 
Class Conexion {
 
	private static $instancia;
	public $repositoryUrl;
	public $userName;
	public $password;
	public $ticket;
	public $session;
	public $repository;
	public $spacesStore;
	public $total_coincidencias;
 
	// Singleton
	public static function getIstance() {
		if (!isset(self::$instancia)) {
			$obj = __CLASS__;
			self::$instancia = new $obj;
		}
		return self::$instancia;
	}
 
	private function __construct() {}
 
	private function __clone() {
		throw new Exception ("Este objeto no se puede clonar");
	}
 
	// Conexion, autenticacion e inicio de sesion
	public function connectRepository($url, $user, $pass) {
		$this->repositoryUrl = $url;
		$this->userName = $user;
		$this->password = $pass;
		$this->repository = new Repository($this->repositoryUrl);
		$this->ticket = $this->repository->authenticate($this->userName, $this->password);
		$this->session = $this->repository->createSession($this->ticket);	
		$this->spacesStore = new SpacesStore($this->session);		
	}
 
	// Upload archivos, recibe los datos de un form
	public function uploadFile($nodo, $nombre, $titulo, $descripcion, $tipo, $file) {
		$this->total_coincidencias = 0;
		// Compruebo si el archivo ya existe
		foreach ($nodo->children as $c) {
			if ($c->child->cm_name == $nombre && $c->child->cm_title == $titulo && $c->child->cm_description == $descripcion) {
				$this->total_coincidencias++;
			}
		}
		// Si no existe...
		if ($this->total_coincidencias == 0) {
			$contentNode = $nodo->createChild("cm_content", "cm_contains", "cm_".$nombre);
			$contentNode->addAspect("cm_titled");
			$contentNode->cm_name = $nombre;
			$contentNode->cm_title = $titulo;
			$contentNode->cm_description = $descripcion;
			$contentData = $contentNode->updateContent("cm_content", $tipo, "UTF-8");
			$contentData->writeContentFromFile($file);
			$this->saveChanges();
			return true;
		}else{
			return false;
		}
	}
 
	// Busqueda de documentos
	public function buscarArchivo($nodo, $nombre) {
		$this->total_coincidencias = 0;
		foreach ($nodo->children as $c) {
			if ($c->child->cm_name == $nombre) {
				$this->total_coincidencias++;
			}
		}
		return $this->total_coincidencias;
	}
 
	// Guardar cambios en un nodo
	private function saveChanges() {
		$this->session->save();
	}
 
	// GETTERS
	public function getDatos($nodo, $archivo) {
		$encontrado = "";
		foreach ($nodo->children as $c) {
			if ($c->child->cm_name == $archivo) {
				$encontrado = $c->child->cm_created;
			}
		}
		if ($encontrado != "") {
			// Fecha
			$fecha = substr($encontrado, 0, 10);
			$fecha = explode ("-", $fecha);
			$fecha = $fecha[2]."/".$fecha[1]."/".$fecha[0];
			// Hora
			$hora = substr($encontrado, 11, 5);
			return "Certificado obtenido el ".$fecha." a las ".$hora;		
		}else{
			return "No se ha encontrado el certificado de notas.";
		}
	}	
 
	public function getRepositoryUrl() {
		return $this->repositoryUrl;
	}
 
	public function getUserName() {
		return $this->userName;
	}
 
	public function getPassword() {
		return $this->password;
	}
 
	public function getTicket() {
		return $this->ticket;
	}
 
	public function getSession() {
		return $this->session;
	}
 
	public function getRepository() {
		return $this->repository;
	}
 
	public function getSpacesStore() {
		return $this->spacesStore;
	}
 
	public function getTotal() {
		return $this->total_coincidencias;
	}
}
?>
 
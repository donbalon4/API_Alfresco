<?php
//require_once '../../libraries/Alfresco/phpclient/trunk/atom/cmis/cmis_service.php';
class HomeController extends BaseController {

	
	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/
	
	public function showWelcome()
	{	
		$urlRepositorio = "http://192.168.1.139:8080/alfresco/cmisatom";
		$Usuario = "admin";
		$Pass = "workmate2014";
		$carpeta = "/Compartido";
		$nuevaCarpeta = "API";
		$archivo = "api.txt";
		$contenido = "probando la nueva api";
		//$conexion = new CMISService($urlRepositorio,$Usuario,$Pass);
		//$objCarpeta = $conexion->getObjectByPath($carpeta);
		$conexion = WMAlfresco::getInstance();
		$conexion->conectar($urlRepositorio,$Usuario,$Pass);
		$conexion->checkRespuesta();

		$conexion->setCarpetaPorPath($carpeta);
		$conexion->checkRespuesta();

		$conexion->crearCarpeta($nuevaCarpeta);
		$conexion->checkRespuesta();

		$objs = $conexion->crearArchivo($archivo,array(),$contenido);
		$conexion->checkRespuesta();

		$checkeado = false;
		$error = null;
		/*
		if ($conexion->getLastRequest()->code <= 299) {
			$checkeado = true;
		}
		if ($checkeado) {
			if ($objCarpeta->properties['cmis:baseTypeId'] != "cmis:folder"){
			    $error = "No es una carpeta!<br>";
			}
			else{
				$nueva_carpeta = $conexion->createFolder($objCarpeta->id, $nuevaCarpeta);
				$obj_doc = $conexion->createDocument($nueva_carpeta->id, "archivodetexto.txt", array (), "soy un nuevo documento", "text/plain");
				$objs = $conexion->getChildren($nueva_carpeta->id);			
			}
		}
		else{
			$error="Error en la petición";
		}*/
		//check_response($objCarpeta);
		//$ticket = $repositorio->authenticate($Usuario,$Pass);
		//$ticket = $conexion->connectRepository($urlRepositorio,$Usuario,$Pass);
		//$ticket = curl_init("http://192.168.1.139:8080/alfresco/api/AuthenticationService?wsdl");
		$ticket = ":B";
		return View::make('hola', array(
			'url' => $urlRepositorio,
			'usuario' => $Usuario,
			'pass' => $Pass,
			'obj' => $objs,
			'ticket' => $ticket,
			'error' => $error
			));
	}

}

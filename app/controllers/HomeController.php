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
		$idCarpeta = "workspace://SpacesStore/99047dab-4c88-4e70-a548-b3d2139ffc65";
		$nuevaCarpeta = "API";
		$archivo = "api.txt";
		$idArchivo = "workspace://SpacesStore/aac5b6e8-d2ea-4471-8f7c-067c11b11fe7;1.0";
		$idCarpetaNueva = "workspace://SpacesStore/31f76136-867d-493e-80f6-c95cb8ef2e79";
		$contenido = "probando la nueva api";

		//$conexion = new CMISService($urlRepositorio,$Usuario,$Pass);
		//$objCarpeta = $conexion->getObjectByPath($carpeta);
		$conexion = WMAlfresco::getInstance();
		$conexion->conectar($urlRepositorio,$Usuario,$Pass);
		$conexion->checkRespuesta();

		$conexion->setCarpetaPorRuta($carpeta);
		$conexion->checkRespuesta();

		//$conexion->crearCarpeta($nuevaCarpeta);
		//$conexion->checkRespuesta();

		//$conexion->crearArchivo($archivo,array(),$contenido);
		//$conexion->checkRespuesta();

		$conexion->setCarpetaPorId($idCarpeta);
		$conexion->checkRespuesta();

		$objs = $conexion->moverObjeto($idArchivo,$idCarpetaNueva,$idCarpeta);
		//$objs = $conexion->getObjetoPorId($idArchivo);

		//$objs = $conexion->carpetaPadre;

		$checkeado = false;
		$error = null;

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

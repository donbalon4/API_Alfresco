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
		$urlRepositorio = "http://desarrollo.workmate.cl:8080/alfresco/cmisatom";
		//$Usuario = "admin";
		$Usuario = "Acreditacion_linea";
		//$Pass = "workmate2014";
		$Pass = "acreditacion2014";
		$carpeta = "/Compartido";
		$idCarpeta = "workspace://SpacesStore/99047dab-4c88-4e70-a548-b3d2139ffc65";
		$nuevaCarpeta = "API";
		//$archivo = "a.txt";
		$idArchivo = "workspace://SpacesStore/aac5b6e8-d2ea-4471-8f7c-067c11b11fe7;1.0";
		$idCarpetaNueva = "workspace://SpacesStore/31f76136-867d-493e-80f6-c95cb8ef2e79";
		$contenido = "probando la nueva api";

		$archivo = "C:\Users\danielojeda\Documents\Daniel Ojeda\Control Laboral\Planificacion Global V2.1(2007).pdf";
		/*
		$nombreArchivo = basename($archivo);
		$archivoA = fopen("C:\Users\danielojeda\Documents\Daniel Ojeda\Control Laboral\Planificacion Global V2.1(2007).pdf", "r");
		$streamArchivo = fread($archivoA, filesize($archivo));
		//activar extension php fileinfo
		$tipoArchivo = mime_content_type($archivo);*/
		//$conexion = new CMISService($urlRepositorio,$Usuario,$Pass);
		//$objCarpeta = $conexion->getObjectByPath($carpeta);
		$conexion = WMAlfresco::getInstance();
		$conexion->conectar($urlRepositorio,$Usuario,$Pass);
		$conexion->checkRespuesta();

		$conexion->setCarpetaPorRuta($carpeta);
		$conexion->checkRespuesta();

		//$conexion->crearCarpeta($nuevaCarpeta);
		//$conexion->checkRespuesta();

		/*$conexion->crearArchivo($archivo,array(),$contenido);
		$conexion->checkRespuesta();
		*/
		$conexion->setCarpetaPorId($idCarpeta);
		$conexion->checkRespuesta();
		
		$objs = getcwd();
		/*/$objs = $conexion->query("SELECT * FROM cmis:folder where cmis:name = '17359243'");
		$objs = $objs->numItems;
		if ($objs > 0) {
			$objs = "Existe la carpeta";
		}
		else{
			$objs = "No existe la carpeta";
		}*/
		//$objs = $conexion->verArchivo("workspace://SpacesStore/998a37ce-d9c9-45e7-bace-ca5354ca2379;1.0");
		//$objs = $conexion->descargarCarpeta($idCarpeta);

		//$objs = $conexion->getHijosId($idCarpeta);
		//$objs = $conexion->descargarArchivo("workspace://SpacesStore/998a37ce-d9c9-45e7-bace-ca5354ca2379;1.0");
		//$objs = $conexion->subirArchivo($archivo);
		//$objs = $archivo;
		//$objs = fread($archivoA, filesize($archivo));
		//$objs = $conexion->moverObjeto($idArchivo,$idCarpetaNueva,$idCarpeta);
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

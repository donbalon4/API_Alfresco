# API_Alfresco

## Requirements

1. PHP >= 5.4 
2. Alfresco Community Edition V 5.0

## USAGE

clone this repository and put the files together in your project.

### Example  of Use:

    require '/path/to/APIAlfresco.php';`

    $urlRepository = 'http://xx.xxx.xx.xx:xxxx/alfresco/cmisatom';
    $user = 'user';
    $pass = 'pass';
    $folder =  '/Shared';
    $folderId = 'workspace://SpacesStore/xxx-xxx-xxx-xxx-xxx';
    $fileId = 'workspace://SpacesStore/xxx-xxx-xxx-xx-xxxx;`x.X';
    $childrenId = 'workspace://SpacesStore/xxx-xxx-Xxx-xxx-xxx';

* Connect to repository:

    ```
    $conexion = APIAlfresco::getInstance();             
    try {                                               
        $conexion->connect($urlRepository,$user,$pass); 
    } catch (Exception $e) {                            
        //do something                                  
    }
    ```

* Set Workspace directory:

    `$conexion->setFolderByPath($folder);`

    or

    `$conexion->setFolderById($folderId);`

* Create Folder:

    `$conexion->createFolder('new_folder');`

* Create File:

    `$conexion->createFile('file',[],'hola soy un archivo');`

* Upload File:

    `$conexion->uploadFile('new_file.txt');`

* Display File in the browser:

    `$conexion->displayFile($fileId);`

* Download a File

    `$conexion->downloadFile($fileId);`

* Move a File to a local folder

    `$conexion->moveFile($fileId);`

* Download a folder

    `$conexion->downloadFolder($folderId);`

* Get Children of workspace folder

    `$conexion->getChildrenFolder();`

* Get Children of a folder by its id

    `$conexion->getChildrenId($childrenId);`

* Get Object by Id

    `$conexion->getObjectById($childrenId);`

* Delete an object

    `$conexion->delete(`$childrenId);`

* Perform a [query](https://wiki.alfresco.com/wiki/CMIS_Query_Language)

    `$conexion->query("SELECT * FROM cmis:document");`


##Contribute

We (I) appreciate if you have any other custom function that you want to add to this repository and share with the community. :D


<?php

/**
 * @author: Daniel Ojeda
 * @Version: 1.2
 */

/**
 * @usage:
 * $conexion = APIAlfresco::getInstance();
 * $conexion->connect($urlRepository,$User,$Pass);
 * $conexion->checkResponse();
 * $conexion->setFolderByPath($folder);
 *
 * This API uses the files listed below, distributed by Apache Chemistry
 * for more info visit: https://chemistry.apache.org/php/phpclient.html
 */
require_once 'cmis_repository_wrapper.php';
require_once 'cmis_service.php';

class APIAlfresco
{
    private static $instance;
    public $urlRepository;
    public $user;
    public $pass;
    public $repository;
    public $parentFolder;

    // Singleton

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $obj = __CLASS__;
            self::$instance = new $obj();
        }

        return self::$instance;
    }

    public function __clone()
    {
        trigger_error('Clone not allowed.', E_USER_ERROR);
    }

    /**
     * Function to connect to alfresco.
     *
     * @example $url = "http://127.0.0.1:8080/alfresco/cmisatom";
     *
     * @param string $url
     * @param string $user
     * @param string $pass
     */
    public function connect($url, $user, $pass)
    {
        $this->urlRepository = (string) $url;
        $this->user = (string) $user;
        $this->pass = (string) $pass;
        $this->repository = new CMISService($url, $user, $pass);
    }

    public function checkResponse()
    {
        if ($this->repository->getLastRequest()->code > 299) {
            echo 'an error has ocurred';
            exit(255);
        }
    }

    /**
     * Sets working folder.
     *
     * @example $folder = "/folder";
     *
     * @param string $folder
     * @param array  $options
     */
    public function setFolderByPath($folder, array $options = array())
    {
        $obj = $this->repository->getObjectByPath($folder, $options);
        $propiedad = $obj->properties['cmis:baseTypeId'];
        if ($propiedad != 'cmis:folder') {
            echo 'The object is not a folder';
            exit(255);
        } else {
            $this->parentFolder = $obj;
        }
    }

    /**
     * Sets working folder by id.
     *
     * @example $id = "workspace://SpacesStore/xxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxx";
     *
     * @param string $id
     * @param array  $options
     */
    public function setFolderById($id, array $options = array())
    {
        $obj = $this->repository->getObject($id, $options);
        $propiedad = $obj->properties['cmis:baseTypeId'];
        if ($propiedad != 'cmis:folder') {
            echo 'The object is not a folder';
            exit(255);
        } else {
            $this->parentFolder = $obj;
        }
    }

    /**
     * Creates a folder inside workspace folder.
     *
     * @example $name = "folder";
     *
     * @param string $name
     * @param array  $properties
     * @param array  $options
     *
     * @return null|stdClass
     */
    public function createFolder($name, array $properties = array(), array $options = array())
    {
        $exists = $this->existsFolder($name);
        if ($exists) {
            echo 'Error:->The '.$name.' folder already exists';
            exit(255);
        } else {
            return $this->repository->createFolder($this->parentFolder->id, $name);
        }
    }

    /**
     * Creates a file inside the folder previously setted ($this->parentFolder)
     * $name = name of the file to create.
     *
     * @example $name = "file.txt";
     * @example	$content = "hi this is a file";
     *
     * @param string $name
     * @param string $content
     * @param string $content_type
     * @param array  $option
     *
     * @return null|stdClass
     */
    public function createFile($name, array $properties = array(), $content = null, $content_type = 'application/octet-stream', array $options = array())
    {
        $exists = $this->FileExists($name);
        if ($exists) {
            echo 'Error:->the '.$name.' file already exists';
            //exit (255);
        } else {
            return $this->repository->createDocument($this->parentFolder->id, $name, $properties, $content, $content_type, $options);
        }
    }

    /**
     * Uploads a file.
     *
     * @example $file = "c:/temp/hello.pdf";
     *
     * @param string $file
     *
     * @return stdClass
     */
    public function uploadFile($file)
    {
        $name = basename($file);
        $name = $this->extractSpecialCharacters($name);
        $openFile = fopen($file, 'r');
        $content = fread($openFile, filesize($file));
        //You need to activate fileinfo
        $content_type = mime_content_type($file);
        $newFile = $this->createFile($name, array(), $content, $content_type, array());
        if ($newFile) {
            return $newFile;
        }
    }

    /**
     * Downloads a file into temp directory and display the content by the browser.
     *
     * @param string $id
     */
    public function displayFile($id)
    {
        $file = $this->getObjectById($id);
        $name = $file->properties['cmis:name'];
        $mime = $file->properties['cmis:contentStreamMimeType'];
        $mime = str_replace("\t", '', $mime);
        $mime = str_replace("\n", '', $mime);
        $length = $file->properties['cmis:contentStreamLength'];
        $content = $this->repository->getContentStream($id);
        $name = str_replace(' ', '_', $name);
        $tempFile = fopen($name, 'wb');
        fwrite($tempFile, $content);
        fclose($tempFile);
        $domain = $_SERVER['SERVER_NAME'];
        $path = getcwd().'/'.$name;
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: '.$mime);
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: '.filesize($name));
        if (ob_get_contents()) {
            ob_end_clean();
        }
        flush();
        readfile($path);
        unlink($name);
        exit();
    }

    /**
     * Downloads a file by its id.
     *
     * @param string $id
     */
    public function downloadFile($id)
    {
        $file = $this->getObjectById($id);
        $name = $file->properties['cmis:name'];
        $mime = $file->properties['cmis:contentStreamMimeType'];
        $mime = str_replace("\t", '', $mime);
        $mime = str_replace("\n", '', $mime);
        $length = $file->properties['cmis:contentStreamLength'];
        $content = $this->repository->getContentStream($id);
        $name = str_replace(' ', '_', $name);
        $tempFile = fopen($name, 'wb');
        fwrite($tempFile, $content);
        fclose($tempFile);
        $domain = $_SERVER['SERVER_NAME'];
        $path = getcwd().'/'.$name;
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: '.$mime);
        header('Content-Disposition: attachment; filename="'.$name."\"\n");
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: '.filesize($name));
        if (ob_get_contents()) {
            ob_end_clean();
        }
        flush();
        readfile($path);
        unlink($name);
        exit();
    }

    /**
     * Downloads a file by its id to a temp folder.
     *
     * @param string $id
     *
     * @return string
     */
    public function moveFile($id)
    {
        $file = $this->getObjectById($id);
        $name = $file->properties['cmis:name'];
        $length = $file->properties['cmis:contentStreamLength'];
        $content = $this->repository->getContentStream($id);
        $name = str_replace(' ', '_', $name);
        $tempFile = fopen($name, 'wb');
        fwrite($tempFile, $content);
        fclose($tempFile);
        $domain = $_SERVER['SERVER_NAME'];
        $path = getcwd().'/'.$name;

        return $path;
    }

    /**
     * Downloads a folder as zip.
     *
     * @param string $id
     */
    public function downloadFolder($id, $previousPath = null, $zip = null, $firstPath = null)
    {
        $path = $previousPath;
        $obj = $this->getObjectById($id);
        $folderName = $obj->properties['cmis:name'];
        if (!is_null($path)) {
            $zip = $zip;
            $path = $path.'/'.$folderName;
            //var_dump(basename($path));
            $zip->addEmptyDir(basename($path));
        } else {
            $createFolder = false;
            $path = getcwd().'/'.$folderName;
            $firstPath = $path;
        }
        if (!file_exists($path)) {
            $createFolder = mkdir($path, 0777, true);
        } else {
            $createFolder = true;
        }
        if ($createFolder) {
            //chmod($path, 0777);
            if (is_null($zip)) {
                $zip = new ZipArchive();
                $zipName = $folderName.'.zip';
                if ($zip->open($path.'/'.$zipName, ZipArchive::CREATE) !== true) {
                    exit("could not open <$zipName>\n");
                }
            }

            $children = $this->getChildrenId($id);
            /*
            echo "<pre>";
            print_r($children);
            echo "</pre>";
            die();*/
            $files = array();
            $c = 0;
            for ($i = 0; $i < count($children->objectList); ++$i) {
                if ($children->objectList[$i]->properties['cmis:baseTypeId'] == 'cmis:folder') {
                    $this->downloadFolder($children->objectList[$i]->id, $path, $zip, $firstPath);
                } else {
                    $fileName = $children->objectList[$i]->properties['cmis:name'];
                    $length = $children->objectList[$i]->properties['cmis:contentStreamLength'];
                    $content = $this->repository->getContentStream($children->objectList[$i]->id);
                    var_dump($path);
                    $tempFile = fopen($path.'/'.$fileName, 'wb');
                    fwrite($tempFile, $content);
                    fclose($tempFile);
                    $files[$c] = $path.'/'.$fileName;
                    ++$c;
                }
            }

            for ($i = 0; $i < count($files); ++$i) {
                //var_dump($files[$i]);
                //var_dump(basename($files[$i]));
                //die();
                if ($zip->filename != "$folderName.zip") {
                    $zip->addFile($files[$i], str_replace($firstPath, '', $files[$i]));
                } else {
                    $zip->addFile($files[$i], basename($files[$i]));
                }
            }
            if (is_null($previousPath)) {
                $zip->close();
                $zipPath = $path.'/'.$zipName;
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Content-Description: File Transfer');
                header('Content-type: application/zip');
                header('Content-Disposition: attachment; filename="'.$zipName."\"\n");
                header('Content-Transfer-Encoding: Binary');
                header('Expires: 0');
                header('Pragma: public');
                header('Content-Length: '.filesize($zipPath));
                if (ob_get_contents()) {
                    ob_end_clean();
                }
                flush();
                readfile($zipPath);
                $this->deleteDir($path);
                exit();
            }
        }
    }

    /**
     * Gets the children of the folder previously setted.
     *
     * @return stdClass
     */
    public function getChildrenFolder()
    {
        return $this->repository->getChildren($this->parentFolder->id);
    }

    /**
     * Gets the children of a folder by its Id.
     *
     * @param string $id
     *
     * @return stdClass
     */
    public function getChildrenId($id)
    {
        return $this->repository->getChildren($id);
    }

    /**
     * Gets an object by its Id.
     *
     * @param string $id
     *
     * @return stdClass
     */
    public function getObjectById($id)
    {
        return $this->repository->getObject($id);
    }

    /**
     * Deletes Object by its id.
     *
     * @param string $id
     * @param array  $options
     *
     * @return stdClass
     */
    public function delete($id, $options = array())
    {
        return $this->repository->deleteObject($id, $options);
    }

    /**
     * Executes a query to repository.
     *
     * @param string $query
     *
     * @example $query = 'SELECT * FROM cmis:document';
     *
     * @return stdClass
     */
    public function query($query)
    {
        return $this->repository->query($query);
    }

    /*
    *
    *	Internal Use
    *
    *	check if the folder already exists before to create it
    */

    /**
     * Determines if it exists folder.
     *
     * @param string $name
     *
     * @return bool True if exists folder, False otherwise.
     */
    private function existsFolder($name)
    {
        $obj = $this->repository->getChildren($this->parentFolder->id);
        $name = str_replace('(', '', $name);
        $name = str_replace(')', '', $name);
        $continue = true;
        $c = 0;
        while ($continue and $c < count($obj->objectList)) {
            if ($obj->objectList[$c]->properties['cmis:objectTypeId'] == 'cmis:folder') {
                if ($obj->objectList[$c]->properties['cmis:name'] == $name) {
                    $continue = false;
                }
            }
            $c = $c + 1;
        }
        if (!$continue) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if exists file.
     *
     * @param string $name
     *
     * @return bool
     */
    private function FileExists($name)
    {
        $obj = $this->repository->getChildren($this->parentFolder->id);
        $name = str_replace('(', '', $name);
        $name = str_replace(')', '', $name);
        $continue = true;
        $c = 0;
        while ($continue and $c < count($obj->objectList)) {
            if ($obj->objectList[$c]->properties['cmis:objectTypeId'] == 'cmis:document') {
                if ($obj->objectList[$c]->properties['cmis:name'] == $name) {
                    $continue = false;
                }
            }
            $c = $c + 1;
        }
        if (!$continue) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if it has folders.
     *
     * @param string $id
     *
     * @return bool True if has folders, False otherwise.
     */
    private function hasFolders($id)
    {
        $obj = $this->repository->getChildren($id);
        $continue = true;
        $c = 0;
        while ($continue and $c < count($obj->objectList)) {
            if ($obj->objectList[$c]->properties['cmis:objectTypeId'] == 'cmis:folder') {
                $continue = false;
            }
            $c = $c + 1;
        }
        if (!$continue) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a directory.
     *
     * @param string $dir The dir
     */
    private function deleteDir($dir)
    {
        $current_dir = opendir($dir);
        while ($entryname = readdir($current_dir)) {
            if (is_dir("$dir/$entryname") and ($entryname != '.' and $entryname != '..')) {
                deldir("${dir}/${entryname}");
            } elseif ($entryname != '.' and $entryname != '..') {
                unlink("${dir}/${entryname}");
            }
        }
        closedir($current_dir);
        rmdir(${'dir'});
    }

    /**
     * Extracts Special characters.
     *
     * @param string $word
     *
     * @return string
     */
    private function extractSpecialCharacters($word)
    {
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
            array('n', 'N', 'c', 'C'),
            $word
        );

        $word = str_replace(
            array('\\', '¨', 'º', '~',
                 '#', '@', '|', '!', '"',
                 '$', '%', '&', '/',
                 '(', ')', '?', "'", '¡',
                 '¿', '[', '^', '`', ']',
                 '+', '}', '{', '¨', '´',
                 '>', '< ', ';', ',', ':', ),
            '',
            $word
        );

        return $word;
    }
}

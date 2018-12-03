<?php
declare(strict_types=1);

use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Http\Response as Response;
use Phalcon\Mvc\View\Simple as View;
use Ds\Vector;
use NavikeyCore\Library\TokenHandler;
use NavikeyCore\Models\Users;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \MongoDB\Driver\Manager;
use NavikeyCore\Library\FileLoader;
use MongoDB\BSON\ObjectId;
use NavikeyCore\Library\SRTMGeoTIFFReader;

class ApiController extends NavikeyCore\Controllers\ApiBaseController
{
    public function initialize()
    {
        //header('Content-Type: html; charset=UTF-8');
        $string_arg = new Vector(["type", "geojson", "file", "geometry_type","tplnr", "type_line", "no_opory", "near","name","info","name","id","objects",
            "ps", "res_type","res_id", "po_id", "fil_id", "worker_number", "regex", "fieldRegex", "skip", "type_worker", "type_obj", "deviceId", "timeA", "timeB",
            "names", "bukrs", "location", "balance", "balance_name", "TypeByTplnr", "filId", "kapzatr", "polygon", "geometry", "voltage", "balance_name", "resId", "poId", "walk", "typeStaff",
            "accessToken","refreshToken"]);
        $float_arg = new Vector(["lon1", "lat1", "lon2", "lat2", "lon", "lat", "year_0",
            "year_1", "points", "limit"]);
        $layers = new Vector(['Opory','ResCenter' ,'EmergencyReserve','Lines', 'Ps', 'Loss', 'Ztp', 'Workers', 'Track', 'Region', 'Res', 'Rise',
            'UniversRegions', 'UniversObjs', 'UniversLines', 'UniversPs','UniverseWays', 'LineBonds', 'Sap', 'MobileControllers', 'ElectricMeters',
            'MobileControllersTracks', 'PsArea', 'Message', "Staff", "WorkersType"]);
        parent::initialize();
        parent::setArgs($string_arg, $float_arg, $layers);
        $this->users = new UsersMrsk($this->config->database->dbname, $this->config);
    }

    public function postmessageAction()
    {
        $files = $this->request->getUploadedFiles();

        if (!count($files) || !isset($files[0])) {
            $this->logger->info("Необходим zip файл\n");
            echo $this->status->getStatusInfo(400, "Zip file is need");
            return;
        }
        $file = $files[0];

        $tmpName = $file->getTempName();
        $zip = new ZipArchive();
        if ($zip->open($tmpName, ZipArchive::CREATE) !== true) {
            $this->logger->info("Невозможно открыть <$tmpName>\n");
            echo $this->status->getStatusInfo(400, "Zip incorrect");
            return;
        }
        $zip_dir = $this->config->application->tmp_path . date('YmdHis') . "/";
        $zip->extractTo($zip_dir);
        $zip->close();
        echo $this->status->getStatusInfo(200, "Ok");
        $media = new NavikeyCore\Library\Media();
        $list = $media->load($zip_dir . "message/");
        $hrefs = $media->getHrefs($list, $zip_dir . "message/");
        unset($media);
        $message = new Message($this->config->database->dbname);
        $content = file_get_contents($zip_dir . "message/message.json");
        $text_message = json_decode($content, true);
        $message->load($text_message, $hrefs, $zip_dir . "message/");
        unset($message);
        if (isset($this->config->application->resend)) {
            $curl = new \NavikeyCore\Library\CURLLoader();
            $this->logger->debug((string)$curl->fileSend($this->config->application->resend, $file->getTempName(), $file->getName()));
        }
    }

    public function getSapAction()
    {
        $get = $this->request->getQuery();
        $file = "sap.sap";
        $tplnr = $get['tplnr'];
        $text = $this->viewSimple->render(
            "../var/config/sap.ini", [
                "tplnr" => $tplnr . "\n",
            ]
        );
        $resp = new Response();

        $resp->setHeader("Content-Disposition", 'attachment; filename="' . $file . '"');
        $resp->setHeader("Content-Type", "application/octet-stream");
        $resp->setContent($text);
        return $resp;
    }

    public function authAction()
    {
        $tokenHandler = new TokenHandler($this->config->token, $this->config->database->dbname);
        $get = $this->request->getQuery();
        $username = $get["username"];
        $password = $get["password"];
        if (!isset($username) || !isset($password) || $username == "" || $password == "") {
            echo $this->status->getStatusInfo(200, "Login or password is empty", 1);
            return;
        }
        $user = $this->users->getUser($username, $password);
        if ((isset($user) && $user !== false)) {
            $tokens = $tokenHandler->createTokenPair(["id" => base64_encode($user->name), "name" => $user->name, "role" => $user->role]);
            $tokens["role"] = $user->role;
            echo $this->status->getStatusInfo(200, "OK", 0, $tokens);
            return;
        } else {
            echo $this->status->getStatusInfo(200, "Bad login or password", 1);
            return;
        }
    }

    public function registrationAction()
    {
        $role = parent::getRole();
        if (strcmp($role,"Master_admin")){
            echo $this->status->getStatusInfo(403);
            return;
        }
        $users = new Users($this->config->database->dbname);
        $post = $this->request->getQuery();
        if (!isset($post['name']) || !isset($post['password'])|| !isset($post['role'])) {
            echo $this->status->getStatusInfo(400);
            return;
        }
        if (array_key_exists('properties', $post)){
            $props = json_decode($post['properties'],true);
        }
        else $props=[];
        $exist = $users->findUser("name",$post['name']);
        if (isset($exist)){
            echo $this->status->getStatusInfo(200, "User is exist");
            return;
        }
        $res = $users->createLogPassMrsks($post['name'], $post['password'], $post['role'], $props);
        if ($res){
            echo $this->status->getStatusInfo(200, "User has been created");
        }
        else{
            echo $this->status->getStatusInfo(200, "Cannot create user");
        }
    }


    /*
     * this function is supose to delete MobileDivision and set propertie 'type' of all members to 0
     */
   public function deleteMobileDivisionAction(){
        $role = parent::getRole();
        if (strcmp($role,"Master_admin")){
            echo $this->status->getStatusInfo(403);
            return;
        }
        $post = $_REQUEST;
        $id = $post['id'];
        $manager = new Manager();
        $workersColl = new Collection($manager, $this->config->database->dbname, 'Workers');
        $workersColl->updateMany(["properties.type"=>$id],['$set'=>["properties.type"=>"0"]],['upsert'=>true]);
        $divisionColl = new Collection($manager, $this->config->database->dbname, 'MobileDivisions');
        $divisionColl->deleteOne(["_id"=>new ObjectId($id)]);
    }

    /*
     * this function responsible for creation and updating mobile divisions
     */
    public function createMobileDivisionAction(){
        $role = parent::getRole();
        if (strcmp($role,"Master_admin")){
            echo $this->status->getStatusInfo(403);
            return;
        }
        if ($this->request->hasFiles()) {
            $file = $this->request->getUploadedFiles()[0];
            $tempName = $file->getTempName();
            $fileName = $file->getName();
            $loader = new FileLoader();
            $divPath = $loader->saveFile("img/mobileDivisions",$file,$fileName);
        }
        $post = $_REQUEST;
        if ($post["name"]) {
            $divName = $post["name"];
        }
        $manager = new Manager();
        $coll = new Collection($manager, $this->config->database->dbname, 'MobileDivisions');
        if ($post["id"]){
            $id = new ObjectId($post["id"]);
            $query = [];
            if ($divPath){
                $query["properties.img"] = $divPath;
            }
            if ($divName){
                $query["properties.name"] = $divName;
            }
            $coll->updateOne(["_id"=>$id],['$set'=>$query],['upsert'=>true]);
        }
        else{
            $obj = [];
            $obj["properties"]=[];
            if ($divPath){
                $obj['properties']["img"] = $divPath;
            }
            if ($divName){
                $obj['properties']["name"] = $divName;
                $coll->insertOne($obj);
            }
        }
    }

    /**
     * return Res by Label
     */
    public function getResAction(){
        $manager = new Manager();
        $collection = new Collection($manager,$this->config->database->dbname,'Res');
        $query =['properties.Label'=>['$regex'=>trim($_GET['Label'])]];
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $collection->find($query,$options)->toArray();
        echo json_encode($result);
    }

    /**
     *  return Filiation PO and Res scale values
     */
    public function getFiliationZoomAction(){
        $manager = new Manager();
        $collection = new Collection($manager,$this->config->database->dbname,'ScaleStat');
        $query =[];
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $collection->find($query,$options)->toArray();
        echo json_encode($result);
    }

    public function srtmAction()
    {
        $post = $this->request->getPost();
        $dataReader = new SRTMGeoTIFFReader($this->config->application->srtm);
        if (isset($post["lat"]) && isset($post["lon"])) {
            echo $dataReader->getElevation($post["lat"], $post["lon"], true);
        }

        if (isset($post["latlon"]) && $post["latlon"] !== "") {
            $json = $post["latlon"];
            $latlon = json_decode($json, true);
            if (count($latlon) > 2) {
                echo json_encode($dataReader->getMultipleElevations($latlon, false));
            } else {
                $coord = [$dataReader->getElevation($latlon[0], $latlon[1])];
                echo json_encode($coord);
            }
        }
        unset($dataReader);
    }
}

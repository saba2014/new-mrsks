<?php

declare(strict_types=1);

namespace NavikeyCore\Controllers;

use NavikeyCore\Library\TokenHandler;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
use Ds\Map;
use Ds\Vector;
use MongoDB\Driver\Manager;
use MongoDB\BSON\ObjectID;
use Phalcon\Logger\Adapter\File as FileAdapter;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use NavikeyCore\Library\ElectricObjectsManager;
use NavikeyCore\Models\Users;
use NavikeyCore\Library\StatusPage;
use NavikeyCore\Library\GetObj;
use NavikeyCore\Library\Converter\FormatConverter;
use NavikeyCore\Library\Worker;
use NavikeyCore\Library\SRTMGeoTIFFReader;
use NavikeyCore\Library\Menu;
use NavikeyCore\Library\LayerMobileControllers as MobileControllers;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use NavikeyCore\Library\TokenHandler as Token;
use NavikeyCore\Library\Cluster\Clusterer;

use NavikeyCore\Library\ArgsMaker;

use SoapClient;

class ApiBaseController extends Controller
{

    protected $users, $status, $menu, $mail, $modalFactory, $string_args, $float_args, $layers, $tokenHandler;

    public function initialize()
    {
        $this->view->setRenderLevel(
            View::LEVEL_NO_RENDER
        );
        header('Content-Type: application/json; charset=UTF-8');
        $http_origin = $this->request->getServer("HTTP_ORIGIN");
        header("Access-Control-Allow-Origin: $http_origin");
        $this->status = new StatusPage();
        try {
            $this->users = new Users($this->config->database->dbname);
            $this->mail = new PHPMailer(true);
            $this->modalFactory = new \NavikeyCore\Library\ModalFactory();
            $this->tokenHandler = new TokenHandler($this->config->token, $this->config->database->dbname);
        } catch (\Exception $exception) {
            echo $this->status->getStatusInfo(500, $exception->getMessage());
        }
    }

    public function __destruct()
    {
        unset($this->users, $this->status, $this->mail, $this->modalFactory, $this->tokenHandler);
    }

    public function indexAction()
    {
        echo $this->status->getStatusInfo(200, "Request is need");
    }

    public function refreshTokensAction()
    {
        $newTokens = $this->tokenHandler->refreshTokens($this->request);
        switch ($newTokens) {
            case 0:
                {
                    header("Authorization: 0");
                    echo json_encode($this->tokenHandler->tokenPair);
                    return;
                }
            case 1:
                {
                    header("Authorization: 3");
                    echo $this->status->getStatusInfo(400, "Refresh token is not sended", 1);
                    return;
                }
            case 3:
                {
                    header("Authorization: 3");
                    echo $this->status->getStatusInfo(400, "Refresh token is not correct", 3);
                    return;
                }
            case 2:
                {
                    header("Authorization: 1");
                    echo $this->status->getStatusInfo(400, "Expired token", 2);
                    return;
                }
            case 4:
                {
                    header("Authorization: 3");
                    echo $this->status->getStatusInfo(500);
                    return;
                }
        }
    }

    public function authAction()
    {
        $users = new Users($this->config->database->dbname);
        $get = $this->request->getQuery();
        $username = $get["name"];
        $password = $get["password"];
        if (!isset($username) || !isset($password) || $username == "" || $password == "") {
            echo $this->status->getStatusInfo(200, "Login or password is empty", 1);
            return;
        }
        $user = $users->getUser($username, $password);
        if ((isset($user) && $user !== false)) {
            $tokens = $this->tokenHandler->createTokenPair(["id" => (string)$user->_id, "name" => $user->name]);
            echo $this->status->getStatusInfo(200, "OK", 0, $tokens);
            return;
        } else {
            echo $this->status->getStatusInfo(200, "Bad login or password", 1);
            return;
        }
    }

    public function getAction(string $typeArgument = null)
    {
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        /*$role = $this->session->get('auth')["role"];
        session_write_close();
        */
        if (!isset($role)) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $get_obgs = new GetObj($this->config->database->dbname);
        $objs = new Vector();
        $formatConvertor = new FormatConverter();
        $get = $this->request->getQuery();
        $format = "geojson";
        if (array_key_exists("format", $get)) {
            $format = $get["format"];
        }
        if (array_key_exists("filename", $get)) {
            header("Content-Disposition: attachment; filename='{$get["filename"]}.$format'");
            if (!strcmp($format, "KML")) {
                header('Content-Type: application/vnd.google-earth.kml+xml');
            }
        }
        if (!$formatConvertor->isFormat($format)) {
            echo $this->status->getStatusInfo(400, "Convert format is incorrect", 1);
            unset($get_obgs, $objs, $format);
            return;
        }
        if (!$get_obgs->get($get, $objs, $role)) {
            echo $get_obgs->status_message;
            unset($get_obgs, $objs, $format);
            return;
        }
        echo $formatConvertor->convert($format, $objs->toArray());
        unset($get_obgs, $objs);
    }

    /*
     * Устаревший
     */
    public function getobjsAction()
    {
        $user = $this->tokenHandler->getAccessInfo($this->request);
        if (!isset($user->id)) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $role = $user->role;

        $get_obgs = new GetObj($this->config->database->dbname, $this->string_args, $this->float_args, $this->layers, $this->config);
        $objs = new Vector();
        $formatConvertor = new FormatConverter();
        $get = $this->request->getQuery();
        $format = "geojson";
        if (array_key_exists("format", $get)) {
            $format = $get["format"];
        }
        if (array_key_exists("filename", $get)) {
            header("Content-Disposition: attachment; filename='{$get["filename"]}.$format'");
            if (!strcmp($format, "KML")) {
                header('Content-Type: application/vnd.google-earth.kml+xml');
            }
        }
        if (!$formatConvertor->isFormat($format)) {
            echo $this->status->getStatusInfo(400, "Convert format is incorrect");
            unset($get_obgs, $objs, $format);
            return;
        }
        if (!$get_obgs->get($get, $objs, $role)) {
            echo $get_obgs->status_message;
            unset($get_obgs, $objs, $format);
            return;
        }
        $arr = $objs->toArray();

        if (array_key_exists("cluster", $get) && $get['cluster'] == 'true') {
            if ($get['alg'] == 'dbscan') {
                if (array_key_exists('radius', $get) && $get['radius'] >= 1) {
                    $objs = Clusterer::dbscan($objs, (int)$get['radius'], 1);
                } else {
                    echo $this->status->getStatusInfo(400, "Need parametr 'radius' integer  >= 1");
                    return;
                }
            } elseif ($get['alg'] == 'kmeans') {
                if (array_key_exists('count', $get) && $get['count'] >= 1) {
                    $objs = Clusterer::kmeans($objs, (int)$get['count']);
                } else {
                    echo $this->status->getStatusInfo(400, "Need parametr 'count' integer  >= 1");
                    return;
                }
            } else {
                echo $this->status->getStatusInfo(400, "Clustering algoritm is incorrect");
                return;
            }
        }

        echo $formatConvertor->convert($format, $objs->toArray());
        unset($get_obgs, $objs);
    }

    public function getRoleListAction()
    {
        $list = [
            'Roles' => [
                [
                    "name" => "Guests", "code" => 0
                ],
                [
                    "name" => "Users", "code" => 1
                ],
                [
                    "name" => "Admin", "code" => 2
                ],
                [
                    "name" => "Master_admin", "code" => 3
                ]
            ]
        ];
        $res = json_encode($list);
        echo $res;
        //echo print_r($this->dispatcher);
    }

    public function getTplnrObjsAction()
    {
        echo "\n";
        $formatConvertor = new FormatConverter();
        $format = "geojson";
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $get_obgs = new GetObj($this->config->database->dbname, $this->string_args, $this->float_args, $this->layers);
        $objs = new Vector();
        $get = $this->request->getQuery();
        //  if ($get['page'])
        $get['page'] = $get['page'] - 1;
        $query = json_decode($get['regex']);
        if (is_array($query)) {
            $res = $get_obgs->getSeveralTplnrObject($get, $objs);
            echo $formatConvertor->convert($format, $objs->toArray());
        } else {
            if (!$get_obgs->getTplnrObjects($get, $objs)) {
                echo $get_obgs->status_message;
                return;
            }
            echo $formatConvertor->convert($format, $objs->toArray());
        }
    }

    public function getNameObjsAction()
    {
        /*$role = $this->session->get('auth')["role"];
        session_write_close();*/
        $formatConvertor = new FormatConverter();
        $format = "geojson";
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $get_obgs = new GetObj($this->config->database->dbname, $this->string_args, $this->float_args, $this->layers);
        $objs = new Vector();
        $get = $this->request->getQuery();
        $get['page'] = $get['page'] - 1;
        if (!$get_obgs->getNameObjects($get, $objs)) {
            echo $get_obgs->status_message;
            return;
        }
        echo $formatConvertor->convert($format, $objs->toArray());
    }

    public function getTplnrCountAction()
    {
        //$role = $this->session->get('auth')["role"];
        //session_write_close();
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $get_obgs = new GetObj($this->config->database->dbname, $this->string_args, $this->float_args, $this->layers);
        $count = 0;
        $get = $this->request->getQuery();
        if (!$get_obgs->getTplnrCount($get, $count)) {
            echo $get_obgs->status_message;
            return;
        }
        echo $count;
    }

    public function getNameCountAction()
    {
        /*$role = $this->session->get('auth')["role"];
        session_write_close();
        if (!isset($role)) {
            echo $this->status->getStatusInfo(403);
            return;
        }*/
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $get_obgs = new GetObj($this->config->database->dbname, $this->string_args, $this->float_args, $this->layers);
        $count = 0;
        $get = $this->request->getQuery();
        if (!$get_obgs->getNameCount($get, $count)) {
            echo $get_obgs->status_message;
            return;
        }
        echo $count;
    }

    public function getmenuAction()
    {
        $role = $this->getRole();
        if (!isset($role) || strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $userId = null;
        $mainMenu = new Menu($this->config->database->dbname, $this->config->application->iconsDir, $this->config->mask->path_sort_legend, $this->config->mask->path_sort_legend_univers);
        $post = $this->request->getPost();
        $get = $this->request->getQuery();
        $dpi = 0;
        if (array_key_exists("dpi", $post)) {
            $dpi = $post["dpi"];
        }
        if (array_key_exists("dpi", $get)) {
            $dpi = $get["dpi"];
        }

        $menu = new Vector();
        $menu->push(...$mainMenu->getMenu($mainMenu->findMenu("shared", "free"), $dpi));
        if (isset($userId)) {
            $menu->push(...$mainMenu->getMenu($mainMenu->findMenu("owner_id", $userId), $dpi));
        }
        $jsonMenu = json_encode($menu);
        if ((array_key_exists("application", $post) && $post["application"] == 1) ||
            (array_key_exists("application", $get) && $get["application"] == 1)) {
            echo json_encode($this->checkEnableApplciation($menu));
        } else {
            echo $jsonMenu;
        }
    }

    public function svgToPngAction()
    {
        $post = $this->request->getPost();
        $svg = $post["svg"];
        $dpi = $post["dpi"];
        if (!isset($svg) || $svg === "") {
            echo $this->status->getStatusInfo(400, "SVG post parametr is need");
            return;
        }
        if (!isset($dpi) || $dpi === "") {
            $dpi = '96';
        }
        $output = $this->svgtopng($svg, $dpi, 32);
        echo '{ "image" : "' . base64_decode($output) . '" }';
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

    /*
     * Усторевший
    */
    public function deleteObjectAction()
    {
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0 || strcmp($role, "Users") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $post = $this->request->getPost();
        if (array_key_exists("type", $post)) {
            $type = $post["type"];
        }
        if (array_key_exists("dell_opory", $post)) {
            $del_opory = $post["dell_opory"];
            if (strcmp($del_opory, "true") == 0) $del_opory = true;
            else $del_opory = false;
        } else {
            $del_opory = false;
        }
        if (array_key_exists("tplnr", $post)) {
            $tplnr = $post["tplnr"];
        }
        if (!isset($tplnr) && array_key_exists("id", $post)) {
            $tplnr = $post["id"];
        }
        if (!isset($tplnr) && array_key_exists("doknr", $post)) {
            $tplnr = $post["doknr"];
        }
        $result = new Map(["deleted" => 0]);

        if (!isset($tplnr) || !strlen($tplnr)) {
            echo json_encode($result);
            exit;
        }

        $tplnrs = new Vector(explode("\n", str_replace("\r", '', $tplnr)));

        $crud = new ElectricObjectsManager($this->config->mask->maskurl, $this->config->database->dbname, $this->logger_import);

        $result["deleted"] = $crud->deleteObj($tplnrs, $type, $this->config->mask->backup . date('YmdHis') . ".txt", $del_opory, $user);
        echo json_encode($result);
    }

    /*
     * Усторевший
    */
    public function delAction()
    {
        $post = $this->request->getPost();
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, $post["type"]);
        if (array_key_exists("idfield", $post) && array_key_exists("field", $post)) {
            if (strcmp($post["idfield"], "_id")) {
                $collection->deleteOne(["properties.{$post["idfield"]}" => $post["field"]]);
            } else {
                $collection->deleteOne(["_id" => new ObjectID($post["field"])]);
            }
        }
    }

    public function uploadAction()
    {
        if ($this->request->hasFiles()) {
            $files = $this->request->getUploadedFiles();
            $file = $files[0];
            $XmlFile = $file->getTempName();
            $userfname = $file->getName();
            $logfilename = $this->config->log->uploud . $userfname . "-" . date('YmdHis') . '.html';
            $logger = new FileAdapter($logfilename);
            $formatter = new \NavikeyCore\Plugins\EmptyFormatter();
            $logger->setFormatter($formatter);
            $logger->log("<html>\n<meta charset=\"UTF-8\" />\n<body>\n");
            $crud = new ElectricObjectsManager($this->config->mask->maskurl, $this->config->database->dbname, $logger);
            $crud->updatePath($XmlFile);// запись xml в базу
            $logger->log("</body>\n</html>\n");
            $logger->close();
            $tempPath = $file->getTempName();
            $uploadPath = "up/{$file->getName()}-$userfname-" . date("YmdHis") . ".xml";
            move_uploaded_file($tempPath, $uploadPath);
            $answer = ['answer' => $logfilename];
        } else {
            $answer = ['answer' => "No files!"];
        }
        $json = json_encode($answer);
        echo $json;
    }

    public function uploadtmpAction()
    {
        if ($this->request->hasFiles()) {
            $files = $this->request->getUploadedFiles();
            $file = $files[0];
            $userfname = $file->getName();
            $tempPath = $file->getTempName();
            $uploadPath = "up/pdf-" . date('YmdHis') . $userfname;
            move_uploaded_file($tempPath, $uploadPath);
            $answer = ['answer' => "Successful", "url" => $uploadPath];
        } else {
            $answer = ['answer' => "No files!"];
        }
        $json = json_encode($answer);
        echo $json;
    }

    public function locationAction()
    {
        $in = fopen("php://input", "r");
        $instr = stream_get_contents($in);
        fclose($in);
        if ($this->config->application->debug === 2) {
            $this->track_logger->info($instr);
        }
        $trafic_data = json_decode($instr, true);
        if (!isset($trafic_data)) {
            return;
        }
        $worker = new Worker($this->config->database->dbname);
        if (isset($trafic_data["deviceId"])) {
            $explode = explode("-", $trafic_data["deviceId"]);
            $id = $explode[0];
            $number = null;
            if (array_key_exists(1, $explode)) {
                $number = $explode[1];
            }
            if (!$worker->checkWorker($trafic_data["deviceId"])) {
                if (!isset($number) || !strcmp($number, "unknown")) {
                    $number = null;
                }
                $worker->createWorker($id, $trafic_data["deviceId"], $number);
                $this->track_logger->info("Worker create: $id");
            }
            unset($Worker);
            return;
        }
        if (isset($trafic_data[0]) && isset($trafic_data[0]["trackId"])) {
            $explode = explode("-", $trafic_data[0]["trackId"]);
            $id = $explode[0];
            $worker->updateLocation($trafic_data[0]["trackId"], $trafic_data);
        }
        if (isset($trafic_data["userMsgDeliver"])) {
            $worker->updateMessage($trafic_data["userMsgDeliver"]["deviceId"], (int)$trafic_data["userMsgDeliver"]["result"]);
        }
        unset($Worker);
    }

    /**
     * Function returns user's info from session
     */
    public function getUserInfoAction()
    {
        /*$res = $this->session->get('auth');
        session_write_close();
        if (!isset($res)) {
            echo $this->status->getStatusInfo(200, "Unauthorized");
            return;
        }
        echo json_encode($res);*/
    }

    /**
     * Регистрация пользователя необходимые параметры
     * string name
     * string password
     * string email в формате example@mail ~
     */
    public function registrationAction()
    {
        $get = $this->request->getQuery();
        $email = $get["email"];
        $name = $get["name"];
        $pass = $get["password"];
        if (!isset($email) || !isset($name) || !isset($pass)) {
            echo $this->status->getStatusInfo(400);
            return;
        }
        $users = new Users($this->config->database->dbname);
        if ($users->checkForExisting($name, $email)) {
            echo $this->status->getStatusInfo(200, 'User with such name or email exists', 1);
            return;
        }
        $key = $users->generateUnicKey(array($name));
        $domain = $this->config->domain->domain;
        $link = $domain . "/confirmRegistration?key=" . $key;
        if ($users->createLogPass($name, $pass, $email, $key)) {
            $body = "Пройдите по ссылке для подтверждения регистрации: <a href='" . $link . "'>$link</a>";
            $subject = "Confirm/Подтверждение";
            if (!$this->sendEmail($email, $name, $subject, $body)) {
                echo $this->status->getStatusInfo(200, 'Mailer Error: ' . $this->mail->ErrorInfo, 3);
                return;
            }
            echo $this->status->getStatusInfo(200, 'Unconfirmed user has been created', 0);
        } else return;
    }

    /**
     * Вход пользователя через name и password
     * @return type
     */
    public function loginUserAction()
    {
        $auth;// = $this->session->get('auth');
        unset($auth);
        $get = $this->request->getQuery();
        if (!isset($get['name']) || !isset($get['password'])) {
            echo $this->status->getStatusInfo(400, "Name and password are need");
            return;
        }
        $res = $this->users->logNamePass($get['name'], $get['password']);
        if ($res == true) {
            // $this->view->auth = json_encode($this->session->get('auth'));
        }
        if (!$this->loginUser($res, $get['name'])) {
            echo $this->status->getStatusInfo(200, "User is not exist", 1);
        } else {
            // echo json_encode($this->session->get('auth'));
            //echo $this->status->getStatusInfo(200);
        }
    }

    //Проверка правильности авторизации в вк

    public function checkVkAction()
    {
        $post = $this->request->getPost();
        $vk = new VK();
        $app_cookie = $this->response->getCookies("vk_app_{$this->config->auth->vk_appid}");
        $obj = $vk->getUser($this->config->auth->vk_appsec, $app_cookie);
        unset($vk);
        if (!$obj) {
            echo json_encode(false);
            return;
        }
        $ServerID = $obj['id'];
        $ClientID = $post['mid'];
        $res = false;
        if ($ServerID == $ClientID) {
            $res = true;
            $user = new RegUsers($this->config->database->dbname);
            $user->CreateVk($ServerID);
            unset($user);
        }
        echo json_encode(false);
    }

    //Запрос на создание объекта универсиады
    public function createAction()
    {
        $post = $this->request->getPost();
        $UnivObj = new UniverseObj($this->config->database->dbname);
        $UnivObj->create($post);
    }

    /**
     * функция для закрытия сессии после нажатия пользователем кнопки выход
     */
    public function userOutAction()
    {
        $this->tokenHandler->userExit($this->request);
    }

    public function updateAction()
    {
        $post = $this->request->getPost();
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, $post["type"]);
        $filter = ["properties.{$post["idfield"]}" => $post["id"]];
        if (!strcmp($post["idfield"], "_id")) {
            $filter = ["_id" => new ObjectID($post["id"])];
        }
        if (array_key_exists("idfield", $post) && array_key_exists("field", $post) &&
            array_key_exists("typefield", $post) && array_key_exists("id", $post)) {
            $field = json_decode($post["field"], true);
            $collection->updateOne($filter, ['$set' => ["properties.{$post["typefield"]}" => $field]], ["upsert" => true]);
            /*if (strcmp($post["typefield"],"email")==0){
                $collection->update(array("_id" => new ObjectID($post["id"])), array('$set' => array("properties.email" => (string)$field)), true);
            }*/
        }
        if (array_key_exists("href", $post)) {
            $new_href = [];
            $new_href["href"] = $post["href"];
            if (array_key_exists("name", $post)) {
                $new_href["name"] = $post["name"];
            }
            if (array_key_exists("type_href", $post)) {
                $new_href["type_href"] = $post["type_href"];
            }
            if (array_key_exists("href_id", $post)) {
                $new_href["id"] = $post["href_id"];
                $document = $collection->findOne(["properties.{$post["idfield"]}" => $post["id"]]);
                for ($i = 0; $i < count($document["properties"]["hrefs"]); $i++) {
                    if (!strcmp($document["properties"]["hrefs"][$i]["id"], $new_href["id"])) {
                        $document["properties"]["hrefs"][$i] = $new_href;
                        $collection->updateOne($filter, ['$set' =>
                            $document]);
                        break;
                    }
                }

                $collection->updateOne($filter, ['$addToSet' => ["properties.hrefs" => $new_href]]);
                echo json_encode($new_href);
                return;
            }
            $new_href["id"] = uniqid();
            $collection->updateOne($filter, ['$push' => ["properties.hrefs" => $new_href]]);
            echo json_encode($new_href);
        }
    }

    public function deleteHrefAction()
    {
        $post = $this->request->getPost();
        $manager = new Manager();
        if (!array_key_exists("type", $post)) {
            echo $this->status->getStatusInfo(400, "Bad request need type objects");
            return;
        }
        $collection = new Collection($manager, $this->config->database->dbname, $post["type"]);
        $filter = ["properties.{$post["idfield"]}" => $post["id"]];
        if (!strcmp($post["idfield"], "_id")) {
            $filter = ["_id" => new ObjectID($post["id"])];
        }
        if (array_key_exists("href_id", $post)) {
            if (!array_key_exists("idfield", $post) || !array_key_exists("href_id", $post)) {
                echo $this->status->getStatusInfo(400, "Bad request need tplnr and href_id");
                return;
            }
            $collection->updateOne($filter, ['$pull' =>
                ["properties.hrefs" => ["id" => $post["href_id"]]]]);
            echo json_encode(["id" => $post["href_id"]]);
        }
    }

    public function messageAction()
    {
        $post = $this->request->getPost();
        if (!array_key_exists("id", $post) && !array_key_exists("all", $post)) {
            echo $this->status->getStatusInfo(400, "Bad request need id of worker");
            return;
        }
        $all = 0;
        if (array_key_exists("all", $post) && $post["all"] == "1") {
            $all = 1;
        }
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, "Workers");
        if (array_key_exists("message", $post)) {
            $new_message = [];
            $new_message["id"] = uniqid();
            $new_message["message"] = trim($post["message"]);
            if ($new_message['message'] == '') return;
            $new_message["status"] = "send";
            $new_message["time"] = time();
            if ($all) {
                $collection->updateMany([], ['$push' => ["properties.messages" => $new_message]]);
            } else {
                $collection->updateOne(["properties.deviceId" => $post["id"]], ['$push' =>
                    ["properties.messages" => $new_message]]);
            }
            echo json_encode($new_message);
            $output = "";
            $message = str_replace("'", '"', $post["message"]);
            if ($all) {
                $cursor = $collection->find();
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
                foreach ($cursor as $user) {
                    exec("{$this->config->application->processing} -tcp-server={$this->config->application->server}"
                        . " -deviceId={$user["properties"]["deviceId"]} -message='{$message}'", $output);
                }
            } else {
                exec("{$this->config->application->processing} -tcp-server={$this->config->application->server}"
                    . " -deviceId={$post["id"]} -message='{$message}'", $output);
            }
            foreach ($output as $msg) {
                $this->track_logger->info($msg);
            }
        } else {
            $document = $collection->findOne(["properties.deviceId" => $post["id"]]);
            if (!isset($document["properties"]["messages"]) || (count($document["properties"]["messages"]) == 0)) {
                echo "[]";
                return;
            }
            $bson = $document["properties"]["messages"]->bsonSerialize();
            $last_message = array_pop($bson);
            echo json_encode($last_message);
        }
    }

    public function searchKadastrAction()
    {
        $get = $this->request->getQuery();
        $query = $get["query"];
        $region = $get["region"];
        if ((isset($query)) && (isset($region))) {
            $manager = new Manager();
            $collection = new Collection($manager, $region, "kadastr");
            $a1 = ['$match' => ['$text' => ['$search' => $query]]];
            $a2 = ['$project' => ['kadastrid' => 1, '_id' => 0, 'address' => 1, 'score' =>
                ['$meta' => 'textScore']]];
            $a3 = ['$match' => ['score' => ['$gte' => 1.5]]];
            $a4 = ['$sort' => ['score' => ['$meta' => 'textScore']]];
            $a5 = ['$limit' => 5];
            $ttt = [$a1, $a2, $a3, $a4, $a5];
// $query = Array('' => Array('$geoWithin' => Array('$box' =>Array(Array((float)$_GET["lon1"] , (float)$_GET["lat1"]),Array((float)$_GET["lon2"], (float)$_GET["lat2"])))));
// $query = Array('geometry' => Array('$geoWithin' => Array('$box' =>Array(Array(90 , 55),Array(91 , 56)))));
            $cursor = $collection->aggregate($ttt);
            echo json_encode($cursor->toArray(), JSON_UNESCAPED_UNICODE);
        }
    }

    public function saveGeoJsonAction()
    {
        $get = $this->request->getQuery();
        header('Content-Disposition: attachment; filename="line.json"');
        echo $get["data"];
    }

    public function iconsAction()
    {
        $dir = $this->request->getQuery()["dir"];
        $array = array_diff(scandir($dir), array('..', '.'));
        $new_arr = [];
        foreach ($array as $item) {
            $new_arr[] = $dir . $item;
        }
        echo json_encode(["icons" => $new_arr]);
    }

    /**
     * Получение приватных JS файлов
     * string path
     */
    public function getPrivateJsAction()
    {
        $get = $this->request->getQuery();
        if (array_key_exists("path", $get)) {
            $path = "js-private/" . $get["path"];
        } else {
            echo $this->status->getStatusInfo(400, "Path for file is need.");
            return;
        }
        if (!file_exists($path)) {
            echo $this->status->getStatusInfo(200, "File is not exist", 1);
            return;
        }
        echo file_get_contents($path);
        //header('X-SendFile: ' . realpath($path));
        //header('Content-Type: application/octet-stream');
        //header('Content-Disposition: attachment; filename=' . basename($path));
    }

    protected function startSession($user)
    {
        /* $this->session->set(
             "auth", [
                 "name" => $user->name,
                 "id" => $user->_id,
                 "role" => $user->role,
                 "email" => $user->email
             ]
         );*/
    }

    protected function generateMenu(&$entity_menu, $dpi)
    {
        if (array_key_exists("type", $entity_menu) && !strcmp($entity_menu["type"], "entity")) {
            $properies = [];
            if (array_key_exists("properties", $entity_menu)) {
                $properies = $entity_menu["properties"];
            }
            $this->handler_entity->getSubMenu($entity_menu, $properies);
            $entity_menu["type"] = "layer";
        }
        if (array_key_exists("child", $entity_menu)) {
            foreach ($entity_menu["child"] as &$subtree) {
                $this->generateMenu($subtree, $dpi);
            }
        }
        if (array_key_exists("properties", $entity_menu) &&
            array_key_exists("icon_type", $entity_menu["properties"]) &&
            !strcmp($entity_menu["properties"]["icon_type"], "name")) {
            $entity_menu["properties"]["icon"] = base64_encode($this->handler_entity->getIocn($entity_menu["properties"], $dpi));
            $entity_menu["properties"]["icon_type"] = "icon";
        }
    }

    protected function disableApp(&$menu)
    {
        if (array_key_exists("child", $menu)) {
            $new_menu = [];
            $child = $menu["child"];
            for ($i = 0; $i < count($child); $i++) {
                if (!(array_key_exists("enable_application", $child[$i]) && $child[$i]["enable_application"] == 0)) {
                    $this->disableApp($child[$i]);
                    array_push($new_menu, $child[$i]);
                }
            }
            $menu["child"] = $new_menu;
        }
    }

    protected function checkEnableApplciation(Vector $menu)
    {
        $new_menu = [];
        for ($i = 0; $i < $menu->count(); $i++) {
            if (!(array_key_exists("enable_application", $menu[$i]) && $menu[$i]["enable_application"] == 0)) {
                $this->disableApp($menu[$i]);
                array_push($new_menu, $menu[$i]);
            }
        }
        return $new_menu;
    }

    protected function loginUser(bool $res, string $name)
    {
        if ($res == true) {
            $new_user = [];
            $log_user = $this->users->findUser("name", $name);
            $new_user['_id'] = $log_user['_id'];
            $new_user['role'] = $log_user['role'];
            $new_user['email'] = $log_user['email'];
            $new_user['name'] = $log_user['name'];
            $this->startSession((object)$new_user);
        } else {
            return false;
        }
        return true;
    }

    protected function sendEmail(string $email, string $name, string $subject, string $body)
    {
        try {
            $mail = $this->config->mail;
            $this->mail = new PHPMailer();
            $this->mail->isSMTP();
            // $this->mail->SMTPDebug = 2;
            $this->mail->Host = $mail->Host;  // Specify main and backup SMTP servers
            $this->mail->SMTPAuth = true;                               // Enable SMTP authentication
            $this->mail->Username = $mail->Username;                 // SMTP username
            $this->mail->Password = $mail->Password;                           // SMTP password
            $this->mail->SMTPSecure = $mail->SMTPSecure;                            // Enable TLS encryption, `ssl` also accepted
            $this->mail->Port = $mail->Port;
            $this->mail->addAddress($email, $name);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->From = $mail->from;
            $this->mail->FromName = $mail->from;;
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTestAction()
    {
        $token = new Token($this->config->token, $this->config->database->dbname);
        $request = $this->request;
        $acc = $token->findToken($request);
    }

    public function confirmRegistrationAction()
    {
        $get = $this->request->getQuery();
        $key = $get["key"];
        $users = new Users($this->config->database->dbname);
        $res = $users->confirmUser($key);
        if ($res) {
            echo $this->status->getStatusInfo(200, 'user has been confirmed', 0);
        } else {
            echo $this->status->getStatusInfo(200, 'cannot find user', 1);
        }

    }

    protected function getRole(): string
    {
        $role = $this->tokenHandler->getRole($this->request);
        return $role;
    }

    protected function getId()
    {
        $auth = $this->request->getHeader("Authorization");
        $data = $this->tokenHandler->getAccessInfo($this->request);
        if ($data !== false && isset($data->id)) {
            return $data->id;
        }
        return 0;
    }

    public function getNamesAction()
    {
        $role = $this->getRole();
        if (strcmp($role, "Guests") === 0) {
            echo $this->status->getStatusInfo(403);
            return;
        }
        $coll = new MobileControllers($this->config->database->dbname, "MobileControllers");
        $query['properties.name']['$regex'] = $_GET['name'];
        //$options = ["limit" => 5];
        // seems like phalcon doesn't give a fuck about options array in distinct query so i will limit it manually
        $names = $coll->getNames($query);
        if (count($names) > 5) {
            $names = array_slice($names, 0, 5);
        }
        echo json_encode($names);
    }

    /*
     * Create entry in PathState collection, save path and short path points
     * returns id of entry
     */
    public function insertPathAction()
    {
        $Path = json_decode($_POST['p']);
        $shortPath = json_decode($_POST['sp']);
        $hash = $_POST['h'];
        $manager = new Manager();
        $PathState = new Collection($manager, $this->config->database->dbname, "PathState");
        if (count($Path)) {
            $icons = [];
            $points = [];
            for ($i = 0; $i < count($Path); $i++) {
                if ($Path[$i]->i !== '') {
                    $icons[$i] = $Path[$i]->i;
                }
                $points[] = $Path[$i]->p;
            }
            if (count($icons) !== 0) {
                $query['path']['icons'] = $icons;
            }
            $query['path']['points'] = $points;
        }
        if (count($shortPath)) {
            $query['shortPath']['points'] = $shortPath;
        }
        $query['hash'] = $hash;
        $result = $PathState->insertOne($query);
        $result = (string)$result->getInsertedId();
        echo json_encode($result);
    }

    public function getPathItemsAction()
    {
        $id = $_POST['id'];
        $manager = new Manager();
        $options = array(
            'typeMap' => array(
                'root' => 'array',
                'document' => 'array',
            ),
        );
        $PathState = new Collection($manager, $this->config->database->dbname, "PathState", $options);
        $id = new ObjectID($id);
        $query = array("_id" => $id);
        $result = $PathState->findOne($query);
        echo json_encode($result);
    }

    protected function setArgs(Vector $string_args, Vector $float_args, Vector $layers)
    {
        $this->string_args = $string_args;
        $this->float_args = $float_args;
        $this->layers = $layers;
    }
}

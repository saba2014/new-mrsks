<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use \Firebase\JWT\JWT;
use Phalcon\Config;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use Phalcon\Exception;

/*
 * Class that handles with tokens. Contains encode/decode functionality
 */

class TokenHandler
{

    private $configInfo, $db, $serverName, $accessExp, $refreshExp, $manager, $coll, $bulk, $alg, $privateKey, $publicKey;
    public $tokenPair;

    public function __construct(Config $tokeninfo, string $db)
    {
        JWT::$leeway = 5;
        $this->configInfo = $tokeninfo;
        $this->key = base64_decode((string)$this->configInfo->key);
        $this->accessExp = (int)$this->configInfo->accessTime;
        $this->refreshExp = (int)$this->configInfo->refreshTime;
        $this->serverName = (string)$this->configInfo->iss;
        $this->alg = (string)$this->configInfo->alg;
        if (file_exists($this->configInfo->publicKeyPath)) {
            $this->publicKey = file_get_contents($this->configInfo->publicKeyPath);
        } else {
            throw new \Exception("Public key is not create", 1);
        }
        if (file_exists($this->configInfo->privateKeyPath)) {
            $this->privateKey = file_get_contents($this->configInfo->privateKeyPath);
        } else {
            throw new \Exception("Private key is not create", 1);
        }
        $this->manager = new Manager();
        $this->bulk = new BulkWrite();
        $this->db = $db;
    }

    public function createRefreshToken($info)
    {
        $tokenId = base64_encode(random_bytes(32));
        $issuedAt = time();
        $notBefore = $issuedAt + 1;             //Adding 10 seconds
        $expire = $notBefore + $this->refreshExp;            // Adding 60 seconds
        $serverName = $this->serverName; // Retrieve the server name from config file

        $data = [
            'iat' => $issuedAt,         // Issued at: time when the token was generated
            'jti' => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss' => $serverName,       // Issuer
            'nbf' => $notBefore,        // Not before
            'exp' => $expire,           // Expire
            /*'data' => [                  // Data related to the signer user
                'userId' => $id,

            ]*/
            'data'=>$info
        ];

        $jwt = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $this->privateKey, // The signing key
            $this->alg   // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        return $jwt;
    }

    public function createAccessToken($info)
    {
        $tokenId = base64_encode(random_bytes(32));
        $issuedAt = time();
        $notBefore = $issuedAt + 1;             //Adding 10 seconds
        $expire = $notBefore + $this->accessExp;            // Adding 60 seconds
        $serverName = $this->serverName; // Retrieve the server name from config file

        $data = [
            'iat' => $issuedAt,         // Issued at: time when the token was generated
            'jti' => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss' => $serverName,       // Issuer
            'nbf' => $notBefore,        // Not before
            'exp' => $expire,           // Expire
            'data' => $info
        ];
        $jwt = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $this->privateKey, // The signing key
            $this->alg     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
        return $jwt;
    }


    /*
     * function creates pair of new tokens
     * @param object $data - object that contains information about user: role, name, email
     * @return array of objects $res - 2 tokens: refresh and access
     */
    public function createTokenPair($data)
    {
        $id = $data['id'];
        $info = [
            'name' => $data['name'],
            'id' => $data['id']
        ];
        if (isset($data['role'])) {
            $info['role'] = $data['role'];
        }
        $refreshT = $this->createRefreshToken($info);
        $accessT = $this->createAccessToken($info);
        $date = time() + $this->refreshExp;
        $obj = [
            'id' => $id,
            'expireDate' => $date,
            'token' => $refreshT
        ];

        $this->bulk->update(['properties.id' => $id], ['properties' => $obj], ['upsert' => true]);
        $this->manager->executeBulkWrite("$this->db.Tokens", $this->bulk);

        $res = [
            'refresh_token' => $refreshT,
            'access_token' => $accessT,
            'expire' => time() + $this->accessExp
        ];
        return $res;
    }

    /*
     * @param object $refreshT
     * @param objcet $accessT
     * @return array of objets or false
     */

    public function refreshTokens($request) : int
    {
        try {
            $token = $this->findToken($request, "refresh_token");
            if (!$token) {
                return 1;
            }
            $refreshT = $this->decodetoken($token);
            if (!is_object($refreshT)) {
                return 3;
            }
            $id = $refreshT->data->id;
            $coll = new Collection($this->manager, $this->db, 'Tokens');
            $result = $coll->findOne(['properties.id' => $id, 'properties.token' => $token]);
            if (!isset($result) || (!isset($result->properties)) || time() > $result->properties->expireDate) {
                return 2;
            }
            //$accToken = $this->getAccessInfo($request);
           /* if (is_numeric($accToken)) {
                return 3;
            }*/
            $this->tokenPair = $this->createTokenPair(json_decode(json_encode($refreshT->data), true));
            return 0;
        } catch (Exception $e) {
            return 4;
        }
    }

    public function userExit($request){
        $ref = $this->findToken($request, "refresh_token");
        if ($ref){
            $this->deleteTokenById($ref);
        }
    }

    public function deleteTokenById($refreshT)
    {
        try {
            $decodedT = $this->decodetoken($refreshT);
            if (is_numeric($decodedT))
                return $decodedT;
            $id = $decodedT->data->id;
            $coll = new Collection($this->manager, $this->db, 'Tokens');
            $result = $coll->deleteOne(['properties.id' => $id]);
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    public function findToken($request, $type = "access_token")
    {
        $header = $request->getHeader("Authorization");
        if (isset($header) && strcmp($header, "")) {
            $acc = $this->parseAuthHeader($header);
            return $acc;
        }
        $header = $request->getHeader("refresh_token");
        if (isset($header) && strcmp($header, "")) {
            return $acc;
        }
        /*$cookie = $_COOKIE[$type];
        if (isset($cookie) && strcmp($cookie, "")) {
            $acc = $cookie;
            return $acc;
        }*/
        if (isset($_COOKIE[$type])){
            if (strcmp($_COOKIE[$type], "")){
                $acc = $_COOKIE[$type];
                return $acc;
            }
        }
        $acc = $request->getPost($type);
        if (isset($acc) && strcmp($acc, "")) {
            return $acc;
        }
        $acc = $request->getQuery($type);
        if (isset($acc) && strcmp($acc, "")) {
            return $acc;
        }
        return false;
    }

    public function getAccessInfo($request)
    {
        $token = $this->findToken($request, "access_token");
        if ($token === false) {
            return 1;
        }
        $res = $this->decodetoken($token);
        if (isset($res->data)) {
            return $res->data;
        } else {
            return 1;
        }
    }

    public function getRefreshInfo($request)
    {
        $token = $this->findToken($request, "refresh_token");
        if (!$token)
            return 1;
        $res = $this->decodetoken($token);
        if ($token === false) {
            return 1;
        }
        $res = $this->decodetoken($token);
        if (isset($res->data)) {
            return $res->data;
        } else {
            return 1;
        }
    }

    public function parseAuthHeader($authHeader)
    {
        if ($authHeader) {
            $jwt = sscanf($authHeader, 'Bearer %s');
            if ($jwt) {
                $acc = $jwt[0];
                if (isset($acc) && strcmp($acc, "")) {
                    return $acc;
                }
            }
        }
        return false;
    }

    public function getRole($request)
    {
        $data = $this->getAccessInfo($request);
        if (is_numeric($data)) {
            return "Guests";
        } else {
            return $data->role;
        }
    }

    /*
    * function which encrypt token by using secret key
    * @param string $token
    * @return array $result
    */
    public function decodetoken($token)
    {
        try {
            $result = JWT::decode($token, $this->publicKey, [$this->alg]);
            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
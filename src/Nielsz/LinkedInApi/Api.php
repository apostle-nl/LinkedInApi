<?php
/**
 * @package     Nielsz/LinkedInApi
 * @author      Niels van Hove <niels@nielsvanhove.nl>
 */

/**
 * @namespace
 */
namespace Nielsz\LinkedInApi;

class Api {
    public $clientId;
    public $clientSecret;

    public $redirectUrl;
    public $state;
    public $scope;

    public $accessToken;

    public function __construct($cId, $cSecret) {
        $this->clientId = $cId;
        $this->clientSecret = $cSecret;
    }

    public function setState($state) {
        $this->state = $state;
    }

    public function setRedirectUrl($url) {
        $this->redirectUrl = $url;
    }

    public function setAccessToken($token) {
        $this->accessToken = $token;
    }    

    public function oauthConfirm($code) {
        $response = $this->doGet($this->getAccessUrl($code));
        if($response->success) {
            $this->accessToken = $response->data->access_token;
            return $this->accessToken;
        }
        return false;
    }

    public function getLoginUrl($scope) {
        $this->scope = $scope;
        $url = "https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=".$this->clientId."&state=".$this->state."&scope=".urlencode($this->scope)."&redirect_uri=" . urlencode($this->redirectUrl);
        return $url;
    }

    private function getAccessUrl($code) {
        $url = "https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code&code=".$code."&redirect_uri=" . urlencode($this->redirectUrl)."&client_id=".$this->clientId."&client_secret=" . $this->clientSecret;
        return $url;
    }
    
    public function getMyConnections($onlyCount = true) {
        // if we're only counting, dont get all info, get as little as needed., the data->_total will have the count.
        if($onlyCount == true) {
            $url = "https://api.linkedin.com/v1/people/~/connections?count=1";
        } else {
            $url = "https://api.linkedin.com/v1/people/~/connections";
        }
        
        $response = $this->doGet($url);
        return $response;
    }    
    
    public function getMyUpdates() {
        $url = "https://api.linkedin.com/v1/people/~/network/updates?type=SHAR&scope=self";
        $response = $this->doGet($url);
        return $response;
    }

    public function getMyProfile() {
        $userId = "~";
        return $this->getProfile($userId);
    }

    public function getProfile($id) {
        $fields = array();
        $fields[] = 'id';
        $fields[] = 'first-name';
        $fields[] = 'last-name';
        $fields[] = 'headline';
        $fields[] = 'picture-url';
        $fields[] = 'num-connections';
        $fields[] = 'num-connections-capped';
        $fields[] = 'summary';
        $fields[] = 'specialties';
        $fields[] = 'public-profile-url';

        $fields = implode(",",$fields);
        $url = "https://api.linkedin.com/v1/people/".$id.":(".$fields.")";

        $response = $this->doGet($url);
        return $response;
    }

    public function postShare($data) {
        //http://api.linkedin.com/v1/people/~/shares
        $url = "https://api.linkedin.com/v1/people/~/shares";
        $data = json_encode($data);
        return $this->doPost($url, $data);
    }

    private function doGet($url) {
        if($this->accessToken) {
            if(strpos($url, "?") === false) 
                $url.="?oauth2_access_token=" . $this->accessToken;
            else 
                $url.="&oauth2_access_token=" . $this->accessToken;
        }

        $curl = curl_init($url); 
        curl_setopt($curl, CURLOPT_FAILONERROR, true); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('x-li-format: json'));

        $responseText = curl_exec($curl); 
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = new Response();
        $response->httpStatus = $httpStatus;
        $response->success = in_array($httpStatus, array(200,201,204));
        $response->data = json_decode($responseText);
        return $response;
    }

    private function doPost($url, $data) {
        if($this->accessToken) {
            if(strpos($url, "?") === false) 
                $url.="?oauth2_access_token=" . $this->accessToken;
            else 
                $url.="&oauth2_access_token=" . $this->accessToken;
        }

        $curl = curl_init($url); 
        curl_setopt($curl, CURLOPT_FAILONERROR, true); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json; charset=UTF-8','x-li-format: json'));
        curl_setopt($curl,CURLOPT_POST,true); 
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data); 

        $responseText = curl_exec($curl); 
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = new Response();
        $response->httpStatus = $httpStatus;
        $response->success = in_array($httpStatus, array(200,201,204));
        if($response->success) {
            $response->data = json_decode($responseText);
        } else {
            if($resonse->responseText) {
                $response->data = $response->responseText;
            } else {
                $response->data = "Error " . $httpStatus;
            }
        }
        return $response;

    }
}

class Response
{
    public $httpStatus;
    public $success;
    public $data;
}

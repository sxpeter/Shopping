<?php

/**
 * Created by PhpStorm.
 * User: i2
 * Date: 16/1/20
 * Time: 下午1:13
 */
require_once "../data_management/DSDDatabaseConnector.php";
require_once "../utils/Utils.php";
class DSDAccountManager {
    const COMPANY="company";
    const INDIVIDUAL="individual";
    const SUB="sub";
    const LAWYER="lawyer";
    const SECRETARY="secretary";

    static function addAccount($username, $email, $type){
        $res=DSDDatabaseConnector::get_first_match("select valid,user_id from users where email=:email", array(":email"=>$email));
        if($res&&$res["valid"]==1){
            return false;
        }
        return DSDDatabaseConnector::insertOrUpdate("users", array("valid"=>0, "username"=>$username, "email"=>$email, "type"=>$type, "regtime"=>time()), "user_id");
    }
    static function activateAccountWithPassword($password, $token){
        $salt=Utils::createRandom(6);
        $password=md5($password.$salt);
        return DSDDatabaseConnector::update("users", array("password"=>$password, "salt"=>$salt, "valid"=>1, "activatetime"=>time()), "register_token=:token", array(":token"=>$token));
    }
    static function activateAccountWithPasswordAndUid($password, $uid){
        $salt=Utils::createRandom(6);
        $password=md5($password.$salt);
        return DSDDatabaseConnector::update("users", array("password"=>$password, "salt"=>$salt, "valid"=>1, "activatetime"=>time()), "user_id=:uid", array(":uid"=>$uid));
    }
    static function checkAccount($email, $password, $type=null){
        $info=DSDDatabaseConnector::get_first_match("select password,salt,type from users where email=:email limit 1", array(":email"=>$email));
        if(!$info) return array("success"=>false, "msg"=>"用户名不存在");
        if(md5($password.$info["salt"])==$info["password"]){
            if($type){
                if($type!=$info["type"]){
                    return array("success"=>false, "msg"=>"账户类型不匹配");
                }else{
                    return array("success"=>true);
                }
            }else{
                return array("success"=>true);
            }
        }else{
            return array("success"=>false, "msg"=>"密码错误");
        }
    }
    static function issueAccessTokenWithID($uid, $time=3600){
        $token=Utils::createRandom(32);
        $tokentime=time()+$time;
        DSDDatabaseConnector::insert("authorization", array("token"=>$token, "user_id"=>$uid, "tokentime"=>$tokentime, "type"=>CMDatabaseConnector::get_first_match("select type from users WHERE user_id=:uid", array(":uid"=>$uid), "type")));
//        $time=time();
//        $time=$time+1800;
//        $date=date("D, d M Y H:i:s",$time)." GMT";
//        header('Set-cookie: access_token='.$token.'; expires='.$date.'; path=/'."\n",false);
        return $token;
    }
    static function invalidateAccessToken($token){
        return DSDDatabaseConnector::write("delete from authorization WHERE token=:token", array(":token"=>$token));
    }
    static function uidForEmail($email){
        return DSDDatabaseConnector::get_first_match("select user_id from users where email=:email", array(":email"=>$email), "user_id");
    }
    static function clearAllAccessTokenForUid($uid){
        return DSDDatabaseConnector::write("delete from authorization WHERE user_id=:uid and tokentime<:time", array(":uid"=>$uid, ":time"=>time()));
    }
    static function addSecretaryAccount($username, $email, $password){
        $id=DSDAccountManager::addAccount($username, $email, CMAccountManager::SECRETARY);
        DSDAccountManager::activateAccountWithPasswordAndUid($password, $id);
        return true;
    }
}

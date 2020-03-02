<?php
session_start();
require("openId.php");
require('db.php');
require('apiKey.php');
$db = Basic::connect();
$openID = new LightOpenID('localhost');

if (!$openID->mode) {

        $openID -> identity = 'https://steamcommunity.com/openid';
        header("Location: {$openID->authUrl()}");
    }
    else if ($openID->mode == "cancel") {
        echo "User has canceled authentication.";
        header("Location: index.php");
    } else {
        if(!isset($_SESSION["steamAuth"])){
          $_SESSION["steamAuth"] = $openID->validate() ? $openID-> identity : null;
          $_SESSION["steam64"] = str_replace("https://steamcommunity.com/openid/id/","",$_SESSION["steamAuth"]);

          $isRegQuery = $db->query('Select * from user where steamID = '.$_SESSION["steam64"]);
          $isReg = $isRegQuery -> fetch(PDO::FETCH_ASSOC);
          if($isReg){
            $_SESSION["name"] = $isReg['name'];
            $_SESSION["points"] = $isReg['points'];
            $_SESSION["avatar"] = $isReg['avatar'];
            $_SESSION["steamid3"]=$isReg['steamID3'];
            $_SESSION["myrefcode"]=$isReg['refCode'];

          } else {
            $userInfo = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key="._apiKey."&steamids=".$_SESSION["steam64"]);
            //ustawiam id64 i id3 pozniej edytuje query
            $steamid64=$_SESSION["steam64"];
            $steamid3=strval($steamid64);
            $steamid3=substr($steamid64, -10, 10);

            $steamid3=$steamid3-7960265728;
            if(isset($_SESSION['ref'])){
              $ru=htmlspecialchars($_SESSION['ref']);
              $reffering = $db->query('Select * from user where refCode="'.$ru.'"');
              $refferingU = $reffering -> fetch(PDO::FETCH_ASSOC);
              if($refferingU){
                $prefp= $db->prepare('UPDATE `user` SET points=points+50 WHERE refCode=?');
                $prefp->execute([$ru]);
              }else{

              }
            }else{
              $ru=NULL;
            }

            //koniec obliczania steamid3
            $dd = json_decode($userInfo, true);
            $playerInfo = $dd["response"]["players"][0];
            $_SESSION["name"] = $playerInfo['name'];
            $_SESSION["points"] = 0;
            $_SESSION["avatar"] = $playerInfo["avatar"];
            $_SESSION["steamid3"]=$steamid3;
            $_SESSION["myrefcode"]=$steamid3;
            $dbSend = $db->prepare('INSERT INTO `user`(`name`, `steamID`, `points`, `avatar`,`steamID3`,`refUser`,`refCode`)
              VALUES (:name,:steamID,0,:avatar,:steamID3,:refUser,:refCode)');
            $dbSend ->execute(array(':name' => $playerInfo["personaname"],':steamID' => $playerInfo["steamid"],':avatar' => $playerInfo["avatarfull"],':steamID3' => $steamid3,':refUser' => $ru,':refCode' => $steamid3));
          }
          header("Location: index.php");
        }
    }


?>

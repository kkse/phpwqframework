<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:18
 */

namespace kkse\wqframework\base;


abstract class WeModuleProcessor extends WeBase {

    public $priority;

    public $message;

    public $inContext;

    public $rule;

    public function __construct(){
        global $_W;

        $_W['member'] = array();
        if(!empty($_W['openid'])){
            load()->model('mc');
            $_W['member'] = mc_fetch($_W['openid']);
        }
    }


    protected function beginContext($expire = 1800) {
        if($this->inContext) {
            return true;
        }
        $expire = intval($expire);
        WeSession::$expire = $expire;
        $_SESSION['__contextmodule'] = $this->module['name'];
        $_SESSION['__contextrule'] = $this->rule;
        $_SESSION['__contextexpire'] = TIMESTAMP + $expire;
        $_SESSION['__contextpriority'] = $this->priority;
        $this->inContext = true;

        return true;
    }

    protected function refreshContext($expire = 1800) {
        if(!$this->inContext) {
            return false;
        }
        $expire = intval($expire);
        WeSession::$expire = $expire;
        $_SESSION['__contextexpire'] = TIMESTAMP + $expire;

        return true;
    }

    protected function endContext() {
        unset($_SESSION['__contextmodule']);
        unset($_SESSION['__contextrule']);
        unset($_SESSION['__contextexpire']);
        unset($_SESSION['__contextpriority']);
        unset($_SESSION);
        session_destroy();
    }

    abstract function respond();

    protected function respText($content) {
        if (empty($content)) {
            return error(-1, 'Invaild value');
        }
        if(stripos($content,'./') !== false) {
            preg_match_all('/<a .*?href="(.*?)".*?>/is',$content,$urls);
            if (!empty($urls[1])) {
                foreach ($urls[1] as $url) {
                    $content = str_replace($url, $this->buildSiteUrl($url), $content);
                }
            }
        }
        $content = str_replace("\r\n", "\n", $content);
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'text';
        $response['Content'] = htmlspecialchars_decode($content);
        preg_match_all('/\[U\+(\\w{4,})\]/i', $response['Content'], $matchArray);
        if(!empty($matchArray[1])) {
            foreach ($matchArray[1] as $emojiUSB) {
                $response['Content'] = str_ireplace("[U+{$emojiUSB}]", utf8_bytes(hexdec($emojiUSB)), $response['Content']);
            }
        }
        return $response;
    }

    protected function respImage($mid) {
        if (empty($mid)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'image';
        $response['Image']['MediaId'] = $mid;
        return $response;
    }

    protected function respVoice($mid) {
        if (empty($mid)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'voice';
        $response['Voice']['MediaId'] = $mid;
        return $response;
    }

    protected function respVideo(array $video) {
        if (empty($video)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'video';
        $response['Video']['MediaId'] = $video['MediaId'];
        $response['Video']['Title'] = $video['Title'];
        $response['Video']['Description'] = $video['Description'];
        return $response;
    }

    protected function respMusic(array $music) {
        if (empty($music)) {
            return error(-1, 'Invaild value');
        }
        global $_W;
        $music = array_change_key_case($music);
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'music';
        $response['Music'] = array(
            'Title' => $music['title'],
            'Description' => $music['description'],
            'MusicUrl' => tomedia($music['musicurl'])
        );
        if (empty($music['hqmusicurl'])) {
            $response['Music']['HQMusicUrl'] = $response['Music']['MusicUrl'];
        } else {
            $response['Music']['HQMusicUrl'] = tomedia($music['hqmusicurl']);
        }
        if($music['thumb']) {
            $response['Music']['ThumbMediaId'] = $music['thumb'];
        }
        return $response;
    }

    protected function respNews(array $news) {
        if (empty($news) || count($news) > 10) {
            return error(-1, 'Invaild value');
        }
        $news = array_change_key_case($news);
        if (!empty($news['title'])) {
            $news = array($news);
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'news';
        $response['ArticleCount'] = count($news);
        $response['Articles'] = array();
        foreach ($news as $row) {
            $response['Articles'][] = array(
                'Title' => $row['title'],
                'Description' => ($response['ArticleCount'] > 1) ? '' : $row['description'],
                'PicUrl' => tomedia($row['picurl']),
                'Url' => $this->buildSiteUrl($row['url']),
                'TagName' => 'item'
            );
        }
        return $response;
    }


    protected function respCustom(array $message = array()) {
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'transfer_customer_service';
        if (!empty($message['TransInfo']['KfAccount'])) {
            $response['TransInfo']['KfAccount'] = $message['TransInfo']['KfAccount'];
        }
        return $response;
    }


    protected function buildSiteUrl($url) {
        global $_W;
        $mapping = array(
            '[from]' => $this->message['from'],
            '[to]' => $this->message['to'],
            '[rule]' => $this->rule,
            '[uniacid]' => $_W['uniacid'],
        );
        $url = str_replace(array_keys($mapping), array_values($mapping), $url);
        if(strexists($url, 'http://') || strexists($url, 'https://')) {
            return $url;
        }
        if (uni_is_multi_acid() && strexists($url, './index.php?i=') && !strexists($url, '&j=') && !empty($_W['acid'])) {
            $url = str_replace("?i={$_W['uniacid']}&", "?i={$_W['uniacid']}&j={$_W['acid']}&", $url);
        }
        if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY) {
            return $_W['siteroot'] . 'app/' . $url;
        }
        static $auth;
        if(empty($auth)){
            $pass = array();
            $pass['openid'] = $this->message['from'];
            $pass['acid'] = $_W['acid'];

            $sql = 'SELECT `fanid`,`salt`,`uid` FROM ' . tablename('mc_mapping_fans') . ' WHERE `acid`=:acid AND `openid`=:openid';
            $pars = array();
            $pars[':acid'] = $_W['acid'];
            $pars[':openid'] = $pass['openid'];
            $fan = pdo_fetch($sql, $pars);
            if(empty($fan) || !is_array($fan) || empty($fan['salt'])) {
                $fan = array('salt' => '');
            }
            $pass['time'] = TIMESTAMP;
            $pass['hash'] = md5("{$pass['openid']}{$pass['time']}{$fan['salt']}".config('AUTHKEY'));
            $auth = base64_encode(json_encode($pass));
        }

        $vars = array();
        $vars['uniacid'] = $_W['uniacid'];
        $vars['__auth'] = $auth;
        $vars['forward'] = base64_encode($url);

        return $_W['siteroot'] . 'app/' . str_replace('./', '', url('auth/forward', $vars));
    }


    protected function extend_W(){
        global $_W;

        if(!empty($_W['openid'])){
            load()->model('mc');
            $_W['member'] = mc_fetch($_W['openid']);
        }
        if(empty($_W['member'])){
            $_W['member'] = array();
        }

        if(!empty($_W['acid'])){
            if (empty($_W['uniaccount'])) {
                $_W['uniaccount'] = uni_fetch($_W['uniacid']);
            }
            if (empty($_W['account'])) {
                $_W['account'] = account_fetch($_W['acid']);
                $_W['account']['qrcode'] = tomedia('qrcode_'.$_W['acid'].'.jpg').'?time='.$_W['timestamp'];
                $_W['account']['avatar'] = tomedia('headimg_'.$_W['acid'].'.jpg').'?time='.$_W['timestamp'];
                $_W['account']['groupid'] = $_W['uniaccount']['groupid'];
            }
        }
    }
}
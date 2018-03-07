<?php
namespace kkse\wqframework;

use kkse\wqframework\dtwq\admin\Visitor;
use kkse\quick\lang\Preset as langPreset;
use think\Request;

/**
 * 预定义处理类
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/2/12
 * Time: 16:45
 */
final class Preset extends langPreset
{
    public static $folder_name_map = [
        'web'=>'web',
        'mp'=>'mp',
        'app'=>'app',
    ];

    public static $entrance_name_map = [
        'web'=>'web.php',
        'mp'=>'mp.php',
        'app'=>'app.php',
    ];

    public static $constant_name_map = [
        'web'=>'IN_SYS',
        'mp'=>'IN_MP',
        'app'=>'IN_MOBILE',
    ];

    /**
     * 预定义的函数别名处理列表
     * @var array
     */
    public static $func_map = [
        'load' => [base\Loader::class, 'load'],
    ];

    /**
     * 常量定义
     * @var array
     */
    public static $constant_map = [
        'IN_IA' => true,
        'WQ_ROOT' => __DIR__,
        'TIMESTAMP' => ['__type__'=>'function', 'func'=>'time'],

        'REGULAR_EMAIL' => '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i',
        'REGULAR_MOBILE' => '/1\d{10}/',
        'REGULAR_USERNAME' => '/^[\x{4e00}-\x{9fa5}a-z\d_\.]{3,15}$/iu',
        'REGULAR_PASSWORD' => '/^(?=.*?[a-zA-Z])(?=.*?\d)|(?=.*?[a-zA-Z])(?=.*?[~!@#$%^&*()_<>])|(?=.*?\d)(?=.*?[~!@#$%^&*()_<>])[a-zA-Z\d~!@#$%^&*()_<>]*$/',

        'TEMPLATE_DISPLAY' => 0,
        'TEMPLATE_FETCH' => 1,
        'TEMPLATE_INCLUDEPATH' => 2,

        'ACCOUNT_SUBSCRIPTION' => 1,
        'ACCOUNT_SUBSCRIPTION_VERIFY' => 3,
        'ACCOUNT_SERVICE' => 2,
        'ACCOUNT_SERVICE_VERIFY' => 4,
        'ACCOUNT_OAUTH_LOGIN' => 3,
        'ACCOUNT_NORMAL_LOGIN' => 1,

        'WEIXIN_ROOT' => 'https://mp.weixin.qq.com',

        'CLIENT_IP' => [
            '__type__'=>'function',
            'obj'=> ['__type__'=>'function', 'func'=>'instance', 'class'=>Request::class],
            'func'=>'ip',
            'params'=>[0, true]
        ],

        'IMS_FAMILY' => 'y',//x
    ];


    protected static function doOption(array $option = [], $isReturn = false)
    {
        if ($isReturn) {
            $str = 'namespace{'.PHP_EOL;
            if (!empty($option['root']) && is_dir($option['root'])) {
                $str .= 'define(\'IA_ROOT\', '.var_export($option['root'], true).');'.PHP_EOL;
                $str .= 'define(\'ATTACHMENT_ROOT\', '.var_export($option['root'].'/public/attachment/', true).');'.PHP_EOL;
            }

            if (!empty($option['mode']) && isset(self::$constant_name_map[$option['mode']])) {
                $str .= 'define(\'APP_STATUS\', '.var_export($option['mode'], true).');'.PHP_EOL;
                $str .= 'define(\''.self::$constant_name_map[$option['mode']].'\', true);'.PHP_EOL;
            }

            $str .= 'define(\'DEVELOPMENT\', '.var_export(!empty($option['debug']), true).');'.PHP_EOL;
            $str .= base\Loader::class.'::load()->initctrl();'.PHP_EOL;

            $str .= '}'.PHP_EOL;
            return $str;
        } else {
            if (!empty($option['root']) && is_dir($option['root'])) {
                define('IA_ROOT', $option['root']);
                define('ATTACHMENT_ROOT', IA_ROOT .'/public/attachment/');
            }

            if (!empty($option['mode']) && isset(self::$constant_name_map[$option['mode']])) {
                define('APP_STATUS', $option['mode']);
                define(self::$constant_name_map[$option['mode']], true);
            }

            define('DEVELOPMENT', !empty($option['debug']));

            base\Loader::load()->initctrl();

            if ($option['mode'] == 'web') {
                global $_W;
                $visitor = Visitor::getInstance();
                $user = $visitor->getUser();
                if ($user) {
                    $_W['isfounder'] = $user->isAdministrator();
                    $_W['uid'] = $user->getUid();
                    $_W['username'] = $user->getUsername();
                    $_W['user'] = $user->toArray();

                    //只有登录成功的才设置公众号
                    //$this->setUniacid();//设置选中的公众号
                }

                //$this->checkPermission();//进行权限总控
                //$this->checkFilter();//过滤白名单
            }
        }
        return '';
    }
}


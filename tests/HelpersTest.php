<?php

use PHPUnit\Framework\TestCase;
use DiDom\Document;

/**
 * @covers helpers
 */
final class HelpersTest extends TestCase
{

    protected $dir;

    function __construct()
    {
        parent::__construct();
        $this->dir = path(__DIR__, '..');
    }

    public function testFunc()
    {
        echo getFullURL(), br();
        echo getFullURL(), br();
        echo indentJson(['a' => 'xx', 'b' => ['ccc', 3, 'c']]);
    }

    public function testPath()
    {
        echo path($this->dir, 'extends', 'helpers.php');
    }

    public function testTimeDebug()
    {
        timeDebug();
        sleep(1);
        timeDebug();
        sleep(1);
        timeDebug('forTime');
        for ($i = 0; $i < 999999; $i++) {
            $a = count(['a', 'b', $i]);
        }
        timeDebug('forTime');
        timeDebug();
    }

    public function testGetCertInfo()
    {
        $info = getCertInfo('bastion.baidu.com');
        var_dump($info);
        return $info;
    }

    public function testMkdirs()
    {
        mkdirs(path($this->dir, 'cache', 'cookie'));
        var_dump($this->dir, path($this->dir, 'cache', 'cookie'));
    }

    public function testSimpleCurl()
    {
        global $argv;
        // 更新cookie
        echo date('Y-m-d H:i:s') . " Start...\n";
        $loginUrl = "https://github.com/login";
        $loginHtml = simpleCurl($loginUrl, ['cookie_dir' => './cache/']);
        if ($loginHtml == '<html><body>You are being <a href="https://github.com/">redirected</a>.</body></html>') {
            echo "Logined...\n";
        } else {
            $token = $this->_getUserToken($loginHtml)[0];
            echo "Get authenticity_token ———— $token...\n";
            $loginData = [
                'commit' => 'Sign in',
                'utf8' => '✓',
                'authenticity_token' => $token,
                'login' => $argv[2],
                'password' => $argv[3]
            ];

            // post数据登录
            $headers[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.75 Safari/537.36';
            $headers[] = 'Connection:keep-alive';
            $headers[] = 'Cache-Control:max-age=0';
            $headers[] = 'Accept-Language:zh-CN,zh;q=0.8,en;q=0.6';
            $headers[] = 'Accept:*/*';
            $loginParams = [
                    'header' => $headers,
                    'method' => 'post',
                    'data' => $loginData,
                    'cookie_dir' => './cache/',
                ];
            $html = simpleCurl('https://github.com/session', $loginParams);

            if ($html == '<html><body>You are being <a href="https://github.com/">redirected</a>.</body></html>') {
                echo "Login success...\n";
            } else {
                echo "Login error...\n$html\n";
            }
        }
        // verify
        $header = simpleCurl('https://github.com/settings/developers', [
            'return' => 'header',
            'cookie_dir' => './cache/',
        ]);
        var_dump(substr($header, 0, 66) . ' ...');
    }

    private function _getUserToken($html)
    {
        $doc = new Document($html);
        $list = $doc->find('input[name=authenticity_token]');
        foreach($list as $li) {
            $return[] = $li->attr('value');
        }
        return $return;
    }

    public function testSimpleDump()
    {
        $arr = [1, 2, '233'];
        $obj = new stdClass();
        simpleDump($this->dir, $arr, $obj);
    }

    public function testSimpleLog()
    {
        $arr = [1, 2, '233'];
        $string = 'xx';
        simpleLog(
            path($this->dir, 'cache', 'simpleLog.log'),
            $arr, $string, $_SERVER
        );
    }
}
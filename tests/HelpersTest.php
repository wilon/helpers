<?php

use PHPUnit\Framework\TestCase;

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

    public function testGetFullURL()
    {
        echo getFullURL();
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
        $info = getCertInfo('www.baidu.com');
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
        // simple request
        $res[] = simpleCurl('http://github.com/wilon');

        // get request: http://github.com/wilon?username=wilon&password=test
        $res[] = simpleCurl('http://github.com/wilon', [
            'data' => [
                'username' => 'wilon',
                'password' => 'test',
            ]]
        );

        // return what
        $res[] = simpleCurl('https://api.github.com/', [
            'return' => 'header'
        ]);

        // set header
        $res[] = simpleCurl('https://api.github.com/', [
            'header' => [
                'GET /v4/guides/intro-to-graphql/ HTTP/1.1',
                'Host: developer.github.com'
            ],
        ]);
        // Or you can copy from chrome-dev-tool [ Response Headers  view source ]
        $res[] = simpleCurl('https://api.github.com/', [
            'header' => 'GET /v4/guides/intro-to-graphql/ HTTP/1.1
        Host: developer.github.com
        Connection: keep-alive
        Pragma: no-cache
        Cache-Control: no-cache
        Upgrade-Insecure-Requests: 1
        User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36
        Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8
        Referer: https://developer.github.com/
        Accept-Encoding: gzip, deflate, br
        Accept-Language: en,zh-CN;q=0.8,zh;q=0.6,zh-TW;q=0.4,mt;q=0.2,fr;q=0.2,pt;q=0.2,ja;q=0.2,da;q=0.2,pl;q=0.2,lt;q=0.2',
        ]);

        // post or other..
        $res[] = simpleCurl('https://api.github.com/events', [
            'method' => 'post',
            'data' => [
                'username' => 'wilon',
                'password' => 'test',
            ]
        ]);

        var_dump($res);
    }

    public function testSimpleDump()
    {
        $arr = [1, 2, '233'];
        simpleDump($this->dir, $arr, $this);
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
<?php

if (! function_exists('br')) {
    function br()
    {
        // windows      echo "\r\n";
        // unix\linux   echo "\n";
        // mac          echo "\r";
        return PHP_SAPI == 'cli' ? PHP_EOL : '<br>';
    }
}

if (! function_exists('indentJson')) {
    /**
     * A better show for json.
     */
    function indentJson($data = [])
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if (PHP_SAPI == 'cli') return $json;
        $search = ["\r", "\n", " "];
        $replace = ['<br>', '<br>', '&nbsp;'];
        return str_replace($search, $replace, $json);
    }
}

if (! function_exists('getFullUrl')) {
    /**
     * Get the full URL.
     * @return string
     */
    function getFullUrl()
    {
        if (PHP_SAPI == 'cli') return false;
        $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '';
        $p = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($p, 0, strpos($p, '/')) . $s;
        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_ADDR'];
        $uri = $_SERVER['REQUEST_URI'] == '/' ? '' : $_SERVER['REQUEST_URI'];
        return urldecode("$protocol://$host$uri");
    }
}

if (! function_exists('path')) {
    /**
     * Connect the args for Path.
     * @return string  The path
     */
    function path()
    {
        $args = func_get_args();
        if (empty($args)) return __DIR__;
        $path = [];
        $num = count($args);
        foreach ($args as $k => $arg) {
            $arg = (string) $arg;
            if (!$arg) continue;
            $arg = ($k == 0) ? $arg : ltrim($arg, '/');
            $arg = ($k == $num - 1) ? $arg : rtrim($arg, '/');
            $path[] = $arg;
        }
        return implode('/', $path);
    }
}

if (! function_exists('timeDebug')) {
    /**
     * PHP time debug
     * @param  string $mark
     * @param  string $separate
     * @return array
     */
    function timeDebug($mark = '', $echo = true, $separate = '')
    {
        global $timeDebug;
        $separate = $separate ?: (PHP_SAPI == 'cli' ? PHP_EOL : '<br>');
        $mt = microtime();
        if (!$mark) $mark = 'timeDebug';

        // Mark time
        $timeDebug[$mark][] = (float) (
            substr((string) $mt, 17) . substr((string) $mt, 1, 6)
        );
        $arr = $timeDebug[$mark];
        $endKey = count($arr) - 1;

        // Time diff
        if (array_key_exists($endKey - 1, $arr)) {
            $timeDiff = sprintf('%.5f', $arr[$endKey] - $arr[$endKey - 1]);
            $separate = '    >> ' . $timeDiff . 's' . $separate;
            $markDiff = $endKey . '_' . ($endKey - 1);
            $markDiffKey = $mark . '_diff';
            $timeDebug[$markDiffKey][$markDiff] = $timeDiff;
        }
        if ($echo) {
           echo $mark, '  ',
               date('Y-m-d H:i:s') . substr((string) microtime(), 1, 6),
               $separate;
        }
        return $timeDebug;
    }
}

if (! function_exists('getCertInfo')) {
    /**
     * Get the domain cert info.
     * @param  string $domain
     * @return array
     */
    function getCertInfo($domain = '')
    {
        $g = @stream_context_create(array(
                "ssl" => array(
                    "capture_peer_cert" => true
                ),
            ));
        if (!$g) return false;
        $r = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $g
        );
        if ($r) {
            $cert = stream_context_get_params($r);
            $certInfo = openssl_x509_parse(
                $cert['options']['ssl']['peer_certificate']
            );
            if ($certInfo) return $certInfo;
        }
        if ($fp = tmpfile()) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://$domain");
            curl_setopt($ch, CURLOPT_STDERR, $fp);
            curl_setopt($ch, CURLOPT_CERTINFO, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
            $result = curl_exec($ch);
            if (curl_errno($ch) != 0) {
                $error = "Error:" . curl_errno($ch) . " " . curl_error($ch);
                return $error;
            }
            fseek($fp, 0);
            $str = '';
            while(strlen($str .= fread($fp, 8192)) == 8192);
            fclose($fp);
            return $str;
        }
        return false;
    }
}

if (! function_exists('mkdirs')) {
    /**
     * mkdirs
     * @param  string $dir
     * @return boolen
     */
    function mkdirs($dir = '')
    {
        return is_dir($dir) ?: mkdirs(dirname($dir)) && mkdir($dir);
    }
}

if (! function_exists('simpleCurl')) {
    /**
     * simple curl
     * @param  string $url
     * @param  array  $param
     * @return mix
     */
    function simpleCurl($url = '', $param = [])
    {
        // params init
        if (!$url) return false;
        $parseUrl = parse_url($url);
        if (!isset($param['method'])) $param['method'] = 'get';
        $param['method'] = strtoupper($param['method']);
        if (!isset($param['data'])) $param['data'] = [];
        if (!isset($param['header'])) $param['header'] = [];
        if (!isset($param['cookie'])) $param['cookie'] = [];
        if (!isset($param['return'])) $param['return'] = 'body';
        if (!isset($param['cookie_dir'])) $param['cookie_dir'] = '';

        // cookie keep
        $dir = trim($param['cookie_dir']);
        $keepCookie = false;
        if (!empty($dir) && is_dir($dir) && is_writable($dir)) {
            $sessionKey = md5($parseUrl['host'] . 'simple-curl');
            $cookieFunc = function (
                $action = 'get',
                $cookieData = []
            ) use (
                $sessionKey,
                $dir
            ) {
                if ($action == 'set') {
                    return @file_put_contents("$dir/$sessionKey", json_encode($cookieData));
                } else {
                    return json_decode(@file_get_contents("$dir/$sessionKey"), true);
                }
            };
            $keepCookie = true;
        }

        // curl init
        $ch = curl_init();
        if ($param['method'] == 'GET' && $param['data']) {
            $joint = $parseUrl['query'] ? '&' : '?';
            $url .= $joint . http_build_query($param['data']);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // https
        if ($parseUrl['scheme'] == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        // header
        $header = [];
        if (strpos(json_encode($param['header']), 'User-Agent') === false) {
            $header[] = 'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36';
        }
        if (is_string($param['header'])) {
            foreach (explode("\n", $param['header']) as $v) {
                $header[] = trim($v);
            }
        } else if (is_array($param['header'])) {
            $header = array_merge($header, $param['header']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // cookie keep
        $curloptCookie = '';
        if ($keepCookie == true) {
            $cookieData = $cookieFunc('get');
        } else {
            $cookieData = array();
        }
        if (is_string($param['cookie'])) {
            $curloptCookie .= $param['cookie'];
        } else if (is_array($param['cookie']) && is_array($cookieData)) {
            $cookieData = array_merge($cookieData, $param['cookie']);
        }
        if ($cookieData) {
            foreach ($cookieData as $k => $v) {
                $curloptCookie .= "$k=$v;";
            }
        }
        curl_setopt($ch, CURLOPT_COOKIE, $curloptCookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        // method
        switch ($param['method']){
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
                break;
            case "PUT" :
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
                break;
            case "PATCH":
                curl_setopt($ch, CULROPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
                break;
            case "DELETE":
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
                break;
        }

        // response
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = trim(substr($response, 0, $headerSize));
        $body = trim(substr($response, $headerSize));
        curl_close($ch);

        // update cookie
        preg_match_all('/Set-Cookie:(.*?)\n/', $header, $matchesCookie);
        if (is_array($matchesCookie[1])) {
            foreach ($matchesCookie[1] as $setCookie) {
                foreach (explode(';', $setCookie) as $cookieStr) {
                    @list($key, $value) = explode('=', trim($cookieStr));
                    $cookieData[$key] = $value;
                }
            }
        }
        if ($keepCookie == true) $cookieFunc('set', $cookieData);

        // return
        $return = $param['return'] == 'header' ? $header :
            ($param['return'] == 'all' ? [$header, $body] : $body);
        return $return;
    }
}


if (! function_exists('simpleDump')) {
    /**
     * simple dump
     *
     * @return
     */
    function simpleDump()
    {
        if (func_num_args() < 1) {
            throw new Exception('need args');
        }

        // get args name
        $bt = debug_backtrace();
        $args = _simpledebugGetArgsInfo($bt[0]);
        if (PHP_SAPI == 'cli') {
            foreach ($args as $argName => $arg) {
                $argName = isset($argName) ? $argName : 'null';
                echo "\033[1;31m$argName\033[0m",
                    ' => ',
                    var_dump($arg);
            }
            return;
        }

        // better dump
        ob_start();
        foreach ($args as $argName => $arg) {
            $argName = isset($argName) ? $argName : 'null';
            echo "&wilonlt;span style='color:red'&wilongt;$argName&wilonlt;/span&wilongt;",
                ' => ',
                var_dump($arg);
        }
        echo '<pre style="white-space:pre-wrap;word-wrap:break-word;">' .
            preg_replace(
                array('/\]\=\>\n(\s+)/m','/</m','/>/m', '/&wilonlt;/m', '/&wilongt;/m'),
                array('] => ','&lt;','&gt;', '<', '>'),
                ob_get_clean()
            ) .
            '</pre><br>';
        return;
    }
}

if (! function_exists('simpleLog')) {
    /**
     * simple log
     *
     * @return
     */
    function simpleLog()
    {
        if (func_num_args() < 1) {
            throw new Exception('need args');
        }
        $bt = debug_backtrace();
        $args = _simpledebugGetArgsInfo($bt[0]);
        $file = $bt[0]['args'][0];
        foreach ($args as $argName => $arg) {
            if ($arg === $file) {
                unset($args[$argName]);
            }
        }
        @mkdirs(dirname($file));
        $result['time'] = date('Y-m-d H:i:s');
        $result['data'] = $args;
        $logStr = json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($file, $logStr, FILE_APPEND);
        return;
    }
}

if (! function_exists('_simpledebugGetArgsInfo')) {
    /**
     * simpledebug get args name
     *
     * @param  array $btUse function debug_backtrace
     * @return array        args names
     */
    function _simpledebugGetArgsInfo($btUse)
    {
        global $SimpleDebug;

        // code string
        $file = new \SPLFileObject($btUse['file']);
        $codeStr = '';
        $codeArr = array();
        foreach ($file as $lineNum => $line) {
            $codeStr .= $line;
            $codeArr[$lineNum] = trim($line);
        }

        // function token
        $tokensAll = token_get_all($codeStr);
        foreach ($tokensAll as $k => $token) {
            if ($token[0] == T_COMMENT) {
                continue;
            }
            if (isset($token[1]) && trim($token[1]) == '') {
                continue;
            }
            $tokensUse[] = $token;
        }

        // parse token, get function info
        $funcArr = $funcTmp = array();
        foreach ($tokensUse as $k => $token) {
            if (isset($token[1]) && $token[1] == $btUse['function']) {
                $funcTmp = array();
                // $funcTmp['args'] = array();
                $funcTmp['code'] = array();
                $funcTmp['line'] = $token[2];
                $argsKey = 0;
                continue;
            }
            if (isset($funcTmp['code'])) {
                $funcTmp['code'][] = $token;
            }
            if ($token == ',') {
                isset($argsKey) && $argsKey++;
            }
            if ($token == ')' && $tokensUse[$k+1] == ';' && !empty($funcTmp)) {
                $funcArr[] = $funcTmp;
                unset($funcTmp);
            }
        }
        $parseCodeToken = function ($tokenArr) {
            if ($tokenArr[0] == '(') {
                unset($tokenArr[0]);
            }
            $bracketArr2 = array();
            foreach ($tokenArr as $k => $token) {
                if (is_string($token) && in_array($token, array('(', ')'))) {
                    $bracketArr2[$k] = $token;
                }
            }$bracketArr = array();
            foreach ($tokenArr as $k => $token) {
                $c = count($bracketArr);
                if (is_string($token) && $token == '(') {
                    $bracketArr[] = array('start' => $k);
                    continue;
                }
                if (is_string($token) && $token == ')') {
                    for ($i = $c-1; $i >= 0; $i--) {
                        if (!array_key_exists('end', $bracketArr[$i])) {
                            $bracketArr[$i]['end'] = $k;
                            break;
                        }
                    }
                    continue;
                }
            }
            $argsKey = 0;
            $argsArr = array();
            foreach ($tokenArr as $k => $token) {
                $inArgs = false;
                foreach ($bracketArr as $bracket) {
                    if ($k >= $bracket['start'] && $k <= $bracket['end']) {
                        $inArgs = true;
                        break;
                    }
                }
                if ($inArgs !== true && $token == ',') {
                    isset($argsKey) && $argsKey++;
                    continue;
                }
                !isset($argsArr[$argsKey]) && $argsArr[$argsKey] = '';
                $argsArr[$argsKey] .= is_string($token) ? $token : ($token[1]);
            }
            return $argsArr;
        };
        foreach ($funcArr as $k => $func) {
            $funcArr[$k]['args'] = $parseCodeToken($func['code']);
        }

        // get the function use line
        $funcMark = $funcLine = 0;
        $markArr = array();
        foreach ($funcArr as $func) {
            if ($btUse['line'] < $func['line']) {
                continue;
            }
            $funcLine = $func['line'];
        }
        foreach ($funcArr as $k => $func) {
            if ($funcLine == $func['line']) {
                $markArr[] = $k;
            }
        }
        !isset($SimpleDebug[$funcLine]) && $SimpleDebug[$funcLine] = 0;
        $funcMark = $markArr[$SimpleDebug[$funcLine]];
        $SimpleDebug[$funcLine] = ($SimpleDebug[$funcLine] + 1) % count($markArr);

        // handle args name
        $argsNames = $funcArr[$funcMark]['args'];
        $argsCount = count($btUse['args']);
        foreach ($argsNames as $k => $argName) {
            $argName = trim($argName);
            $argName = rtrim($argName, ',');
            $argName = ltrim($argName, '(');
            $argName = rtrim($argName, ')');
            $argTokenArr = token_get_all('<?php ' . $argName);
            $ln = $rn = 0;
            foreach ($argTokenArr as $argToken) {
                if ($argToken == '(') $ln++;
                if ($argToken == ')') $rn++;
            }
            if (($n = $ln - $rn) > 0) {
                $argName .= str_repeat(')', $n);
            }
            if (!empty($argName)) {
                $result[$argName] = $btUse['args'][$k];
            } else {
                $result[] = $btUse['args'][$k];
            }
        }
        return $result;
    }
}


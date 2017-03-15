<?php
class CacheLog {

    public static $logGearman;
    public static $logFunctionName;
    public static $logFiles = array();
    
    public static function logGearman($applicationName){
        if(empty(self::$logGearman)){
            $gearman = new GearmanClient();
            if(empty(CacheHubWeb::$appConfigs[$applicationName]['chw_log_gearman'])){
                self::error($applicationName, 'null', 'config chw_log_gearman is empty', __FILE__, __LINE__);
                return false;
            }
            $gconfig = CacheHubWeb::$appConfigs[$applicationName]['chw_log_gearman'];
            self::$logFunctionName = empty($gconfig['functionName']) ? 'default' : $gconfig['functionName'];
            $host = $gconfig["host"];
            $port = $gconfig["port"];
            $rs = $gearman->addServer($host, $port);
            if(!$rs){
                self::error(APPLICATION_NAME, 'null', "log gearman add server failure,{$host}:{$port}", __FILE__, __LINE__);
            }
            self::$logGearman = $gearman;
        }
        return self::$logGearman;
    }
    
    public static function debug($applicationName, $cacheGroupName, $cacheKey, $type, $rs, $whereCall){
        $log_type = empty(CacheHubWeb::$appConfigs[$applicationName]['chw_debug_log_type']) ? 'no' : CacheHubWeb::$appConfigs[$applicationName]['chw_debug_log_type'];
        switch($log_type){
            case 'no' :
                return true;
            case 'file' :
                $date = date("Y-m-d H:i:s");
//                $mssageFormat="%s|%s|%s|%s|%s|%s|%s\n";
//                $msg=sprintf($mssageFormat, $date, $applicationName, $cacheGroupName, $cacheKey, $type, $rs, $whereCall);
                $msg = "{$date}|{$applicationName}|{$cacheGroupName}|{$cacheKey}|{$type}|{$rs}|{$whereCall}\n";
                $type = "debug";
                $basepath = self::path($applicationName);
                $logFile = self::getLogFile($basepath, $type);
                self::writeLog($msg, $logFile);
                return true;
            default :
                $gearman = self::logGearman($applicationName);
                if(!$gearman){
                    return false;
                }
                if(empty($cacheKey) && $cacheKey !== 0 && $cacheKey !== '0'){
                    $cacheKey = '';
                }
                if(empty($whereCall)){
                    $whereCall = '';
                }
                $date = date("Y-m-d H:i:s");
                $data['site'] = $applicationName;
                $data['type'] = 'cacheHubWeb_debug';
                $data['ip'] = '';
                $data['agent'] = '';
                $data['url'] = '';
                $data['referer'] = '';
                $data['uid'] = '';
                $data['session_id'] = '';
                $data['created_at'] = $date;
                $data['ext1'] = $cacheGroupName;
                $data['ext2'] = $cacheKey;
                $data['ext3'] = $type;
                $data['ext4'] = $rs;
                $data['ext5'] = $whereCall;
                $data['ext6'] = CACHEHUBWEB_SERVER_VERSION;
                $gearman->doBackground(self::$logFunctionName, implode('|', $data));
                return true;
        }
	}

	public static function error($applicationName, $cacheGroupName, $msg, $file, $line, $data = array()){
        if(!isset(CacheHubWeb::$appConfigs[$applicationName]['chw_error_log']) || CacheHubWeb::$appConfigs[$applicationName]['chw_error_log'] != 1){
            return true;
        }
		$date = date("Y-m-d H:i:s");
		if(is_array($msg)){
			$msg = json_encode($msg);
		}
		if(empty($data)){
			$data = '';
        }
		if(is_array($data)){
			$data = json_encode($data);
		}
        if(!empty($data)){
            $data = '|'.$data;
        }
//		$mssageFormat="%s|%s|%s|%s in %s on %s%s\n";
//		$msg=sprintf($mssageFormat ,$date, $applicationName, $cacheGroupName, $msg, $file, $line, $data);
        $msg_str = "{$date}|{$applicationName}|{$cacheGroupName}|{$msg} in {$file} on {$line}{$data}\n";
        $basepath = self::path($applicationName);
		$logFile = self::getLogFile($basepath, "error");
		self::writeLog($msg_str, $logFile);
	}

	public static function writeLog($msg, $logFile){
        file_put_contents($logFile, $msg, FILE_APPEND);
	}

	public static function getLogFile($basepath, $type, $application=null, $cacheGroupName=null){
        $key = "{$type}_{$application}_{$cacheGroupName}";
        if(empty(self::$logFiles[$key])){
            $date = date("Y-m-d");
            $arr = array($type, $application, $cacheGroupName);
            $dir = '';
            foreach($arr as $str){
                if(!empty($str)){
                    $dir .= '/'.$str;
                }
            }
            $file = $basepath.'/'.CACHEHUBWEB_CLIENT_VERSION."/{$dir}/{$date}.log";
            $path = dirname($file);
            if(!is_dir($path)){
                mkdir($path, 0777, true);
            }
            self::$logFiles[$key] = $file;
        }
		return self::$logFiles[$key];
	}

	public static function path($applicationName){
        if(!empty(CacheHubWeb::$appConfigs[$applicationName]['chw_log_path'])){
            $log_path = CacheHubWeb::$appConfigs[$applicationName]['chw_log_path'];
        }else{
            $log_path = '/data/log/cacheHubWeb/client';
        }
		return $log_path;
	}
    
}

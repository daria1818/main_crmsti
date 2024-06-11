<?
class LoggerRtop{
    static public function writeLog($filePatch,$data){
        if($file = fopen($filePatch, 'a')){
            $data = self::getContetnts($data);
            fwrite($file, $data);
            fclose($file);
            return true;
        } else{
            return false;
        }
    }
    static private function getContetnts($data){
        ob_start();
        echo '-------------------------' . date("F j, Y, g:i a") . '-------------------------', "\n", print_r($data), "\n";
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}
function debugfile($message,$file = "debug.dbg",$path = "/upload/debug/") {

    $message = is_array($message) ? print_r($message,1) : $message;
    $log_path = $_SERVER['DOCUMENT_ROOT'].$path;
    CheckDirPath($log_path,true);
    $log_file = $log_path.$file;
    $info = debug_backtrace();
    $info = $info[0];
    $info['file'] = substr($info['file'],strlen($_SERVER['DOCUMENT_ROOT']));
    $where = date('Y.m.d H:i:s')."{$info['file']}:{$info['line']}";
    $str = $where."\r\n".$message."\r\n";
    //$content = file_get_contents($log_file);
    file_put_contents($log_file,$str,FILE_APPEND);
}

if(!function_exists('DBGF')):
    function DBGF($message,$file = "debug.dbg",$path = "/upload/debug/")
    {
        if(!isset($_COOKIE['DEVDENTLMAN'])):
            return false;
        endif;

        debugfile($message,$file,$path);
    }
endif;

function debugmessage($message, $title = false, $access = true, $color = '#008B8B')
{
    if(
        !isset($_COOKIE['DEVSTIONLINE'])
    ):
        return false;
    endif;
        ?>
    <table border="0" cellpadding="5" cellspacing="0" style="border:1px solid <?=$color?>;margin:2px;z-index: 1000000000;position: relative;background: white;"><tr><td style="text-align:left!important;">
    <?

    if (strlen($title)>0){
        echo '<p style="color:'.$color.';font-size:11px;font-family:Verdana;">['.$title.']</p>';
    }

    if (is_array($message) || is_object($message)){
        echo '<pre style="color:'.$color.';font-size:11px;font-family:Verdana;">'; print_r($message); echo '</pre>';
    }
    else{
        echo '<p style="color:'.$color.';font-size:11px;font-family:Verdana;">'.var_dump($message).'</p>';
    }
        echo '</td></tr><tr><td>'; 
     echo '<div style="font-family:verdana; font-size: 10px; font-weight: normal">'; 
     $a = debug_backtrace(); 
     $a = $a[0]; 
     echo "{$a['file']}: {$a['line']}"; 
     echo '</div>'; 

    ?></td></tr></table><?
}

class RtopLog
{
    public static function writeLog($filePatch, $data)
    {
        if($file = fopen($filePatch, 'a'))
        {
            $data = self::getContents($data);
            fwrite($file, $data);
            fclose($file);
            return true;
        }
        else
        {
            return false;
        }
    }

    private static function getContents($data)
    {
        ob_start();
        echo '-------------------------' . date("F j, Y, g:i a") . '-------------------------', "\n", print_r($data), "\n";
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}
?>
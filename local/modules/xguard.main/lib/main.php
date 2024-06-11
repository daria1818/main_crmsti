<?php
/**
 * xGuard Framework
 *
 * @package    xGuard
 * @subpackage main
 * @copyright  2014 xGuard
 */

namespace xGuard;

/** @noinspection AutoloadingIssuesInspection */

/**
 * Base entity
 */
abstract class Main
{

    /**
     * @var array
     */
    static protected $instance = array();

    /**
     * @var array|bool
     */
    protected $arResult = false;

    /**
     * @var array|bool
     */
    protected $arParams = false;

    /**
     * @var bool|\CMain
     */
    protected $application = false;

    /**
     * @var bool|\CUser
     */
    protected $user = false;

    /**
     * @var bool
     */
    protected $db = false;

    /**
     * @var bool
     */
    protected $cache = false;

    /**
     * @var bool
     */
    protected $cacheId = false;

    /**
     * @var bool
     */
    protected $cacheTime = false;

    /**
     * @var bool
     */
    protected $cachePath = false;

    /**
     * @var array
     */
    protected $saveData = array();

    /**
     * @var bool
     */
    public $__parent = false;

    /**
     * Main constructor.
     *
     * @param array $options
     */
    protected function __construct(array $options = array())
    {
        $options['arResult'] = !\is_array($options['arResult']) ? array()
            : $options['arResult'];
        $options['arParams'] = !\is_array($options['arParams']) ? array()
            : $options['arParams'];

        if (!\is_object($options['application'])) {
            global $APPLICATION;
            $options['application'] =& $APPLICATION;
        }

        if (!\is_object($options['user'])) {
            global $USER;
            $options['user'] =& $USER;
        }

        if (!\is_object($options['db'])) {
            global $DB;
            $options['db'] =& $DB;
        }

        $this->arResult = &$options['arResult'];
        $this->arParams = &$options['arParams'];
        $this->application = &$options['application'];
        $this->user = &$options['user'];
        $this->db = &$options['db'];

        $this->SetParams();
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getResult(string $key=null): array {
        return null === $key ? $this->arResult : $this->arResult[$key];
    }

    /**
     * @param array $params
     */
    public function setAdditionalParams(array $params=null) {
        $this->arParams = array_replace($this->arParams,$params ?? []);
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    public static function GetInstance(array $options = array())
    {
        $class = static::class;

        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new $class($options);
        } else {
            if (\is_array($options['arResult'])) {
                self::$instance[$class]->arResult = &$options['arResult'];
            }

            if (\is_array($options['arParams'])) {
                self::$instance[$class]->arParams = &$options['arParams'];
            }
        }

        return self::$instance[$class];
    }

    /**
     * @param $module
     *
     * @return mixed
     */
    protected function IncludeModule($module)
    {
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        return \Bitrix\Main\Loader::includeModule($module);
    }

    /**
     *
     */
    protected function SetParams()
    {
        $this->arParams['REQUEST'] = &$_REQUEST;
        $_POST = &$this->arParams['REQUEST'];
        $this->SetRecParams($this->arParams['REQUEST']);

        $this->arResult['RESULT'] = (array)$this->arResult['RESULT'];
        /** @noinspection PhpUndefinedClassInspection */
        $this->cache = $this->cache ?: new \CPHPCache;

        //$this->arResult['__parent'] = $this;
    }

    /**
     * @param $array
     */
    protected function SetRecParams(&$array)
    {
        foreach ($array as $key => $value) {
            if (!\is_array($value)) {
                $this->arParams['REQUEST'][$key] = htmlspecialcharsbx($value);
            } else {
                $this->SetRecParams($value);
            }
        }
    }

    /**
     * @param $value
     * @param $line
     */
    protected function Log($value, $line)
    {
        debugfile(array($line, $value,), basename(__FILE__));
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function GetVars(array $options = array()): array
    {
        foreach ($options as $key => $value) {
            if (isset($this->$key)) {
                $options[$key] = &$this->$key;
            } elseif (isset($this->$value)) {
                $options[$value] = &$this->$value;
            }
        }

        return $options;
    }

    /**
     * @param array $options
     */
    protected function GetEvents(array $options = array())
    {
        $options['__CLASS__'] = $options['__CLASS__'] ?? '';
        $options['__FUNCTION__'] = $options['__FUNCTION__'] ?? '';
        $options['TYPE'] = $options['TYPE'] ?? '';
        $options['MODULE'] = $options['MODULE'] ?? '';
        $options['PARAMS'] = $options['PARAMS'] ?? array();

        /** @noinspection PhpUndefinedFunctionInspection */
        foreach (
            GetModuleEvents(
                $options['MODULE'],
                $options['__CLASS__'].'\\'.$options['__FUNCTION__'].'\\'
                .$options['TYPE'],
                true
            ) as $arEvent
        ):
            /** @noinspection PhpUndefinedFunctionInspection */
            ExecuteModuleEventEx($arEvent, array($options['PARAMS']));
        endforeach;
    }

    /**
     * @param        $message
     * @param string $file
     * @param string $path
     */
    public function DebugFile(
        $message,
        $file = 'debug.dbg',
        $path = '/upload/debug/'
    ) {
        $message = \is_array($message)||\is_object($message)?print_r((array)$message, true) : $message;
        $log_path = $_SERVER['DOCUMENT_ROOT'].$path;
        \function_exists('CheckDirPath')
            ? CheckDirPath($log_path, true)
            : mkdir(
            $log_path,
            0777,
            true
        );
        $log_file = $log_path.$file;
        $info = debug_backtrace();
        $info = $info[0];
        $info['file'] = substr(
            $info['file'],
            \strlen($_SERVER['DOCUMENT_ROOT'])
        );
        $str = implode(
                ':',
                array(date('Y.m.d H:i:s'), $info['file'], $info['line'])
            )."\r\n".$message."\r\n";
        file_put_contents($log_file, $str, FILE_APPEND);
    }

    /**
     * @param      $message
     * @param bool $title
     * @param bool $ignore
     */
    public function DebugMessage($message, $title = false, $ignore = false)
    {
        if (!$ignore && false === strpos($_SERVER['REMOTE_ADDR'], '194.228.20.')
        ) {
            return;
        }
        echo '
		<table border="0" cellpadding="5" cellspacing="0" style="background:#ffffff;border:1px solid #008B8B;margin:2px;">
			<tr>
				<td align="left">
					<p style="color:#000000;font-size:11px;font-family:Verdana, Arial, Helvetica, sans-serif;">', ($title
            ?: ''), '</p>
					<pre style="color:#000000;font-size:11px;font-family:Verdana, Arial, Helvetica, sans-serif;text-align:left;padding:5px;">';
        if (\is_array($message) || \is_object($message)) {
            print_r($message);
        } else {
            var_dump($message);
        }
        echo '</pre>
				</td>
			</tr>
			<tr>
				<td>
					<div style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal">
		';
        $a = debug_backtrace();
        $a = $a[0];
        echo $a['file'], ':', $a['line'], '
					</div>
				</td>
			</tr>
		</table>';
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     */
    public function __call($name = '', array $arguments = array())
    {
        return $this;
    }

    /**
     * @return array
     */
    public static function BackTrace(): array
    {
        $arResult = array();
        $arDebugs = debug_backtrace();

        foreach ($arDebugs as $key => $arDebug):
            if ((int)$key === 0):continue;endif;
            $arResult[] = array(
                'function' => $arDebug['function'],
                'file'     => $arDebug['file'],
                'line'     => $arDebug['line'],
            );
        endforeach;

        return $arResult;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    public static function GetModal(array $options = array())
    {
        $options = array_replace(
            array(
                '#ID#'                    => '',
                '#CLASS#'                 => '',
                '#MODAL_DATA#'            => 'show.bs.modal shown.bs.modal hide.bs.modal hidden.bs.modal loaded.bs.modal',
                '#MODAL_DATA_EVENT#'      => '',
                '#MODAL_DATA_TRIGGER#'    => '',
                '#TITLE#'                 => '',
                '#FORM_ACTION#'           => '',
                '#FORM_ID#'               => '',
                '#FORM_CLASS#'            => '',
                '#FORM_DATA#'             => '',
                '#BODY#'                  => '',
                '#BUTTON_WRAP_ID#'        => '',
                '#BUTTON_WRAP_CLASS#'     => '',
                '#BUTTON_WRAP_DATA#'      => '',
                '#BUTTON_ID#'             => '',
                '#BUTTON_HREF#'           => 'javascript:void(0);',
                '#BUTTON_TITLE#'          => '',
                '#BUTTON_TITLE_BEFORE#'   => $options['BUTTON_TITLE_BEFORE'] ??
                    GetMessage(
                        'XGUARD_BUTTON_TITLE_BEFORE'
                    ),
                '#BUTTON_TITLE_AFTER#'    => $options['BUTTON_TITLE_AFTER'] ??
                    $options['BUTTON_TITLE'],
                '#BUTTON_TITLE_DEFAULT#'  => $options['BUTTON_TITLE_DEFAULT'] ??
                    $options['BUTTON_TITLE'],
                '#BUTTON_DATA#'           => '',
                '#BUTTON_CLASS#'          => '',
                '#SUCCESS_TEXT#'          => '',
                '#SUCCESS_ID#'            => '',
                '#SUCCESS_CLASS#'         => '',
                '#SUCCESS_DATA#'          => '',
                '#BFORM_NOTES_WRAP_ID#'   => '',
                '#FORM_NOTES_WRAP_CLASS#' => '',
                '#FORM_NOTES_WRAP_DATA#'  => '',
                '#FORM_NOTES_TEXT#'       => '',
            ),
            $options
        );

        return GetMessage('XGUARD_MAIN_MODAL', $options);
    }
}
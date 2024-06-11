<?
/**
 * xGuard Framework
 * @package xGuard
 * @subpackage main
 * @copyright 2014 xGuard
 */

namespace xGuard\Main\Ajax;

use \xGuard\Main;

/**
 * Base entity
 */
class Init extends \xGuard\Main
{
	public $modeResult	= 'json';
	protected $_ajaxHandler = false;

	public function Init()
	{
		$this->SetParams();

        $this->_ajaxHandler = \xGuard\Main\Ajax\Element::GetInstance(
            array(
                'arResult'      => &$this->arResult,
                'arParams'      => &$this->arParams,
                'application'   => &$this->application,
                'user'          => &$this->user,
                'db'            => &$this->db,
            )
        );

        if(!is_object($this->_ajaxHandler))
        {
            return;
        }

        $this->_ajaxHandler->__parent=$this;

		switch(strtolower($this->arParams['REQUEST']['do']))
		{
			case 'set':
				$this->Set();
			break;
			case 'get':
				$this->Get();
			break;
			case 'clear':
				$this->Clear();
			break;
			case 'check':
				$this->Check();
			break;
			case 'add':
				$this->Add();
			break;
			case 'remove':
				$this->Remove();
			break;
			case 'update':
				$this->Update();
			break;
			default:
			break;
		}
		
		return $this;
	}
	
	protected function Set()
	{
		$method	= 'Set'.$this->arParams['REQUEST']['mode'];
		
		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}
	
	protected function Get()
	{
		$method	= 'Get'.$this->arParams['REQUEST']['mode'];

		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}
	
	protected function Clear()
	{
		$method	= 'Clear'.$this->arParams['REQUEST']['mode'];
		
		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}

	protected function Check()
	{
		$method	= 'Check'.$this->arParams['REQUEST']['mode'];

		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;

		return $this;
	}

	protected function Add()
	{
		$method	= 'Add'.$this->arParams['REQUEST']['mode'];
		
		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}
	
	protected function Remove()
	{
		$method	= 'Remove'.$this->arParams['REQUEST']['mode'];
		
		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}
	
	protected function Update()
	{
		$method	= 'Update'.$this->arParams['REQUEST']['mode'];
		
		method_exists($this->_ajaxHandler,$method)?$this->_ajaxHandler->$method():false;
		
		return $this;
	}

    public function GetResult()
    {
        header("Access-Control-Allow-Origin:*");

        switch($this->modeResult)
        {
            case 'json':
                header("Content-Type: application/json");
                echo json_encode($this->arResult['RESULT']);
                break;
            default:
                echo $this->arResult['RESULT'];
                break;
        }

        return $this;
    }
}
?>

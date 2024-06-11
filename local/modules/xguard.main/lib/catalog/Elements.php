<?
/**
* Bork Framework
* @package Bork
* @subpackage main
* @copyright 2014 Bork
*/

namespace xGuard\Main\Catalog;

IncludeModuleLangFile(__FILE__);

/**
 * Class Elements
 * @package Bork\Main\Catalog
 */
class Elements
{
    /**
     * @return ElementsQuery
     */
    public static function find()
    {
        return new ElementsQuery();
    }
}

/**
 * Class ElementsQuery
 * @package Bork\Main\Catalog
 */
class ElementsQuery
{
    /**
     * @var \Bitrix\Main\DB\MysqlConnection
     */
    protected $db;
    /**
     * @var array
     */
    protected $table=[];
    /**
     * @var array
     */
    protected $where=[];
    /**
     * @var array
     */
    protected $orWhere=[];
    /**
     * @var array
     */
    protected $select=[];
    /**
     * @var array
     */
    protected $orderBy=[];
    /**
     * @var string
     */
    protected $indexBy='';
    /**
     * @var int
     */
    protected $offset=0;
    /**
     * @var int
     */
    protected $limit=0;
    /**
     * @var string
     */
    protected $sql='';
    /**
     * @var resource
     */
    protected $query='';
    /**
     * @var bool
     */
    protected $build;
    /**
     * ElementsQuery constructor.
     */
    public function __construct()
    {
        $this->db = \Bitrix\Main\Application::getConnection();
    }

    /**
     * @param array $table
     * @return $this
     */
    public function addTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param array $where
     * @param string $logic
     * @return $this
     */
    public function addWhere(array $where=[],string $logic='and'): self
    {
        if(isset($where[0]))
        {
            $condition = [];

            $wheres = !is_array($where[0])?[$where]:$where;

            foreach($wheres as $value) {
                $condition = $this->prepareWhere($condition,$value);
            }

            return !empty($condition)?$this->prepareByLogic($condition,$logic):$this;
        }

        $or=[];

        foreach($where as $field=>$value)
        {
            if(\is_array($value))
            {
                $condition = $field.' in ("'.implode('","',$value).'")';
            }
            else
            {
                $condition = $field.'="'.$value.'"';
            }

            if($logic==='or')
            {
                $or[] = $condition;
            }
            else
            {
                $this->prepareByLogic($condition,$logic);
            }
        }

        if(!empty($or))
        {
            $this->prepareByLogic($or,$logic);
        }

        return $this;
    }

    /**
     * @param array $select
     * @return $this
     */
    public function addSelect(array $select=[])
    {
        foreach($select as $field=>$alias)
        {
            if(is_numeric($field))
            {
                $this->select[$field] = $alias;
            }
            else
            {
                $this->select[$field] = $field.' as '.$alias;
            }
        }

        return $this;
    }

    /**
     * @param $indexBy
     * @return $this
     */
    public function addIndexBy($indexBy)
    {
        $this->indexBy = $indexBy;

        return $this;
    }

    /**
     * @param $orderBy
     * @return $this
     */
    public function addOrderBy($orderBy)
    {
        foreach($orderBy as $by=>$order)
        {
            $this->orderBy[] = $by.' '.$order;
        }

        return $this;
    }
    /**
     * @param int $offset
     * @return $this
     */
    public function addOffset(int $offset=0)
    {
        $this->offset = $offset;

        return $this;
    }
    /**
     * @param int $limit
     * @return $this
     */
    public function addLimit(int $limit=0)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return string
     */
    public function getRawSql()
    {
        $this->builder();

        return $this->sql;
    }
    /**
     * @return array
     * @throws Exception
     */
    public function all()
    {
        $result = [];

        try
        {
            $build = $this->builder();

            while($item = $build->Fetch())
            {
                if(
                    isset($item[$this->indexBy])
                    &&
                    (
                        \is_string($item[$this->indexBy])
                        ||
                        is_scalar($item[$this->indexBy])
                    )
                )
                {
                    $result[$item[$this->indexBy]] = $item;
                }
                else
                {
                    $result[] = $item;
                }

            }

            return $result;
        }
        catch(\Exception $e)
        {
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function eachOne()
    {
        try
        {
            $this->build=$this->build??$this->builder();

            return $this->build->Fetch();
        }
        catch(\Exception $e)
        {
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    /**
     * @return \Bitrix\Main\DB\MysqlConnection
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return \Bitrix\Main\DB\MysqlSqlHelper
     */
    public function getDbHelper()
    {
        return $this->db->getSqlHelper();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function builder()
    {
        $sql = [];

        if(empty($this->select))
        {
            $this->select = ['*'];
        }

        $sql[] = 'SELECT';
        $sql[] = implode(',',$this->select);

        if(empty($this->table))
        {
            throw new Exception('TABLE NOT ADDED',__LINE__);
        }

        $sql[] = 'FROM';
        $sql[] = implode(',',$this->table);

        if(empty($this->where))
        {
            $this->where = ['1=1'];
        }

        $sql[] = 'WHERE';
        $sql[] = '(('.implode(') AND (',$this->where).'))';

        if(!empty($this->orWhere))
        {
            foreach($this->orWhere as $or)
            {
                $sql[] = ' AND (('.implode(') OR (',$or).'))';
            }
        }

        if(!empty($this->orderBy))
        {
            $sql[] = 'ORDER BY';
            $sql[] = implode(',',$this->orderBy);
        }

        if(!empty($this->limit))
        {
            $sql[] = 'LIMIT '.$this->offset.','.$this->limit;
        }

        $sql    = implode(' ', $sql);

        $this->sql = $sql;

        $this->query = $this->db->Query($sql);

        return $this->query;
    }

    /**
     * @param $condition
     * @param $logic
     * @return $this
     */
    protected function prepareByLogic($condition,$logic)
    {
        switch($logic)
        {
            case 'and':
                $this->where[] = $condition;
            break;
            case 'or':
                $this->orWhere[] = $condition;
            break;
        }

        return $this;
    }

    /**
     * @param array $condition
     * @param array $where
     *
     * @return array
     */
    protected function prepareWhere(array $condition,array $where): array
    {
        if(isset($where[2]) && is_string($where[2])) {
            $where[2] = '"'.$where[2].'"';
        }

        switch($where[0])
        {
            case 'regexp':
                $condition[] = $where[1].' REGEXP '.$where[2];
                break;
            case 'not regexp':
                $condition[] = $where[1].' NOT REGEXP '.$where[2];
                break;
            case 'like':
                $condition[] = $where[1].' like '.$where[2];
                break;
            case 'not like':
                $condition[] = $where[1].' not like '.$where[2];
                break;
            case 'in':
                $condition[] = $where[1]." in ('".implode("','",(array)$where[2])."')";
                break;
            case 'not in':
                $condition[] = $where[1]." not in ('".implode("','",(array)$where[2])."')";
                break;
            case 'not between':
                $condition[] = $where[1].' NOT BETWEEN '.implode(' AND ', (array)$where[2]);
                break;
            case 'between':
                $condition[] = $where[1].' BETWEEN '.implode(' AND ', (array)$where[2]);
                break;
            case 'is null':
                $condition[] = $where[1].' is null ';
                break;
            case 'is not null':
                $condition[] = $where[1].' is not null';
                break;
            case '>=':
                $condition[] = $where[1].' >= '.$where[2];
                break;
            case '<=':
                $condition[] = $where[1].' <= '.$where[2];
                break;
            case '>':
                $condition[] = $where[1].' > '.$where[2];
                break;
            case '<':
                $condition[] = $where[1].' < '.$where[2];
                break;
            case '<>':
                $condition[] = $where[1].' <> '.$where[2];
                break;
        }

        return $condition;
    }
}

/**
 * Class Exception
 * @package Bork\Main\Catalog
 */
class Exception extends \Exception
{

}
<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUndefinedConstantInspection */

/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedNamespaceInspection */

/**
 * Bork Framework
 *
 * @package    Bork
 * @subpackage main
 * @copyright  2014 Bork
 */

namespace xGuard\Main\Form;

use \xGuard\Main;

/**
 * Base entity
 */

IncludeModuleLangFile(__FILE__);

/**
 * Class Base
 *
 * @package xGuard\Main\Form
 */
class Base extends Main
{

    /**
     * @var int
     */
    protected $template;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $model;

    /**
     * @var
     */
    protected $params = [];

    /**
     * Base constructor.
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    /**
     * @param int|string $id
     */
    public function setId($id = null)
    {
        $this->id = $id ?? '0';
    }

    /**
     * @param string $template
     */
    public function setTemplate($template = null)
    {
        $this->template = $template ?? 1;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params = null)
    {
        $this->params = $params ?? [];
    }

    /**
     * @param null $model
     */
    public function setModel($model = null)
    {
        if (null !== $model) {
            $this->model = $model;

            return;
        }

        $this->model = [];

        ob_start();

        $this->application->IncludeComponent(
            'bitrix:form.result.new',
            $this->template,
            $this->params,
            false
        );

        $this->model['HTML'] = ob_get_contents();

        ob_end_clean();
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        if (null === $this->model) {
            $this->setModel();
        }

        return $this->model;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function removeModel(int $id = null): bool
    {
        return (bool) (new \CSaleOrderUserProps)->delete($id??$this->code);
    }
}


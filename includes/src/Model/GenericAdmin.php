<?php declare(strict_types=1);

namespace JTL\Model;

use Exception;
use JTL\Alert\Alert;
use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Pagination\Pagination;
use JTL\Services\JTL\AlertServiceInterface;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use function Functional\every;
use function Functional\map;

/**
 * Class GenericAdmin
 * @package JTL\Model
 */
class GenericAdmin
{
    /**
     * @var string
     */
    private $adminBaseFile;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @var AlertServiceInterface
     */
    private $alertService;

    /**
     * @var string
     */
    private $step = 'overview';

    /**
     * @var DataModelInterface
     */
    private $item;

    /**
     * @var string
     */
    private $tab = 'overview';

    /**
     * GenericAdmin constructor.
     * @param DataModelInterface    $model
     * @param string                $adminBaseFile
     * @param DbInterface           $db
     * @param AlertServiceInterface $alertService
     */
    public function __construct(
        DataModelInterface $model,
        string $adminBaseFile,
        DbInterface $db,
        AlertServiceInterface $alertService
    ) {
        $this->modelClass    = \get_class($model);
        $this->adminBaseFile = $adminBaseFile;
        $this->db            = $db;
        $this->alertService  = $alertService;
    }

    public function handle(): void
    {
        $this->item = new $this->modelClass($this->db);
        $this->step = $_SESSION['step'] ?? 'overview';
        $valid      = Form::validateToken();
        $action     = Request::postVar('action') ?? Request::getVar('action');
        $itemID     = $_SESSION['modelid'] ?? Request::postInt('id', null) ?? Request::getInt('id', null);
        $continue   = $_SESSION['continue'] ?? Request::postInt('save-model-continue') === 1;
        $save       = $valid && ($continue || Request::postInt('save-model') === 1);
        $modelIDs   = Request::postVar('mid', []);
        $cancel     = Request::postInt('go-back') === 1;
        if (\count($modelIDs) === 0 && Request::postInt('id', null) > 0) {
            $modelIDs = [Request::postInt('id')];
        }
        $delete       = $valid && Request::postInt('model-delete') === 1 && \count($modelIDs) > 0;
        $disable      = $valid && Request::postInt('model-disable') === 1 && \count($modelIDs) > 0;
        $enable       = $valid && Request::postInt('model-enable') === 1 && \count($modelIDs) > 0;
        $saveSettings = Request::postVar('a') === 'saveSettings';
        if ($cancel) {
            $this->modelPRG();
        }
        if ($continue === false) {
            unset($_SESSION['modelid']);
        }
        if ($action === 'detail') {
            $this->step = 'detail';
        }
        if ($itemID > 0) {
            $this->item = $this->modelClass::load(['id' => $itemID], $this->db);
        }
        unset($_SESSION['step'], $_SESSION['continue']);

        if ($save === true && $cancel === false) {
            $this->save($itemID, $continue);
        } elseif ($delete === true) {
            $this->update($continue, $modelIDs);
        } elseif ($saveSettings === true) {
            $this->saveSettings();
        } elseif ($disable === true) {
            $this->disable($modelIDs);
        } elseif ($enable === true) {
            $this->enable($modelIDs);
        }
        $this->setMessages();
    }

    /**
     * @param int  $itemID
     * @param bool $continue
     */
    protected function save(int $itemID, bool $continue): void
    {
        if ($this->updateFromPost($this->item, Text::filterXSS($_POST)) === true) {
            $_SESSION['modelid']         = $itemID;
            $_SESSION['modelSuccessMsg'] = \sprintf(\__('successSave'));
            $_SESSION['step']            = $continue ? 'detail' : 'overview';
        } else {
            $_SESSION['modelErrorMsg'] = \sprintf(\__('errorSave'));
        }
        $_SESSION['continue'] = $continue;
        $this->modelPRG();
    }

    /**
     * @param bool  $continue
     * @param array $modelIDs
     */
    protected function update(bool $continue, array $modelIDs): void
    {
        if ($this->deleteFromPost($modelIDs) === true) {
            $_SESSION['modelSuccessMsg'] = \sprintf(\__('successDelete'));
            $_SESSION['step']            = $continue ? 'detail' : 'overview';
        } else {
            $_SESSION['modelErrorMsg'] = \sprintf(\__('errorDelete'));
        }
        $this->modelPRG();
    }

    protected function setMessages(): void
    {
        if (isset($_SESSION['modelSuccessMsg'])) {
            $this->alertService->addAlert(
                Alert::TYPE_SUCCESS,
                $_SESSION['modelSuccessMsg'],
                'successModel'
            );
            unset($_SESSION['modelSuccessMsg']);
        }
        if (isset($_SESSION['modelErrorMsg'])) {
            $this->alertService->addAlert(
                Alert::TYPE_ERROR,
                $_SESSION['modelErrorMsg'],
                'errorModel'
            );
            unset($_SESSION['modelErrorMsg']);
        }
    }

    /**
     * @param array $ids
     */
    protected function enable(array $ids): void
    {
        if ($this->setState($ids, 1)) {
            $_SESSION['modelSuccessMsg'] = \sprintf(\__('successSave'));
        }
    }

    /**
     * @param array $ids
     */
    protected function disable(array $ids): void
    {
        if ($this->setState($ids, 0)) {
            $_SESSION['modelSuccessMsg'] = \sprintf(\__('successSave'));
        }
    }

    /**
     * @param array $ids
     * @param int   $state
     * @return bool
     */
    protected function setState(array $ids, int $state): bool
    {
        return every(map($ids, function ($id) use ($state) {
            try {
                /** @var DataModelInterface $model */
                $model = $this->modelClass::load(['id' => (int)$id], $this->db, DataModelInterface::ON_NOTEXISTS_FAIL);
                $model->setAttribValue('active', $state);

                return $model->save(['active']);
            } catch (Exception $e) {
                return false;
            }
        }), function (bool $e) {
            return $e === true;
        });
    }

    /**
     * @param JTLSmarty $smarty
     * @param string    $template
     * @throws \SmartyException
     */
    public function display(JTLSmarty $smarty, string $template): void
    {
        $models     = $this->modelClass::loadAll($this->db, [], []);
        $pagination = (new Pagination($template))
            ->setItemCount($models->count())
            ->assemble();
        $smarty->assign('step', $this->step)
            ->assign('item', $this->item)
            ->assign('models', $models->forPage($pagination->getPage() + 1, $pagination->getItemsPerPage()))
            ->assign('action', Shop::getAdminURL() . '/' . $this->adminBaseFile)
            ->assign('pagination', $pagination)
            ->assign('settings', \getAdminSectionSettings(\CONF_CONSENTMANAGER))
            ->assign('tab', $this->tab)
            ->display($template);
    }

    /**
     * @param int $code
     */
    public function modelPRG(int $code = 303): void
    {
        \header('Location: ' . Shop::getAdminURL() . '/' . $this->adminBaseFile, true, $code);
        exit;
    }

    /**
     * @param DataModelInterface $model
     * @param array              $post
     * @return bool
     */
    public function updateFromPost(DataModelInterface $model, array $post): bool
    {
        foreach ($model->getAttributes() as $attr) {
            $name         = $attr->getName();
            $type         = $attr->getDataType();
            $isChildModel = \strpos($type, '\\') !== false && \class_exists($type);
            if ($isChildModel) {
                if (isset($post[$name]) && \is_array($post[$name])) {
                    $test = $post[$name];
                    $res  = [];
                    foreach ($test as $key => $values) {
                        foreach ($values as $idx => $value) {
                            $item       = $res[$idx] ?? [];
                            $item[$key] = $value;
                            $res[$idx]  = $item;
                        }
                    }
                    $model->$name = $res;
                }
            } elseif (isset($post[$name])) {
                $model->$name = $post[$name];
            }
        }

        return $model->save();
    }

    /**
     * @param int[] $ids
     * @return bool
     */
    public function deleteFromPost(array $ids): bool
    {
        return every(map($ids, function ($id) {
            try {
                /** @var DataModelInterface $model */
                $model = $this->modelClass::load(['id' => (int)$id], $this->db, DataModelInterface::ON_NOTEXISTS_FAIL);
            } catch (Exception $e) {
                return false;
            }

            return $model->delete();
        }), static function (bool $e) {
            return $e === true;
        });
    }

    /**
     * @return void
     */
    public function saveSettings(): void
    {
        $this->tab = 'settings';
        $this->alertService->addAlert(
            Alert::TYPE_SUCCESS,
            \saveAdminSectionSettings(\CONF_CONSENTMANAGER, $_POST),
            'saveSettings'
        );
    }
}

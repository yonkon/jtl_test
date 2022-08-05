<?php declare(strict_types=1);

namespace JTL\Pagination;

use JTL\Shop;

/**
 * Class FilterSelectField
 * @package JTL\Pagination
 */
class FilterSelectField extends FilterField
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * @var bool
     */
    public $reloadOnChange = false;

    /**
     * FilterSelectField constructor.
     *
     * @param Filter $filter
     * @param string|array $title
     * @param string $column
     * @param int    $defaultOption
     */
    public function __construct($filter, $title, $column, $defaultOption = 0)
    {
        parent::__construct($filter, 'select', $title, $column, $defaultOption);
    }

    /**
     * Add a select option to a filter select field
     *
     * @param string     $title - the label/title for this option
     * @param string|int $value
     * @param int        $testOp
     * @return FilterSelectOption
     */
    public function addSelectOption(string $title, $value, int $testOp = Operation::CUSTOM): FilterSelectOption
    {
        $option          = new FilterSelectOption($title, $value, $testOp);
        $this->options[] = $option;

        return $option;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string|null
     */
    public function getWhereClause(): ?string
    {
        $testOp = $this->options[(int)$this->value]->getTestOp();
        $value  = Shop::Container()->getDB()->escape($this->options[(int)$this->value]->getValue());

        if ($value !== '' || $testOp === Operation::EQUALS || $testOp === Operation::NOT_EQUAL) {
            switch ($testOp) {
                case Operation::CONTAINS:
                    return $this->column . " LIKE '%" . $value . "%'";
                case Operation::BEGINS_WITH:
                    return $this->column . " LIKE '" . $value . "%'";
                case Operation::ENDS_WITH:
                    return $this->column . " LIKE '%" . $value . "'";
                case Operation::EQUALS:
                    return $this->column . " = '" . $value . "'";
                case Operation::LOWER_THAN:
                    return $this->column . " < '" . $value . "'";
                case Operation::GREATER_THAN:
                    return $this->column . " > '" . $value . "'";
                case Operation::LOWER_THAN_EQUAL:
                    return $this->column . " <= '" . $value . "'";
                case Operation::GREATER_THAN_EQUAL:
                    return $this->column . " >= '" . $value . "'";
                case Operation::NOT_EQUAL:
                    return $this->column . " != '" . $value . "'";
            }
        }

        return null;
    }

    /**
     * @return FilterSelectOption|null
     */
    public function getSelectedOption(): ?FilterSelectOption
    {
        return $this->options[(int)$this->value] ?? null;
    }
}

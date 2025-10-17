<?php

namespace app\models\query;

use yii\db\ActiveQuery;

class SoftDeleteQuery extends ActiveQuery
{
    private $_showDeleted = false;

    public function init()
    {
        parent::init();
        $this->andWhere(['deleted_at' => null]);
    }

    /**
     * Include soft-deleted records in the query results.
     */
    public function withDeleted(): self
    {
        $this->where = null; // Remove the default scope
        return $this;
    }
}

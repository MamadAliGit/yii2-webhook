<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel \mamadali\webhook\models\WebhookSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Webhooks');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="webhook-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'url:url',
            'method',
            'action',
            'model_name',
            'model_class',
            'model_id',
			'created_at:datetime',

            [
					'class' => 'yii\grid\ActionColumn',
					'template' => '{view}',
			],
        ],
    ]); ?>
</div>

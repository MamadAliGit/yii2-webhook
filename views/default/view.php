<?php

use mamadali\webhook\models\Webhook;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Webhook */
/* @var $searchModel \mamadali\webhook\models\WebhookLogSearch */
/* @var $dataProvider \yii\data\ActiveDataProvider */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Webhooks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="webhook-view">

    <h1><?= Html::encode($this->title) ?></h1>


    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
			'url:url',
			'method',
			'action',
			[
				'attribute' => 'data',
				'value' => function (Webhook $model) {
					return Html::tag('pre', $model->data);
				},
				'format' => 'html'
			],
			[
				'attribute' => 'headers',
				'value' => function (Webhook $model) {
					return Html::tag('pre', $model->headers);
				},
				'format' => 'html'
			],
        ],
    ]) ?>

	<h1>Logs</h1>

	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
			['class' => 'yii\grid\SerialColumn'],

			//'id',
			'is_ok:boolean',
			'response_status_code',
			[
				'attribute' => 'response_data',
				'value' => function($model) {
					return Html::tag('pre', $model->response_data);
				},
				'format' => 'html',
			],
			[
				'attribute' => 'response_headers',
				'value' => function ($model) {
					return Html::tag('pre', $model->response_headers);
				},
				'format' => 'html',
			],
		],
	]); ?>

</div>

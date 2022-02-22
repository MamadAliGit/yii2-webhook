<?php

namespace mamadali\webhook\controllers;

use mamadali\webhook\models\Webhook;
use mamadali\webhook\models\WebhookLogSearch;
use mamadali\webhook\models\WebhookSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller
{

	/**
	 * Lists all Webhook models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel = new WebhookSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Displays a single Webhook model.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$model = $this->findModel($id);
		$searchModel = new WebhookLogSearch(['webhook_id' => $model->id]);
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		return $this->render('view', [
			'model' => $model,
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Finds the Webhook model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 *
	 * @return Webhook the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = Webhook::findOne($id)) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
	}

}
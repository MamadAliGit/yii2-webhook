<?php

namespace mamadali\webhook\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * WebhookSearch represents the model behind the search form of `common\models\Webhook`.
 */
class WebhookSearch extends Webhook
{
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id', 'model_id'], 'integer'],
			[['url', 'method', 'action', 'model_name', 'model_class', 'data', 'headers'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function scenarios()
	{
		// bypass scenarios() implementation in the parent class
		return Model::scenarios();
	}

	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ActiveDataProvider
	 */
	public function search($params)
	{
		$query = Webhook::find();

		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'created_at' => SORT_DESC,
				],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			'id' => $this->id,
			'model_id' => $this->model_id,
		]);

		$query->andFilterWhere(['like', 'url', $this->url])
			->andFilterWhere(['like', 'method', $this->method])
			->andFilterWhere(['like', 'action', $this->action])
			->andFilterWhere(['like', 'model_name', $this->model_name])
			->andFilterWhere(['like', 'model_class', $this->model_class])
			->andFilterWhere(['like', 'data', $this->data])
			->andFilterWhere(['like', 'headers', $this->headers]);

		return $dataProvider;
	}
}

<?php

namespace frontend\controllers;

use Yii;
use frontend\models\Po;
use frontend\models\PoSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use frontend\models\PoItem;
use common\models\Model;
/**
 * PoController implements the CRUD actions for Po model.
 */
class PoController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Po models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Po model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Po model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Po();
        $modelPoItem = [new PoItem];

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

          // get Payment data from POST
       $modelPoItem = Model::createMultiple(PoItem::classname());
       Model::loadMultiple($modelPoItem, Yii::$app->request->post());


       // https://github.com/wbraganca/yii2-dynamicform
       $valid = $model->validate();
       $valid = Model::validateMultiple($modelPoItem) && $valid;

       // save deposit data
       if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if ($flag = $model->save(false)) {
                              foreach ($modelPoItem as $modelPoItem) {
                                  $modelPoItem->po_id = $model->id;
                                  if (! ($flag = $modelPoItem->save(false))) {
                                      $transaction->rollBack();
                                      break;
                                  }
                              }
                          }
                          if ($flag) {
                              $transaction->commit();
                              return $this->redirect(['view', 'id' => $model->id]);
                          }
                      } catch (Exception $e) {
                          $transaction->rollBack();
                }
          }

        } else {

            return $this->render('create', [
                'model' => $model,
                'modelPoItem' => (empty($modelPoItem)) ? [new PoItem] : $modelPoItem//verifica si esta vacio el PoItem de lo contrario envía lo que ha leído
            ]);
        }
    }

    /**
     * Updates an existing Po model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        //recupera todos los Po almacenados
        $model = $this->findModel($id);

        $modelPoItem =  PoItem::find()->all();

        //recupera todos los PoItem almacenados
        //$modelPoItem = PoItem::find()->select('id')->where(['id'=>$id])->asArray()->all();
        //$modelPoItem = ArrayHelper::getColumn($modelPoItem,'id');
        //$modelPoItem = PoItem::findAll(['id'=>$modelPoItem]);
        //$modelPoItem = (empty($modelPoItem)) ? [new PoItem] : $modelPoItem;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $oldIDs = ArrayHelper::map($modelPoItem, 'id', 'id');
            $modelPoItem = Model::createMultiple(PoItem::classname(),$modelPoItem);
            Model::loadMultiple($modelPoItem, Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelPoItem, 'id', 'id')));

            //validaciones
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelPoItem) && $valid;

            if ($valid) {
              $transaction = \Yii::$app->db->beginTransaction();
              try {
                  if ($flag = $model->save(false)) {
                      if (! empty($deletedIDs)) {
                          PoItem::deleteAll(['id' => $deletedIDs]);
                      }
                      foreach ($modelPoItem as $modelPoItem) {
                          $modelPoItem->po_id = $model->id;
                          if (! ($flag = $modelPoItem->save(false))) {
                              $transaction->rollBack();
                              break;
                          }
                      }
                  }
                  if ($flag) {
                      $transaction->commit();
                      return $this->redirect(['view', 'id' => $modelCustomer->id]);
                  }
              } catch (Exception $e) {
                  $transaction->rollBack();
              }
          }



            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelPoItem' => (empty($modelPoItem)) ? [new PoItem] : $modelPoItem,
            ]);
        }
    }

    /**
     * Deletes an existing Po model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Po model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Po the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Po::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

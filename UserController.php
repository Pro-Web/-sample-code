<?php

namespace common\modules\useradminka\controllers;

use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use common\models\User;
use common\modules\admin\models\Users;
use common\modules\useradminka\models\BaliWeek;
use common\modules\useradminka\models\BaliMonth;
use common\modules\admin\models\Testi;
use common\modules\admin\models\ModelsInfo;
use common\modules\admin\models\Notice;
use common\modules\admin\models\KonkursniRoboti;
use common\modules\admin\models\Podarki;
use frontend\models\SignupForm;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                //'only' => ['logout', 'signup', 'about'],
                'rules' => [
                    [
                   //'actions' => ['about'],
                   'allow' => true,
                   'roles' => ['@'],
                   'matchCallback' => function ($rule, $action) {
                       return User::isUserProvizor(Yii::$app->user->identity->username);
                   }
                   ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionMessages()
    {
        return $this->render('index');
    }

    public function actionProfile()
    {

        $model = new SignupForm;
        $profile = User::find()->where(['username' => Yii::$app->user->identity->username])->one();
        $baliMonth = BaliMonth::find()->
										where(['juzer_id' => Yii::$app->user->identity->id ])->
										all();

		$notify = Notice::find()->
		where(['juzer_id' => Yii::$app->user->identity->id ])->
		orderBy('data DESC')->
		all();
		
        if($model->load(Yii::$app->request->post()) ){ //При Cмене пароля

            $data_post = Yii::$app->request->post();
            $pass_info = $profile->passUpload( $data_post );

            if($pass_info != 'success'){

                $pass_success = false;

            } else {

                $pass_success = true;

            }
            
            return $this->render('profile', [
                'juzer' => $profile,
                'baliMonth' => $baliMonth,
                'model' => $model,
                'pass_info' => $pass_info,
                'pass_success' => $pass_success
            ]);
            

        } else {

            return $this->render('profile', [
                'juzer' => $profile,
                'baliMonth' => $baliMonth,
                'model' => $model,
                'pass_info' => '',
				'notify' => $notify,
                'pass_success' => false
            ]);

        }
    }

    public function actionOsvitnyaProgramma()
    {
        
        /*$baliWeek = new BaliWeek; //Экземпляр класса таблицы баллов по неделям
        $baliWeek = BaliMonth::find()->where(['juzer_id' => Yii::$app->user->identity->id ])->all();*/

        $model_info = ModelsInfo::find()->where(['atribut' => 'vstup'])
                                   ->orWhere(['atribut' => 'umovi'])
                                   ->orWhere(['atribut' => 'prava'])
                                   ->orderBy(['id'=>SORT_DESC])
                                   ->all();

        if(Yii::$app->getRequest()->getQueryParam('slugosvit') == "faq"){ //Вопросы

            $faq_osvit = ModelsInfo::find()->where(['models' => 'OsvitProgram', 'atribut' => 'faq'])->one();

            return $this->render('faq_osvit', [
                    'faq_osvit' => json_decode($faq_osvit->content)
                ]);

        } else {

            $baliWeek = BaliWeek::find()->select(['test', 'test_glaz', 'test_count', 'present', 'staty', 'instruction', 'date_start', 'date_end' ])->where(['juzer_id' => Yii::$app->user->identity->id ])->all();
            
            $modelWeek = new BaliWeek;
            $week_test_count = isset($modelWeek->infoBalliWeek()->test_count) ? $modelWeek->infoBalliWeek()->test_count : 0;

            
            //Собираем баллы юзера исходя из Всех баллов
            $params = [':juzer_id' => Yii::$app->user->identity->id ];

            $summ_ball = Yii::$app->db_Balli->createCommand("SELECT (SUM(summ_ball) - SUM(potracheno)) FROM bali_month WHERE juzer_id=:juzer_id ")
                            ->bindValues($params)
                            ->queryScalar();

            //Выбираем подарки, доступные юзеру
            $params_podarki = [':balli' => $summ_ball];

            $prizes = Podarki::find()->where('balli <=:balli', [':balli' => $summ_ball])->andWhere(['showed' => '1'])->orderBy(['balli' => SORT_DESC])->all();
  
            return $this->render('osvitviktor', [
                'prizes' => $prizes,
                'baliWeek' => $baliWeek,
                'model_info' => $model_info,
                'week_test_count' => $week_test_count
            ]);
        }

    }

    public function actionPersoninfoupload()
    {
        $model = new User();
        $data_post = Yii::$app->request->post();
        
        if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'profile_upload' ) {

            $user = $model->infoUpload( $data_post['data_form'] );
            echo $user;
        }
    }


    public function actionTovarBalli()
    {

        $data_post = Yii::$app->request->post();
        $data_post['data_id'] = (int) $data_post['data_id'];
        $baliWeek = new BaliWeek; //Экземпляр класса таблицы баллов по неделям

        if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'set_instruction' ) {

            $baliWeek->setInstrukzija(Yii::$app->user->identity->id, $data_post['data_id'] ); //echo 'Инструкция';

        } else if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'set_presentations' ){

            $baliWeek->setPresentation(Yii::$app->user->identity->id, $data_post['data_id'] ); //echo 'Презентация';
        }
    }


    public function actionGetTest()
    {

        $data_post = Yii::$app->request->post();
        $modelTest = new Testi; //Экземпляр класса Тестов по неделям

        if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'get_test' ) {
            
            if($modelTest->getJuzerTest() === 0){

                return $this->redirect(['user/osvitnya-programma']);

            } else {
                echo $modelTest->getJuzerTest(); //echo 'Тест';
            }
        }
    }


    public function actionSprobaTest()
    {

        $data_post = Yii::$app->request->post();
        $model = new BaliWeek; //Экземпляр класса Тестов по неделям

        if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'set_sprobatest' ) {
            
            $week_test_count = isset($model->infoBalliWeek()->test_count) ? $model->infoBalliWeek()->test_count : 0;

            if($week_test_count < 3){

                echo $model->updateSprobaTest(Yii::$app->user->identity->id );

            } else {
                return $this->redirect(['user/osvitnya-programma']);
            }
           
        }
    }


    public function actionResultTest() //Результат за прохождение недельного теста
    {

        $data_post = Yii::$app->request->post();
        $data_post['cur_data'] = (int) $data_post['cur_data'];
        $model = new BaliWeek; //Экземпляр класса Тестов по неделям

        if( Yii::$app->user->identity->username == $data_post['curent_juzer'] && Yii::$app->request->isAjax && $data_post['action_name'] == 'set_testresult' ) {

        	$week_test_count = isset($model->infoBalliWeek()->test_count) ? $model->infoBalliWeek()->test_count : 0;

            if($week_test_count <= 3){

                $model->updateTestResult(Yii::$app->user->identity->id, $data_post['cur_data'] );

            } else {
                return $this->redirect(['user/osvitnya-programma']);
            }
           
        }
    }

    /**
     *
     * Avatar upload.
     *
     */
    public function actionUploadphoto()
    {
        
        $data_post = Yii::$app->request->post();

        $model = new Users();

        if (Yii::$app->request->isAjax && $data_post['action_name'] == 'get_upload' ) {

            $juzer_photo = $model->userPhotoUpload($_FILES, $data_post['action_name'] );
            echo $juzer_photo;
        
         } else {

            $remove_photo = $model->userPhotoUpload($data_post['photoLink'], $data_post['action_name'] );
            echo $remove_photo;

        }
    }

    
    /**
     * Displays a single Myworks page.
     * @param integer $id
     * @return mixed
     */
    public function actionMyworks()
    {

        $year = Yii::$app->getRequest()->getQueryParam('year');

        if(!empty($year))
        {

        	if($year != 'undefined' )
            {
                $juzers_works = KonkursniRoboti::find()->where('YEAR(`data_time`) = '.$year.' ')
                                               ->andWhere(['user_id' => Yii::$app->user->identity->id ]);
            } else {
                throw new \yii\web\NotFoundHttpException('Page not found.');
            }
        }
		else
        {
            $juzers_works = KonkursniRoboti::find()->where('YEAR(`data_time`) = YEAR(NOW())')
                                       ->andWhere(['user_id' => Yii::$app->user->identity->id ]);   
        }

        $pagination = new Pagination([
	            'defaultPageSize' => 20,
	            'totalCount' => $juzers_works->count()
	        ]);

	    $new_works = $juzers_works->orderBy(['data_time' => SORT_DESC])
	            ->offset($pagination->offset)
	            ->limit($pagination->limit)
	            ->all();

        return $this->render('myworks', [
        	'juzers_works' => $new_works,
        	'pagination' => $pagination
        ]);
    }


    /**
     * Displays a single User model.
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
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing User model.
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
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

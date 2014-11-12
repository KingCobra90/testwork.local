<?php

/**
 * This is the model class for table "message".
 *
 * The followings are the available columns in table 'message':
 * @property string $id
 * @property string $user_id
 * @property string $message
 * @property string $date_updated
 * @property string $date_created
 *
 * The followings are the available model relations:
 * @property User $user
 */
class Message extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'message';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('message', 'required'),
			array('message', 'length', 'max'=>200),
			array('message', 'filter', 'filter' => 'strip_tags'),
			array('message', 'filter', 'filter' => 'trim'),
			
			array('date_created', 'default', 'value'=>new CDbExpression('NOW()'), 'on'=>'insert'),
			array('date_updated', 'default', 'value'=>new CDbExpression('NOW()'), 'setOnEmpty'=>false, 'on'=>'update'),
			
			array('user_id', 'default', 'value'=>Yii::app()->user->id, 'on'=>'insert'),
			
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, message, date_updated, date_created', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'message' => 'Message',
			'date_updated' => 'Date Updated',
			'date_created' => 'Date Created',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('date_updated',$this->date_updated,true);
		$criteria->compare('date_created',$this->date_created,true);
		
		$criteria->condition = 'user_id=:user_id';
		$criteria->params = array(':user_id'=>Yii::app()->user->id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	public function afterSave() {
		//send email
		$plainTextContent = $this->user->username . ' оставил сообщение.';
	 	$SM = Yii::app()->swiftMailer;
		$mailHost = Yii::app()->params['sendmail']['host'];
		$mailPort = Yii::app()->params['sendmail']['port'];
	 
		$Transport = $SM->smtpTransport($mailHost, $mailPort);
	 
		$Mailer = $SM->mailer($Transport);
	 
		$Message = $SM
			->newMessage('Message created')
			->setFrom(array(Yii::app()->params['adminEmail'] => Yii::app()->name . ' Admin'))
			->setTo(array(Yii::app()->params['adminEmail']))
			->setBody($plainTextContent);

		$result = $Mailer->send($Message);
	
		return parent::afterSave();
	}
	
	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Message the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}

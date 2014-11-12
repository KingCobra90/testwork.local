<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property string $id
 * @property string $username
 * @property string $email
 * @property string $pass
 * @property string $date_registered
 *
 * The followings are the available model relations:
 * @property Message[] $messages
 */
class User extends CActiveRecord
{
	public $passCompare;
	public $rememberMe;
	
	private $_identity;
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('email, pass', 'required'),
			array('username', 'required', 'on' => 'insert'),
			array('pass', 'authenticate', 'on' => 'login'),
			
			array('rememberMe', 'boolean'),
			
			array('username', 'length', 'max'=>20),
			array('username', 'unique'),
			
			array('email', 'length', 'max'=>60),
			array('email', 'email'),
			array('email', 'unique', 'on' => 'insert'),
			
			array('pass', 'length', 'min'=>6,'max'=>20),
			array('pass', 'compare', 'compareAttribute'=>'passCompare', 'on' => 'insert'),
			
			array('passCompare', 'required', 'on' => 'insert'),
			array('passCompare', 'length', 'min'=>6,'max'=>20),
			
			array('date_registered', 'default', 'value'=>new CDbExpression('NOW()'), 'on'=>'insert'),

			array('username, email, pass', 'filter', 'filter' => 'trim'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, username, email, date_registered', 'safe', 'on' => 'search'),
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
			'messages' => array(self::HAS_MANY, 'Message', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => 'Username',
			'email' => 'Email',
			'pass' => 'Pass',
			'date_registered' => 'Date Registered',
			'rememberMe'=>'Remember me next time',
			'comparePass' => 'Password Confirmation',
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
		$criteria->compare('username',$this->username,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('pass',$this->pass,true);
		$criteria->compare('date_registered',$this->date_registered,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	public function beforeSave(){
		if ($this->isNewRecord) {
			$this->pass = hash_hmac('sha256', $this->pass, Yii::app()->params['encryptionKey']);
		}
		return parent::beforeSave();
	}

	public function afterSave() {
		//send email
		$plainTextContent = $this->username . ' зарегистрировался.';
	 	$SM = Yii::app()->swiftMailer;
		$mailHost = Yii::app()->params['sendmail']['host'];
		$mailPort = Yii::app()->params['sendmail']['port'];
	 
		$Transport = $SM->smtpTransport($mailHost, $mailPort);
	 
		$Mailer = $SM->mailer($Transport);
	 
		$Message = $SM
			->newMessage('User registered')
			->setFrom(array(Yii::app()->params['adminEmail'] => Yii::app()->name . ' Admin'))
			->setTo(array(Yii::app()->params['adminEmail']))
			->setBody($plainTextContent);
	 
		$result = $Mailer->send($Message);
	
		return parent::afterSave();
	}
	 /**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute,$params)
	{
		if(!$this->hasErrors())
		{
			$this->_identity=new UserIdentity($this->email,$this->pass);
			if(!$this->_identity->authenticate())
				$this->addError('pass','Incorrect email or password.');
		}
	}

	/**
	 * Logs in the user using the given email and password in the model.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if($this->_identity===null)
		{
			$this->_identity=new UserIdentity($this->email,$this->pass);
			$this->_identity->authenticate();
		}
		if($this->_identity->errorCode===UserIdentity::ERROR_NONE)
		{
			$duration=$this->rememberMe ? 3600*24*30 : 0; // 30 days
			Yii::app()->user->login($this->_identity,$duration);
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}

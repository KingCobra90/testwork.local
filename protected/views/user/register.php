<?php
/* @var $this UserController */
/* @var $model User */

$this->breadcrumbs=array(
	'Register',
);

?>

<h1>Register User</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>
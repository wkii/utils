<?php

Yii::import('gii.generators.crud.CrudGenerator');

class BootstrapGenerator extends CrudGenerator
{
	public $codeModel = 'ext.gii.bootstrap.BootstrapCode';
}
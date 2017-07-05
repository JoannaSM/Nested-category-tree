<?php
/* @var $this KategorieController */
/* @var $model Category */
/* @var $form CActiveForm */
if(!empty($model->id)){
    $arrParent = Category::model()->parentItem($model->id)->findAll();
    $parent = $arrParent[0]->id;
} else $parent = 0;
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'category-form',
	'enableAjaxValidation'=>false,
)); ?>
    <table>
        <tr>
            <td colspan="2">
                Pola z <span class="required">*</span> są wymagane.<br/>
                <?php echo $form->errorSummary($model); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo $form->labelEx($model,'name'); ?></td>
            <td>
                <?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>255)); ?>
                <?php echo $form->error($model,'name'); ?>                                
            </td>
        </tr>
        <tr>
            <td><?php echo $form->labelEx($model,'parent_id'); ?></td>
            <td><?php echo $form->dropDownList($model, 'parent_id', CHtml::listData($model->getTree(),'id','title'), 
                    array('options' => array($parent=>array('selected'=>true)))
                    )?>
                <?php echo $form->hiddenField($model,'parent_old',array('value'=>$parent));?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
               	<div class="row buttons">
        		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
                </div>
            </td>
        </tr>
    </table>

	


<?php $this->endWidget(); ?>

</div><!-- form -->
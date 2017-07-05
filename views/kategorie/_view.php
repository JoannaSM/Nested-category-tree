<?php
/* @var $this KategorieController */
/* @var $data Category */
$ile = Items::model()->count('cat_id=:cat_id',array(':cat_id'=>$data->id));
?>

<p class="view">
    <?php echo CHtml::link(CHtml::encode($data->title), array('view', 'id'=>$data->id)); if(!empty($ile))echo "&nbsp(ilość pozycji: $ile)"; ?>
</p>
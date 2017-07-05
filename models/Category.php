<?php

/**
 * This is the model class for table "category".
 *
 * The followings are the available columns in table 'category':
 * @property integer $id
 * @property string $name
 * @property integer $lft
 * @property integer $rgt
 */
class Category extends CActiveRecord
{
    public $title;
    public $depth;
    public $parent_id;
    public $parent_old;
    public $maxrgt ;
    
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'category';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('name, lft, rgt', 'required'),
			array('lft, rgt', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>255),
                        array('name', 'unique', 'className' => __CLASS__, 'on'=>'create,update'),
            
                        array('parent_id,parent_old', 'safe', 'on'=> 'create,update'),
			
			array('id, name, lft, rgt', 'safe', 'on'=>'search'),
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
                    'items' => array(self::HAS_MANY, 'Items', 'cat_id'),
                    'ile' => array(self::HAS_MANY, 'Items', 'cat_id', 
                    'alias'=>'nm',
                    'select'=>array('COUNT(nm.id) AS numb'),'group'=>'nm.cat_id' ),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'lft' => 'Lft',
			'rgt' => 'Rgt',
                        'parent_id' => 'Kategoria nadrzędna',
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
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('lft',$this->lft);
		$criteria->compare('rgt',$this->rgt);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
    
    /**
     * Returns a formatted list of categories as CActiveRecord arranged in a tree form. 
     * @return Category[]
     */
    public function getTree(){
        $criteria = new CDbCriteria;
        $criteria->select = "t.id, concat( repeat('--', COUNT(parent.id) - 1),t.name) AS title, t.type";
        $criteria->join = ' JOIN `category` AS `parent`';
        $criteria->group = 't.id';
        $criteria->order  = 't.lft';
        $criteria->addCondition("t.lft BETWEEN parent.lft AND parent.rgt");
        
        $resultSet  = $this->findAll($criteria);
        return $resultSet;
    }
    
    
    /**
     * Returns a formatted list of categories as CActiveRecord arranged in a tree form. 
     * @return string[]
     */
    public function getTreeAsArray(){
        $catTree = $this->getTree();
        $arrTree = array();
        if(!empty($catTree)) foreach($catTree as $objCat){
           $arrTree[$objCat->id] = $objCat->title; 
        }
        return $arrTree;
    }
    
    /**
     * Return root data. Query preparation
     * @param type $catID
     * @return $this
     */
    public function root($catID){
        $catData = $this->findByPk($catID);
        $this->getDbCriteria()->mergeWith(array(
            'condition' => 'lft <= :lft AND rgt >= :rgt',
            'params'    => array(':lft' => $catData->lft, ':rgt'=>$catData->rgt),
            'order'=>'lft ASC',
            'limit'=>1,    
        ));
        return $this;
       
    }
    
    /**
     * Returns the parent record
     * @param type $catID item ID
     * @return CActiveRecord
     */
    public function parentItem($catID){
        $catData = $this->findByPk($catID);
        $this->getDbCriteria()->mergeWith(array(
            'condition' => 'lft < :lft AND rgt > :rgt',
            'params'    => array(':lft' => $catData->lft, ':rgt'=>$catData->rgt),
            'order'=>'lft DESC',
            'limit'=>1,    
        ));
	return $this;
       
    }
    
    /**
     * Read branch. Query preparation
     * @param type $catID ID cild item
     * @param type $rev sort order
     * @return $this
     */
    public function branch($catID, $rev = false){
        $catData = $this->findByPk($catID);
        $this->getDbCriteria()->mergeWith(array(
            'select' => "t.id, concat( repeat('--', COUNT(parent.id) - 1),t.name) AS title",
            'condition' => 't.lft BETWEEN parent.lft AND parent.rgt AND t.lft <= :lft AND t.rgt >= :rgt',
            'join' => ' JOIN `category` AS `parent`',
            'group' => 't.id',
            'order'  => 't.lft',
            'params'    => array(':lft' => $catData->lft, ':rgt'=>$catData->rgt),
        ));
        if($rev)
            $this->getDbCriteria()->mergeWith(array(
                'order'  => 't.lft DESC',
            ));
	return $this;        
    }
    
    /**
     * Returns child records with the depth information
     * @param type $parentCat
     * @return Category[]
     */
    public function getChildren($parentCat){
        //pobiera categorie pierwszego poziomu względem $catID
        $criteria=new CDbCriteria; 
        $criteria->select = "t.id, count(parent.id)-1 AS depth, t.type, t.name, t.lft, t.rgt";//'t.*, tu.* ';
        $criteria->join = ' JOIN `category` AS `parent`';
        $criteria->group = 't.id';
        $criteria->order  = 't.lft';
        $criteria->addCondition("t.lft BETWEEN parent.lft AND parent.rgt");
        $criteria->addCondition("parent.lft BETWEEN $parentCat->lft AND $parentCat->rgt");
        $criteria->having = 'depth = 1';
        $arrCat = Category::model()->findAll($criteria);
        
        return $arrCat;
        
    }
    
    /**
     * Save item. Add or modyfi item. 
     * @return boolean
     */
    public function save(){
        //Opening transaction
        $transaction=$this->dbConnection->beginTransaction();
        try
        {
            
            if(empty($this->parent_id)){ //add item on the end (new root)
                $criteria=new CDbCriteria;
                $criteria->select='MAX(t.rgt) AS maxrgt';
                $row = Category::model()->find($criteria);
                //$somevariable = $row['maxColumn'];  
                CVarDumper::dump($this->atributes,100,true);
                $this->lft = $row['maxrgt']+1;
                $this->rgt = $row['maxrgt']+2;
            } elseif(empty($this->parent_old)) {//new record in the branch
                //shift rgt
                $parent = Category::model()->findByPk($this->parent_id);
                Category::model()->updateAll(array('lft'=>new CDbExpression('lft+2')), "lft >= $parent->rgt");
                Category::model()->updateAll(array('rgt'=>new CDbExpression('rgt+2')), "rgt >= $parent->rgt");
                
                //add record
                $this->lft = $parent->rgt;
                $this->rgt = $parent->rgt+1;
            } elseif($this->parent_old != $this->parent_id) {//shift within the tree
                //reset the indicators
                $lft = $this->lft;
                $rgt = $this->rgt;
                $this->lft = 0;
                $this->rgt = 0;
                parent::save();
                //shift rgt and rgt, reduce by 2
                Category::model()->updateAll(array('lft'=>new CDbExpression('lft-2')), "lft > $rgt");
                Category::model()->updateAll(array('rgt'=>new CDbExpression('rgt-2')), "rgt > $rgt");
                
                $parent = Category::model()->findByPk($this->parent_id);
                Category::model()->updateAll(array('lft'=>new CDbExpression('lft+2')), "lft >= $parent->rgt");
                Category::model()->updateAll(array('rgt'=>new CDbExpression('rgt+2')), "rgt >= $parent->rgt");
                
                $this->lft = $parent->rgt;
                $this->rgt = $parent->rgt+1;
                
            }
            parent::save();
            
            //close transaction
            $transaction->commit();
        }
        catch(Exception $e)
        {
            $transaction->rollback();
        }
        return true;
    }
}

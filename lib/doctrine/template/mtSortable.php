<?php

/**
 * mtSortable
 *
 * Makes records sortable by adding an order field..
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
class mtSortable extends Doctrine_Template
{
  /**
   * @var array
   */
  protected $_options = array(
    // column definition
    'name'       => 'order',
    'alias'      => null,
    'type'       => 'integer',
    'length'     => 1,
    'options'    => array(
      'unsigned' => true,
    ),

    // index definition
    'indexName' => null,
  );

  /**
   * @throws InvalidArgumentException
   */
  public function setTableDefinition()
  {
    $name = $this->_options['name'];

    // add an alias
    if ($this->_options['alias'])
    {
      $name .= ' as ' . $this->_options['alias'];
    }

    $this->hasColumn($name, $this->_options['type'], $this->_options['length'], $this->_options['options']);

    // set index name if it's empty
    if (null === $this->_options['indexName'])
    {
      $this->_options['indexName'] = $this->getTable()->getTableName().'_sortable';
    }

    $this->index($this->_options['indexName'], array('fields' => array($this->_options['name'])));
  }

  /**
   * Gets slug value.
   *
   * @return mixed
   */
  public function getOrderValue()
  {
    return $this->getInvoker()->get($this->getTable()->getFieldName($this->_options['name']));
  }

  /**
   * Sets slug value.
   *
   * @param string $value slug value
   *
   * @return Doctrine_Record
   */
  public function setOrderValue($value)
  {
    return $this->getInvoker()->set($this->getTable()->getFieldName($this->_options['name']), $value);
  }

  /**
   * Gets the the name of the slug field.
   *
   * @return string
   */
  public function getOrderFieldName()
  {
    return $this->getInvoker()->getTable()->getFieldName($this->_options['name']);
  }

  /**
   * Updates the order to match the order of the given array of ids.
   *
   * @param array $order array of ids
   *
   * @return int
   */
  public function updateOrderTableProxy(array $order)
  {
    $con = Doctrine_Manager::getInstance()->getCurrentConnection();

    $placeHolders = implode(',', array_fill(0, count($order), '?'));

    $tableName = $this->getInvoker()->getTable()->getOption('tableName');
    $idColumn = $this->getInvoker()->getTable()->getIdentifierColumnNames();
    $idColumn = array_shift($idColumn);

    // prepared statement can't be used bc. ORDER BY is changing
    return $con
      ->exec("SET @i = 0; UPDATE `$tableName` SET `order` =  (@i := @i + 1) ORDER BY FIELD($idColumn, $placeHolders)", $order)
    ;
  }

  /**
   * Gets an ordered query.
   *
   * @return Doctrine_Query
   */
  public function queryOrderedTableProxy()
  {
    /** @var $record Doctrine_Record|mtSortable */
    $record = $this->getInvoker();

    return $record->getTable()->createQuery()->orderBy($record->getOrderFieldName());
  }
}

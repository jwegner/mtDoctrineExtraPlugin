<?php

/**
 * mtCountCache
 *
 * Caches the number of related records in the inverse side.
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
class mtCountCache extends Doctrine_Template
{
  /**
   * @var array
   */
  protected $_options = array(
    'relations' => array(),
    'listener'  => 'mtCountCacheListener',
  );

  /**
   * {@inheritdoc}
   */
  public function setTableDefinition()
  {
    foreach ($this->_options['relations'] as $relation => $options)
    {
      $relatedTable = $this->_table->getRelation($relation)->getTable();
      $this->_options['relations'][$relation]['className'] = $relatedTable->getOption('name');

      if (isset($this->_options['relations'][$relation]['foreignAlias']))
      {
        $foreignAlias = $this->_options['relations'][$relation]['foreignAlias'];
      }
      else
      {
        // find the alias in the related table
        $foreignAlias = null;
        foreach ($relatedTable->getRelations() as $foreignAlias => $definition)
        {
          if ($definition['class'] == $this->_table->getOption('name'))
          {
            break;
          }
        }

        if ($foreignAlias)
        {
          $this->_options['relations'][$relation]['foreignAlias'] = $foreignAlias;
        }
      }

      // Build column name if one is not given
      if (!isset($this->_options['relations'][$relation]['columnName']))
      {
        $this->_options['relations'][$relation]['columnName'] = 'number_of_' . Doctrine_Inflector::tableize($foreignAlias ?: $relation);
      }

      // Add the column to the related model
      $columnName = $this->_options['relations'][$relation]['columnName'];
      $relatedTable->setColumn($columnName, 'integer', null, array('default' => 0));
    }

    // create the listener
    $class = $this->_options['listener'];
    if (!class_exists($class))
    {
      throw new InvalidArgumentException('Class does not exists "' . $class . '"');
    }

    $class = new $class($this->_options);
    if (!$class instanceof Doctrine_Record_Listener_Interface)
    {
      throw new InvalidArgumentException(sprintf('"%s" class must implement "Doctrine_Record_Listener_Interface"', get_class($class)));
    }

    $this->addListener($class, 'mt-count-cache');
  }

  /**
   * {@inheritdoc}
   */
  public function setUp()
  {
  }

  /**
   * Disables the count cache listener.
   */
  public function disableCountCacheListener()
  {
    $this->getListener()->get('mt-count-cache')->setOption('disabled', true);
  }

  /**
   * Enables the count cache listener.
   */
  public function enableCountCacheListener()
  {
    $this->getListener()->get('mt-count-cache')->setOption('disabled', false);
  }
}

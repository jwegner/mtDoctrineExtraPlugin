<?php

/**
 * mtCountCacheListener
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
class mtCountCacheListener extends Doctrine_Record_Listener
{
  /**
   * @param array $options
   */
  public function __construct(array $options)
  {
    $this->setOption($options);
  }

  /**
   * Increases count if a record is inserted.
   *
   * @param Doctrine_Event $event
   */
  public function postInsert(Doctrine_Event $event)
  {
    $invoker = $event->getInvoker();
    foreach ($this->_options['relations'] as $options)
    {
      $table = Doctrine::getTable($options['className']);
      $relation = $table->getRelation($options['foreignAlias']);

      $table
        ->createQuery()
        ->update()
        ->set($options['columnName'], $options['columnName'].' + 1')
        ->where($relation['local'].' = ?', $invoker->$relation['foreign'])
        ->execute();
    }
  }

  /**
   * Decreases count if a record is removed.
   *
   * @param Doctrine_Event $event
   */
  public function postDelete(Doctrine_Event $event)
  {
    $invoker = $event->getInvoker();
    foreach ($this->_options['relations'] as $options)
    {
      $table = Doctrine::getTable($options['className']);
      $relation = $table->getRelation($options['foreignAlias']);

      $table
        ->createQuery()
        ->update()
        ->set($options['columnName'], $options['columnName'].' - 1')
        ->where($relation['local'].' = ?', $invoker->$relation['foreign'])
        ->execute();
    }
  }

  /**
   * Decreases count if a record is removed with a DQL query.
   *
   * @param Doctrine_Event $event
   */
  public function preDqlDelete(Doctrine_Event $event)
  {
    foreach ($this->_options['relations'] as $options)
    {
      $table = Doctrine::getTable($options['className']);
      $relation = $table->getRelation($options['foreignAlias']);

      $q = clone $event->getQuery();
      $q->select($relation['foreign']);
      $ids = $q->execute(array(), Doctrine::HYDRATE_NONE);

      foreach ($ids as $id)
      {
        $id = $id[0];

        $table
          ->createQuery()
          ->update()
          ->set($options['columnName'], $options['columnName'].' - 1')
          ->where($relation['local'].' = ?', $id)
          ->execute();
      }
    }
  }
}

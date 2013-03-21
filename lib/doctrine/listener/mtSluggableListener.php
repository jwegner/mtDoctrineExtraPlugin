<?php

/**
 * mtSluggableListener
 *
 * Easily create a slug for each record based on a specified set of fields.
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
class mtSluggableListener extends Doctrine_Record_Listener
{
  /**
   * @param array $options
   */
  public function __construct(array $options)
  {
    $this->setOption($options);
  }

  /**
   * Set the slug value automatically when a record is inserted.
   *
   * @param Doctrine_Event $event
   */
  public function preInsert(Doctrine_Event $event)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $event->getInvoker();

    $slug = $record->getSlugValue();

    if (false === $slug)
    {
      // set the column's default value
      $slug = $record->getSlugDefaultValue();
    }
    else
    {
      // generate a slug
      $slug = $record->generateSlug($slug);
    }

    $record->setSlugValue($slug);
  }

  /**
   * Set the slug value automatically when a record is
   * updated if the options are configured to allow it.
   *
   * @param Doctrine_Event $event
   */
  public function preUpdate(Doctrine_Event $event)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $event->getInvoker();

    // don't change the slug if it can't be updated
    if ($this->_options['canUpdate'])
    {
      $slug = $record->getSlugValue();

      if (false === $slug)
      {
        // set the column's default
        $record->setSlugValue($record->getSlugDefaultValue());
      }
      elseif ($record->isSlugModified())
      {
        // the slug was changed by the user
        $record->updateSlug($slug);
      }
      elseif ($record->isSluggableFieldModified() || $record->isSlugShouldBeRegenerated())
      {
        // the slug should be regenerated bc. some fields have changed
        $record->updateSlug();
      }
    }
    elseif ($record->isSlugModified())
    {
      // the slug has been changed but it's not allowed
      // so set back the old value
      $oldValues = $record->getModified(true);

      $record->setSlugValue($oldValues[$record->getSlugFieldName()]);
    }
  }
}

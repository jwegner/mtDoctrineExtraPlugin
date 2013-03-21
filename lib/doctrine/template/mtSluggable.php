<?php

/**
 * mtSluggable
 *
 * Easily create a slug for each record based on a specified set of fields.
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
class mtSluggable extends Doctrine_Template
{
  /**
   * @var array
   */
  protected $_options = array(
    // column definition
    'name'      => 'slug',
    'alias'     => null,
    'type'      => 'string',
    'length'    => 255,
    'options'   => array(),

    // index definition
    'indexName' => null,
    'uniqueBy'  => array(),
    'unique'    => true,

    // listener options
    'listener'  => 'mtSluggableListener',
    'fields'    => array(), // fields used by the slug
    'canUpdate' => true,
    'provider'  => null, // provide a string to slugify
    'builder'   => null, // create an URL friendly slug
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

    if (true == $this->_options['unique'])
    {
      // set index name if it's empty
      if (null === $this->_options['indexName'])
      {
        $this->_options['indexName'] = $this->getTable()->getTableName().'_sluggable';
      }

      // normalize index fields
      if (!is_array($indexFields = $this->_options['uniqueBy']))
      {
        $indexFields = $indexFields ? array($indexFields) : array();
      }

      if (!in_array($this->_options['name'], $indexFields))
      {
        $indexFields = array_merge(array($this->_options['name']), $indexFields);
      }

      $this->index($this->_options['indexName'], array('fields' => $indexFields, 'type' => 'unique'));
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

    $this->addListener($class, 'mt-sluggable');
  }

  /**
   * Disable the sluggable listener..
   */
  public function disableSluggableListener()
  {
    $this->getListener()->get('mt-sluggable')->setOption('disabled', true);
  }

  /**
   * Enable the sluggable listener.
   */
  public function enableSluggableListener()
  {
    $this->getListener()->get('mt-sluggable')->setOption('disabled', false);
  }

  /**
   * Gets slug value.
   *
   * @return mixed
   */
  public function getSlugValue()
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
  public function setSlugValue($value)
  {
    return $this->getInvoker()->set($this->getTable()->getFieldName($this->_options['name']), $value);
  }

  /**
   * Gets the the name of the slug field.
   *
   * @return string
   */
  public function getSlugFieldName()
  {
    return $this->getInvoker()->getTable()->getFieldName($this->_options['name']);
  }

  /**
   * Gets the default value of the slug field.
   *
   * @return mixed
   */
  public function getSlugDefaultValue()
  {
    return $this->getInvoker()->getTable()->getDefaultValueOf($this->getInvoker()->getSlugFieldName());
  }

  /**
   * Gets sluggable field names.
   *
   * @return array
   */
  public function getSluggableFields()
  {
    return (array) $this->_options['fields'];
  }

  /**
   * Gets the length of the slug field.
   *
   * @return int
   */
  public function getSlugFieldLength()
  {
    return $this->getInvoker()->getTable()->getFieldLength($this->getInvoker()->getSlugFieldName());
  }

  /**
   * Cuts down the slug if required.
   *
   * @param string $slug  slug
   * @param int    $spare spare
   *
   * @return string
   */
  public function chopSlug($slug, $spare = 0)
  {
    $length = $this->getInvoker()->getSlugFieldLength();

    if (strlen($slug) > $length)
    {
      return substr($slug, 0, $length - (strlen($spare) + 1));
    }

    return $slug;
  }

  /**
   * Checks whether the slug value is modified or not.
   *
   * @return bool
   */
  public function isSlugModified()
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    return array_key_exists($record->getSlugFieldName(), $record->getModified());
  }

  /**
   * Checks whether a sluggable field is modified or not.
   *
   * @return bool
   */
  public function isSluggableFieldModified()
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    return (boolean) array_intersect($record->getSluggableFields(), array_keys($record->getModified()));
  }

  /**
   * Check whether the slug should be regenerated or not.
   *
   * You can trigger slug regeneration which not depends on
   * the sluggable fields.
   *
   * @return bool
   */
  public function isSlugShouldBeRegenerated()
  {
    return false;
  }

  /**
   * Updates the slug.
   *
   * @param string $slug
   *
   * @return Doctrine_Record|mtSluggable
   */
  public function updateSlug($slug = null)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    $record->setSlugValue($record->generateSlug($slug));

    return $record;
  }

  /**
   * Gets a string to slugify.
   *
   * @return mixed|string
   */
  public function provideSluggableValue()
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();
    $fields = $record->getSluggableFields();

    if (is_callable($this->_options['provider']))
    {
      return call_user_func($this->_options['provider'], $record, $fields);
    }

    if (empty($fields))
    {
      $value = (string) $record;
    }
    else
    {
      $value = '';

      foreach ($fields as $field)
      {
        $value .= $record->get($field) . ' ';
      }

      $value = substr($value, 0, -1);
    }

    return $value;
  }

  /**
   * Creates an URL friendly slug.
   *
   * @param  string $text value to slugify
   *
   * @return string
   */
  public function slugify($text)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    if (is_callable($this->_options['builder']))
    {
      return call_user_func($this->_options['builder'], $text, $record);
    }

    // replace non letter or digits by -
    $text = preg_replace('#[^\\pL\d]+#u', '-', $text);

    // trim
    $text = trim($text, '-');

    // transliterate
    if (function_exists('iconv'))
    {
      $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('#[^-\w]+#', '', $text);

    if (empty($text))
    {
      return 'n-a';
    }

    return $text;
  }

  /**
   * Generates a decent slug.
   *
   * If the slug value was set by the user don't change it
   * just slugify it and make it unique if required.
   *
   * @param null $slug
   *
   * @return string
   */
  public function generateSlug($slug = null)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    if (empty($slug))
    {
      // get an initial value
      $slug = $record->provideSluggableValue();
    }

    // make it URL friendly
    $slug = $record->slugify($slug);
    // cut it down to fit into the column
    $slug = $record->chopSlug($slug);

    // make it unique
    if (true === $this->_options['unique'])
    {
      $slug = $record->makeUniqueSlug($slug);
    }

    return $slug;
  }

  /**
   * Tries to make the slug unique.
   *
   * @param string $slug
   *
   * @return mixed
   */
  public function makeUniqueSlug($slug)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    $similarSlugs = $record->getSimilarSlugs($slug);

    $i = 1;
    while (in_array($slug, $similarSlugs))
    {
      $slug = $record->slugify($slug . '-' . $i);
      $i++;
    }

    // If slug is longer then the column length then we need to trim it
    // and try to generate a unique slug again
    $length = $record->getSlugFieldLength();
    if (strlen($slug) > $length)
    {
      $slug = $record->makeUniqueSlug($record->chopSlug($slug, $i));
    }

    return  $slug;
  }

  /**
   * Gets all slugs which start like the given one.
   *
   * @param string $slug
   *
   * @return array
   */
  public function getSimilarSlugs($slug)
  {
    /** @var $record Doctrine_Record|mtSluggable */
    $record = $this->getInvoker();

    // fix for use with Column Aggregation Inheritance
    if ($record->getTable()->getOption('inheritanceMap'))
    {
      // Be sure that you do not instantiate an abstract class
      $i = 0;
      $parentTable = $record->getTable()->getOption('parents');
      $reflectionClass = new ReflectionClass($parentTable[$i]);

      while ($reflectionClass->isAbstract())
      {
        $i++;
        $reflectionClass = new ReflectionClass($parentTable[$i]);
      }

      $table = Doctrine_Core::getTable($parentTable[$i]);
    }
    else
    {
      $table = $record->getTable();
    }

    $name = $table->getFieldName($this->_options['name']);

    $whereString = 'r.' . $name . ' LIKE ?';
    $whereParams = array($slug . '%');

    if ($record->exists())
    {
      $identifier = $record->identifier();
      $whereString .= ' AND r.' . implode(' != ? AND r.', $table->getIdentifierColumnNames()) . ' != ?';
      $whereParams = array_merge($whereParams, array_values($identifier));
    }

    foreach ($this->_options['uniqueBy'] as $uniqueBy)
    {
      if (null === $record->$uniqueBy)
      {
        $whereString .= ' AND r.' . $uniqueBy . ' IS NULL';
      }
      else
      {
        $whereString .= ' AND r.' . $uniqueBy . ' = ?';
        $value = $record->$uniqueBy;

        if ($value instanceof Doctrine_Record)
        {
          $id = (array) $value->identifier();
          $value = current($id);
        }

        $whereParams[] =  $value;
      }
    }

    // Disable indexBy to ensure we get all records
    $originalIndexBy = $table->getBoundQueryPart('indexBy');
    $table->bindQueryPart('indexBy', null);

    /** @var $query Doctrine_Query */
    $query = $table->createQuery('r')
      ->select('r.' . $name)
      ->where($whereString , $whereParams)
      ->setHydrationMode(Doctrine_Core::HYDRATE_SINGLE_SCALAR);

    // We need to introspect SoftDelete to check if we are not disabling unique records too
    if ($table->hasTemplate('Doctrine_Template_SoftDelete'))
    {
      $softDelete = $table->getTemplate('Doctrine_Template_SoftDelete');

      // we have to consider both situations here
      if ($softDelete->getOption('type') == 'boolean')
      {
        $query->addWhere(sprintf('(r.%1$s = ? OR r.%1$s = ?)', $softDelete->getOption('name')), array(true, false));
      }
      else
      {
        $query->addWhere(sprintf('(r.%1$s IS NULL OR r.%1$s IS NOT NULL)', $softDelete->getOption('name')));
      }
    }

    $results = $query->execute();
    $query->free();

    // Change indexBy back
    $table->bindQueryPart('indexBy', $originalIndexBy);

    return (array) $results;
  }
}
